<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Fields that should never be stored in the audit log
     */
    protected static array $ignored = [
        'password',
        'remember_token',
        'updated_at',
        'created_at',
        'signature_data',
        'signature_ip',
        'order_photo',
        'order_photos',
        'delivery_photos',
    ];

    /**
     * Record an activity.
     *
     * @param  string  $action   created|updated|deleted
     * @param  Model   $model    the affected model
     * @param  array   $changes  optional old/new values for updates
     */
    public static function record(string $action, Model $model, array $changes = []): void
    {
        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id'       => $user?->id,
                'user_name'     => $user?->username ?? 'System',
                'user_role'     => $user?->role,
                'action'        => $action,
                'subject_type'  => get_class($model),
                'subject_id'    => $model->getKey(),
                'subject_label' => static::label($model),
                'description'   => static::describe($action, $model),
                'changes'       => empty($changes) ? null : $changes,
                'ip_address'    => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    public static function label(Model $model): string
    {
        $name = class_basename($model);

        return match ($name) {
            'Order'    => 'Order #' . $model->getKey(),
            'Product'  => 'Product: ' . ($model->name ?? '#' . $model->getKey()),
            'Customer' => 'Customer: ' . ($model->name ?? '#' . $model->getKey()),
            'User'     => 'User: ' . ($model->username ?? '#' . $model->getKey()),
            'Visit'    => 'Visit #' . $model->getKey(),
            'BlockedDate' => 'Blocked Date: ' . ($model->blocked_date ?? '#' . $model->getKey()),
            default    => $name . ' #' . $model->getKey(),
        };
    }

    protected static function describe(string $action, Model $model): string
    {
        $verb = match ($action) {
            'created' => 'Created',
            'updated' => 'Updated',
            'deleted' => 'Deleted',
            default   => ucfirst($action),
        };

        return $verb . ' ' . static::label($model);
    }

    public static function diff(Model $model): array
    {
        $changes = [];

        foreach ($model->getChanges() as $field => $newValue) {
            if (in_array($field, static::$ignored, true)) {
                continue;
            }

            $changes[$field] = [
                'old' => $model->getOriginal($field),
                'new' => $newValue,
            ];
        }

        return $changes;
    }
}
