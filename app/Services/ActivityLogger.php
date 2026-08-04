<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class ActivityLogger
{
    /**
     * Fields that should never be stored in the audit log (noise or sensitive).
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

    /**
     * Capture a full snapshot of a model's attributes as key => [old => value, new => null].
     * Used on DELETE so the log preserves what the record contained, even after
     * the row is permanently removed from the database.
     */
    public static function snapshot(Model $model): array
    {
        $data = [];

        foreach ($model->getAttributes() as $field => $value) {
            if (in_array($field, static::$ignored, true)) {
                continue;
            }

            $data[$field] = [
                'old' => is_array($value) ? json_encode($value) : $value,
                'new' => '(deleted)',
            ];
        }

        return $data;
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

    /**
     * Record a change made to a COA on an order line.
     *
     * @param  \App\Models\Order  $order        parent order (for the label)
     * @param  array              $before       pivot values before the write
     * @param  array              $after        pivot values after the write
     * @param  string|null        $productName  product name for context
     * @param  string             $summary      leading phrase for the description
     */
    public static function recordCoaChange($order, array $before, array $after, ?string $productName = null, string $summary = 'Updated COA'): void
    {
        $track = [
            'coa_template',
            'coa_number',
            'qc_document_number',
            'patient_name',
            'batch_number',
            'coa_product_date',
            'coa_mfg_date',
            'coa_expiry_date',
            'coa_viable_cell_count',
            'coa_signature_date',
            'coa_immuno_cd73',
            'coa_immuno_cd90',
            'coa_immuno_cd105',
            'coa_immuno_negative',
            'coa_morphology_image',
        ];

        $changes = [];
        foreach ($track as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ((string) $old !== (string) $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        if (empty($changes)) {
            return;
        }

        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id'       => $user?->id,
                'user_name'     => $user?->username ?? 'System',
                'user_role'     => $user?->role,
                'action'        => 'updated',
                'subject_type'  => \App\Models\Order::class,
                'subject_id'    => $order->getKey(),
                'subject_label' => 'Order #' . $order->getKey()
                                    . ($productName ? ' (' . $productName . ' COA)' : ' (COA)'),
                'description'   => $summary . ' on Order #' . $order->getKey()
                                    . ($productName ? ' — ' . $productName : ''),
                'changes'       => $changes,
                'ip_address'    => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }

    /**
     * Record a change made to an order_product pivot row (batch number, QC doc,
     * patient name, remarks, prepared_by).
     *
     * @param  \App\Models\Order  $order       the parent order (for the label)
     * @param  array               $before      pivot values before the update
     * @param  array               $after        pivot values after the update
     * @param  string|null         $productName  product name for context
     */
    public static function recordPivotChange($order, array $before, array $after, ?string $productName = null): void
    {
        $track = ['batch_number', 'qc_document_number', 'patient_name', 'remarks', 'prepared_by'];

        $changes = [];
        foreach ($track as $field) {
            $old = $before[$field] ?? null;
            $new = $after[$field] ?? null;
            if ((string) $old !== (string) $new) {
                $changes[$field] = ['old' => $old, 'new' => $new];
            }
        }

        if (empty($changes)) {
            return;
        }

        try {
            $user = Auth::user();

            ActivityLog::create([
                'user_id'       => $user?->id,
                'user_name'     => $user?->username ?? 'System',
                'user_role'     => $user?->role,
                'action'        => 'updated',
                'subject_type'  => \App\Models\Order::class,
                'subject_id'    => $order->getKey(),
                'subject_label' => 'Order #' . $order->getKey()
                                    . ($productName ? ' (' . $productName . ' batch info)' : ' (batch info)'),
                'description'   => 'Updated batch/QC info on Order #' . $order->getKey(),
                'changes'       => $changes,
                'ip_address'    => request()->ip(),
            ]);
        } catch (\Throwable $e) {
            report($e);
        }
    }
}