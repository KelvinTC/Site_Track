<?php

namespace App\Models;

use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use BelongsToCompany;

    // Audit entries are written once and never updated
    public const UPDATED_AT = null;

    /**
     * Audited models and how they appear in the log. Keys are used as the "type" filter.
     */
    public const TYPES = [
        'customer' => ['model' => Customer::class, 'label' => 'Customer'],
        'job' => ['model' => SiteJob::class, 'label' => 'Job'],
        'employee' => ['model' => Casual::class, 'label' => 'Employee'],
        'invoice' => ['model' => Invoice::class, 'label' => 'Invoice'],
        'payment' => ['model' => Payment::class, 'label' => 'Payment'],
        'expense' => ['model' => Expense::class, 'label' => 'Expense'],
        'company' => ['model' => Company::class, 'label' => 'Company settings'],
        'team' => ['model' => User::class, 'label' => 'Team member'],
    ];

    /** Never stored in the log */
    public const HIDDEN_FIELDS = ['password', 'remember_token', 'created_at', 'updated_at', 'deleted_at'];

    protected $fillable = [
        'company_id',
        'user_id',
        'user_name',
        'event',
        'auditable_type',
        'auditable_id',
        'label',
        'old_values',
        'new_values',
        'ip_address',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'created_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Write an entry for the current user and request.
     */
    public static function record(string $event, Model $model, ?array $old, ?array $new, ?int $companyId = null, ?string $label = null): self
    {
        $user = auth()->user();

        return static::create([
            'company_id' => $companyId ?? $model->company_id ?? $user?->current_company_id,
            'user_id' => $user?->id,
            'user_name' => $user?->name ?? 'System',
            'event' => $event,
            'auditable_type' => $model->getMorphClass(),
            'auditable_id' => $model->getKey(),
            'label' => $label ?? (method_exists($model, 'auditLabel') ? $model->auditLabel() : null),
            'old_values' => $old ? static::clean($old) : null,
            'new_values' => $new ? static::clean($new) : null,
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }

    protected static function clean(array $values): array
    {
        return array_diff_key($values, array_flip(static::HIDDEN_FIELDS));
    }

    public static function typeKeyFor(string $class): ?string
    {
        foreach (static::TYPES as $key => $type) {
            if ($type['model'] === $class) {
                return $key;
            }
        }

        return null;
    }
}
