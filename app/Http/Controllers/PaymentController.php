<?php

namespace App\Http\Controllers;

use App\Enums\PaymentStatus;
use App\Models\PaymentRequest;
use App\Services\Payments\PaymentGatewayManager;
use App\Services\Payments\PaymentService;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Throwable;

class PaymentController extends Controller
{
    public function __construct(
        private PaymentService $service,
        private PaymentGatewayManager $gateways,
    ) {}

    /**
     * Driver / admin creates a top-up request for themselves.
     */
    public function create(Request $request): RedirectResponse
    {
        $minAmount = (int) config('payments.min_amount', 50);
        $maxAmount = (int) config('payments.max_amount', 100000);

        $data = $request->validate([
            'amount' => "required|numeric|min:{$minAmount}|max:{$maxAmount}",
            'comment' => 'nullable|string|max:200',
        ], [
            'amount.min' => "Минимальная сумма пополнения — {$minAmount} ₽.",
            'amount.max' => "Максимальная сумма пополнения за один платёж — {$maxAmount} ₽.",
        ]);

        try {
            $paymentRequest = $this->service->create(
                $request->user(),
                (float) $data['amount'],
                $request->user(),
                $data['comment'] ?? null
            );
        } catch (Throwable $e) {
            report($e);
            return back()
                ->with('toast', ['type' => 'error', 'message' => 'Не удалось создать платёж: '.$e->getMessage()])
                ->withInput();
        }

        return redirect()->route('payments.show', $paymentRequest);
    }

    /**
     * Admin: overview of all SBP top-ups.
     */
    public function index(Request $request): View
    {
        $payments = PaymentRequest::with('user')
            ->latest()
            ->paginate(30);

        return view('payments.index', ['payments' => $payments]);
    }

    /**
     * Admin: refund a confirmed top-up (calls gateway Cancel + reverses balance).
     */
    public function refund(Request $request, PaymentRequest $payment): RedirectResponse
    {
        if (! $payment->isConfirmed()) {
            return back()->with('toast', ['type' => 'error', 'message' => 'Вернуть можно только зачисленный платёж.']);
        }

        try {
            $this->gateways->make($payment->gateway)->cancel($payment);
        } catch (Throwable $e) {
            report($e);
            return back()->with('toast', ['type' => 'error', 'message' => 'Возврат не удался: '.$e->getMessage()]);
        }

        return back()->with('toast', ['type' => 'success', 'message' => "Платёж #{$payment->id} возвращён."]);
    }

    /**
     * Show the QR / status page for a payment request.
     * Only the payer themselves OR an admin can open the page.
     */
    public function show(Request $request, PaymentRequest $payment): View
    {
        $this->authorize($request, $payment);

        return view('payments.show', [
            'payment' => $payment,
            'qrSvg' => $this->renderQrSvg($payment->qr_payload ?? $payment->qr_url ?? ''),
            'isFake' => $payment->gateway === 'fake',
        ]);
    }

    /**
     * Polling endpoint — returns current status as JSON.
     */
    public function status(Request $request, PaymentRequest $payment): JsonResponse
    {
        $this->authorize($request, $payment);

        // For real providers we'd also refresh from remote here. Fake gateway is local-only.
        // Skip terminal payments (confirmed/failed/cancelled/refunded) — their status is final.
        if ($payment->gateway !== 'fake' && $payment->isPending()) {
            try {
                $this->gateways->make($payment->gateway)->refreshStatus($payment);
                $payment->refresh();
            } catch (Throwable $e) {
                // Surface but don't break polling — UI shows the cached state.
                report($e);
            }
        }

        return response()->json([
            'id' => $payment->id,
            'status' => $payment->status->value,
            'status_label' => $payment->status->label(),
            'amount' => (float) $payment->amount,
            'confirmed_at' => $payment->confirmed_at?->format('d.m.Y H:i:s'),
        ]);
    }

    /**
     * Fake-only: scanning the QR (or pressing "имитировать оплату") opens this URL.
     * Confirms the payment, credits the balance, redirects to a success view.
     */
    public function confirmFake(Request $request, PaymentRequest $payment): RedirectResponse|Response
    {
        if (! $payment->isFake()) {
            abort(404);
        }
        if ($payment->isPending()) {
            $this->service->confirm($payment, $request->user()?->id);
            $payment->refresh();
        }

        return redirect()->route('payments.show', $payment)
            ->with('status', 'Платёж зачислен.');
    }

    /**
     * T-Bank notifications post here.
     */
    public function webhookTBank(Request $request): Response
    {
        try {
            $gateway = $this->gateways->make('tbank');
            $gateway->handleWebhook($request->all(), $request->getContent());
        } catch (Throwable $e) {
            report($e);
        }
        // T-Bank expects the literal "OK" body on success.
        return response('OK');
    }

    private function authorize(Request $request, PaymentRequest $payment): void
    {
        $user = $request->user();
        if (! $user) {
            abort(403);
        }
        if ($payment->user_id !== $user->id && ! $user->isAdmin()) {
            abort(403, 'Чужой платёж.');
        }
    }

    private function renderQrSvg(string $payload): string
    {
        if ($payload === '') {
            return '';
        }
        try {
            $renderer = new ImageRenderer(
                new RendererStyle(400, 1),
                new SvgImageBackEnd()
            );
            $writer = new Writer($renderer);
            return $writer->writeString($payload);
        } catch (Throwable $e) {
            report($e);
            return '';
        }
    }
}
