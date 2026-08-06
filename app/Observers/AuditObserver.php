<?php

namespace App\Observers;

use App\Models\AuditLog;
use App\Services\CompanyContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Universal observer that logs all model events to audit_logs.
 */
class AuditObserver
{
    public function created(Model $model): void
    {
        $this->log($model, 'CREATE', null, $model->getAttributes());
    }

    public function updated(Model $model): void
    {
        $changes = $model->getChanges();

        unset($changes['updated_at']);

        if (empty($changes)) {
            return;
        }

        $original = array_intersect_key($model->getOriginal(), $changes);

        $this->log($model, 'UPDATE', $original, $changes);
    }

    public function deleted(Model $model): void
    {
        $this->log($model, 'DELETE', $model->getAttributes(), null);
    }

    public function restored(Model $model): void
    {
        // لا يوجد RESTORE داخل الـ Check Constraint
        $this->log($model, 'UPDATE', null, $model->getAttributes());
    }

    public function forceDeleted(Model $model): void
    {
        $this->log($model, 'DELETE', $model->getAttributes(), null);
    }

    /**
     * Log custom events.
     *
     * مثال:
     * AuditObserver::logEvent($invoice,'APPROVE');
     * AuditObserver::logEvent($invoice,'PRINT');
     */
    public static function logEvent(
        Model $model,
        string $event,
        ?array $old = null,
        ?array $new = null
    ): void {
        (new static())->log($model, $event, $old, $new);
    }

    /**
     * Core logger.
     */
    protected function log(
        Model $model,
        string $event,
        ?array $oldAttributes,
        ?array $newAttributes
    ): void {
        try {

            if (!Schema::hasTable('audit_logs')) {
                return;
            }

            if ($model instanceof AuditLog) {
                return;
            }

            $user = Auth::user();

            // تحويل أسماء الأحداث إلى القيم الموجودة فى قاعدة البيانات
            $event = strtoupper($event);

            $map = [
                'CREATED'        => 'CREATE',
                'UPDATED'        => 'UPDATE',
                'DELETED'        => 'DELETE',
                'RESTORED'       => 'UPDATE',
                'FORCE_DELETED'  => 'DELETE',
                'APPROVED'       => 'APPROVE',
                'REJECTED'       => 'REJECT',
                'PRINTED'        => 'PRINT',
                'EXPORTED'       => 'EXPORT',
            ];

            $event = $map[$event] ?? $event;

            AuditLog::create([
                'company_id' => $model->getAttribute('company_id') ?? CompanyContext::id(),
                'user_id' => $user?->id,
                'table_name' => $model->getTable(),
                'record_id' => $model->getKey(),
                'action_type' => $event,
                'old_values' => $oldAttributes,
                'new_values' => $newAttributes,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);
        } catch (\Throwable $e) {

            Log::warning('Audit log failed', [
                'message' => $e->getMessage(),
            ]);

            // لا نسمح بفشل الـ Audit أن يوقف المعاملة الأساسية
        }
    }
}