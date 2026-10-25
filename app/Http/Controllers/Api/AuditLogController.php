<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\CostCentre;
use App\Models\Customer;
use App\Models\Department;
use App\Models\ExpenseCategory;
use App\Models\Invoice;
use App\Models\SiteJob;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    /**
     * Foreign keys shown by name instead of id: field => [model, display column].
     */
    protected const REFERENCES = [
        'customer_id' => [Customer::class, 'name'],
        'site_job_id' => [SiteJob::class, 'job_number'],
        'invoice_id' => [Invoice::class, 'invoice_number'],
        'department_id' => [Department::class, 'name'],
        'expense_category_id' => [ExpenseCategory::class, 'name'],
        'cost_centre_id' => [CostCentre::class, 'name'],
    ];

    public function index(Request $request): JsonResponse
    {
        $query = AuditLog::query()->orderByDesc('created_at')->orderByDesc('id');

        if ($request->filled('type') && isset(AuditLog::TYPES[$request->type])) {
            $query->where('auditable_type', (new (AuditLog::TYPES[$request->type]['model']))->getMorphClass());
        }

        // History of one record, e.g. ?type=invoice&record_id=20
        if ($request->filled('record_id')) {
            $query->where('auditable_id', $request->record_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('from_date')) {
            $query->where('created_at', '>=', $request->from_date);
        }

        if ($request->filled('to_date')) {
            $query->where('created_at', '<=', $request->to_date . ' 23:59:59');
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('label', 'ilike', "%{$search}%")
                  ->orWhere('user_name', 'ilike', "%{$search}%");
            });
        }

        $logs = $query->paginate($request->input('per_page', 25));
        $names = $this->referenceNames($logs->getCollection());
        $logs->getCollection()->transform(fn (AuditLog $log) => $this->format($log, $names));

        return response()->json($logs);
    }

    /**
     * Undo a deletion recorded in the log (soft-deleted records only).
     */
    public function restore(AuditLog $auditLog): JsonResponse
    {
        if ($auditLog->event !== 'deleted' || !$this->canRestore($auditLog)) {
            return response()->json(['message' => 'This record cannot be restored.'], 422);
        }

        $record = $auditLog->auditable_type::onlyTrashed()->find($auditLog->auditable_id);

        if (!$record) {
            return response()->json(['message' => 'This record has already been restored.'], 422);
        }

        $record->restore();

        return response()->json(['message' => "{$auditLog->label} restored"]);
    }

    protected function format(AuditLog $log, array $names = []): array
    {
        $type = AuditLog::typeKeyFor($log->auditable_type);
        $withNames = function (?array $values) use ($names) {
            if ($values === null) {
                return null;
            }
            foreach ($values as $field => $value) {
                if ($value !== null && isset($names[$field][$value])) {
                    $values[$field] = $names[$field][$value];
                }
            }
            return $values;
        };

        return [
            'id' => $log->id,
            'event' => $log->event,
            'type' => $type,
            'type_label' => $type ? AuditLog::TYPES[$type]['label'] : class_basename($log->auditable_type),
            'record_id' => $log->auditable_id,
            'label' => $log->label,
            'user_id' => $log->user_id,
            'user_name' => $log->user_name,
            'old_values' => $withNames($log->old_values),
            'new_values' => $withNames($log->new_values),
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at,
            'can_restore' => $log->event === 'deleted' && $this->canRestore($log),
        ];
    }

    /**
     * Look up display names for every foreign key on the page, one query per model.
     */
    protected function referenceNames($logs): array
    {
        $ids = [];
        foreach ($logs as $log) {
            foreach ([$log->old_values ?? [], $log->new_values ?? []] as $values) {
                foreach (array_intersect_key($values, self::REFERENCES) as $field => $id) {
                    if ($id !== null) {
                        $ids[$field][] = $id;
                    }
                }
            }
        }

        $names = [];
        foreach ($ids as $field => $fieldIds) {
            [$model, $column] = self::REFERENCES[$field];
            $query = $model::query();
            if (in_array(SoftDeletes::class, class_uses_recursive($model))) {
                $query->withTrashed();
            }
            $names[$field] = $query->whereIn('id', array_unique($fieldIds))->pluck($column, 'id')->all();
        }

        return $names;
    }

    protected function canRestore(AuditLog $log): bool
    {
        $class = $log->auditable_type;

        return class_exists($class)
            && in_array(SoftDeletes::class, class_uses_recursive($class))
            && $class::onlyTrashed()->whereKey($log->auditable_id)->exists();
    }
}
