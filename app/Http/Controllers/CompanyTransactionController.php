<?php

namespace App\Http\Controllers;

use App\Models\CompanyTransaction;
use App\Models\User;
use App\Services\CompanyLedger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CompanyTransactionController extends Controller
{
    public function index(): View
    {
        $items = CompanyTransaction::with('creator')->latest('id')->paginate(30);

        $incomeTotal = (float) CompanyTransaction::where('type', 'income')->sum('amount');
        $expenseTotal = (float) CompanyTransaction::where('type', 'expense')->sum('amount');

        // Owners summary: percent, current balance, share of income/expense.
        $owners = collect(config('owners.shares', []))->map(function ($o) use ($incomeTotal, $expenseTotal) {
            $user = User::where('login', $o['login'])->first();
            $pct = (float) $o['percent'];
            return [
                'name' => $user?->full_name ?? $o['login'],
                'percent' => $pct,
                'balance' => (float) ($user->balance ?? 0),
                'income_share' => round($incomeTotal * $pct / 100, 2),
                'expense_share' => round($expenseTotal * $pct / 100, 2),
            ];
        });

        return view('company.index', compact('items', 'incomeTotal', 'expenseTotal', 'owners'));
    }

    public function create(): View
    {
        return view('company.create');
    }

    public function store(Request $request, CompanyLedger $ledger): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'amount' => ['required', 'numeric', 'min:0.01', 'max:99999999.99'],
            'comment' => ['required', 'string', 'max:500'],
        ], [
            'comment.required' => 'Укажите назначение транзакции.',
        ]);

        $ledger->record($data['type'], (float) $data['amount'], $data['comment'], 'manual', null, $request->user()->id);

        return redirect()->route('company.index')
            ->with('toast', ['type' => 'success', 'message' => 'Транзакция компании добавлена и разделена по владельцам.']);
    }
}
