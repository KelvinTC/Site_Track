<?php

namespace App\Traits;

use App\Models\AuditLog;
use App\Models\Company;

/**
 * Records create, update, delete and restore events in the audit log.
 */
trait Auditable
{
    public static function bootAuditable(): void
    {
        static::created(function ($model) {
            AuditLog::record('created', $model, null, $model->getAttributes(), $model->auditCompanyId());
        });

        static::updated(function ($model) {
            $changes = array_diff_key($model->getChanges(), array_flip(AuditLog::HIDDEN_FIELDS));

            // Restoring a soft-deleted record only touches deleted_at; the restored event covers it
            if (empty($changes)) {
                return;
            }

            $old = array_intersect_key($model->getRawOriginal(), $changes);
            AuditLog::record('updated', $model, $old, $changes, $model->auditCompanyId());
        });

        static::deleted(function ($model) {
            AuditLog::record('deleted', $model, $model->getRawOriginal(), null, $model->auditCompanyId());
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                AuditLog::record('restored', $model, null, null, $model->auditCompanyId());
            });
        }
    }

    /**
     * Human-readable identifier shown in the log, e.g. INV-2026-0001.
     */
    public function auditLabel(): string
    {
        foreach (['invoice_number', 'job_number', 'casual_number', 'expense_number', 'reference_number', 'name', 'title'] as $field) {
            if (!empty($this->{$field})) {
                $label = $this->{$field};
                // Show who an employee is, not just their number
                if ($field === 'casual_number' && $this->name) {
                    $label .= ' · ' . $this->name;
                }
                return $label;
            }
        }

        return '#' . $this->getKey();
    }

    protected function auditCompanyId(): ?int
    {
        return $this instanceof Company ? $this->getKey() : $this->company_id;
    }
}
