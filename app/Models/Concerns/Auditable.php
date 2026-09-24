<?php

namespace App\Models\Concerns;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;

trait Auditable
{
    protected static array $auditExcept = ['password', 'remember_token', 'created_at', 'updated_at'];

    public static function bootAuditable(): void
    {
        static::created(fn (Model $model) => $model->writeAudit('created', [], $model->getAttributes()));

        static::updated(function (Model $model) {
            $changes = array_diff_key($model->getChanges(), array_flip(static::$auditExcept));
            if ($changes) {
                $model->writeAudit('updated', array_intersect_key($model->getOriginal(), $changes), $changes);
            }
        });

        static::deleted(fn (Model $model) => $model->writeAudit('deleted', $model->getAttributes(), []));
    }

    public function shouldAudit(): bool
    {
        return true;
    }

    public function auditLabel(): string
    {
        return $this->name ?? $this->title ?? $this->key ?? '#'.$this->getKey();
    }

    protected function writeAudit(string $event, array $old, array $new): void
    {
        if (! $this->shouldAudit()) {
            return;
        }

        $clean = fn (array $values) => array_diff_key($values, array_flip(static::$auditExcept)) ?: null;

        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'auditable_type' => static::class,
            'auditable_id' => $this->getKey(),
            'description' => $this->auditLabel(),
            'old_values' => $clean($old),
            'new_values' => $clean($new),
            'ip_address' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
