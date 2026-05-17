<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Car;
use App\Models\PaymentRequest;
use App\Models\Rental;
use App\Models\Tariff;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Throwable;

class ActivityLogger
{
    /**
     * Fields excluded from the diff. Sensitive (password) or noisy (timestamps, balances).
     * Balance changes are logged separately via dedicated transaction events.
     */
    private const DIFF_EXCLUDE = [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'balance',
    ];

    /**
     * Write a log entry. Never throws — failure to log must not break the user flow.
     */
    public static function log(
        string $action,
        ?Model $subject = null,
        ?string $description = null,
        ?array $changes = null,
        ?int $actorId = null,
    ): void {
        try {
            $actorId = $actorId ?? auth()->id();
            $actorLabel = null;
            if ($actorId) {
                $actor = User::find($actorId);
                $actorLabel = $actor?->short_name ?? "user#{$actorId}";
            } else {
                $actorLabel = 'system';
            }

            $isCli = app()->runningInConsole();

            $subjectLabel = $subject ? static::labelFor($subject) : null;
            // Hard cap to fit DB column. Defensive — labelFor should already be short.
            if ($subjectLabel !== null && mb_strlen($subjectLabel) > 250) {
                $subjectLabel = mb_substr($subjectLabel, 0, 250).'…';
            }
            $descriptionTrim = $description !== null && mb_strlen($description) > 495
                ? mb_substr($description, 0, 495).'…'
                : $description;

            ActivityLog::create([
                'user_id' => $actorId,
                'actor_label' => $actorLabel ? mb_substr($actorLabel, 0, 250) : null,
                'action' => $action,
                'subject_type' => $subject ? get_class($subject) : null,
                'subject_id' => $subject?->getKey(),
                'subject_label' => $subjectLabel,
                'description' => $descriptionTrim,
                'changes' => $changes && count($changes) ? $changes : null,
                'ip_address' => $isCli ? null : request()->ip(),
                'user_agent' => $isCli ? 'cli' : substr((string) request()->userAgent(), 0, 250),
                'created_at' => Carbon::now(),
            ]);
        } catch (Throwable $e) {
            // Never break the application because of logging.
            report($e);
        }
    }

    /**
     * Build an old → new diff from a freshly-saved model.
     * Call AFTER ->save(). Excludes sensitive/noisy fields by default.
     */
    public static function diff(Model $model, array $extraExclude = []): array
    {
        $exclude = array_merge(self::DIFF_EXCLUDE, $extraExclude);
        $changes = $model->getChanges();
        $original = $model->getOriginal();
        $diff = [];

        foreach ($changes as $key => $newVal) {
            if (in_array($key, $exclude, true)) {
                continue;
            }
            $old = $original[$key] ?? null;
            // Skip noise where nothing really changed (cast normalization)
            if ((string) $old === (string) $newVal) {
                continue;
            }
            $diff[$key] = [
                'old' => $old,
                'new' => $newVal,
            ];
        }

        return $diff;
    }

    /**
     * Human-readable identifier of the subject at log time.
     *
     * NOTE: We do NOT fall back to (string) $subject — Eloquent's base Model::__toString
     * dumps the whole model as JSON, which blows past the 255-char subject_label column.
     */
    private static function labelFor(Model $subject): ?string
    {
        return match (true) {
            $subject instanceof User => $subject->full_name,
            $subject instanceof Car => $subject->display_name.' · '.$subject->license_plate,
            $subject instanceof Tariff => $subject->name,
            $subject instanceof Rental => 'Аренда #'.$subject->getKey(),
            $subject instanceof PaymentRequest =>
                'Платёж #'.$subject->getKey().' · '.number_format((float) $subject->amount, 2, '.', ' ').' ₽',
            default => 'id:'.$subject->getKey(),
        };
    }
}
