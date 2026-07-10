<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function created(Model $model): void
    {
        ActivityLogger::record('created', $model);
    }

    public function updated(Model $model): void
    {
        $changes = ActivityLogger::diff($model);

        // If only ignored fields changed, skip the log entirely.
        if (empty($changes)) {
            return;
        }

        ActivityLogger::record('updated', $model, $changes);
    }

    public function deleted(Model $model): void
    {
        // Capture a full field details before delete
        $snapshot = ActivityLogger::snapshot($model);

        ActivityLogger::record('deleted', $model, $snapshot);
    }
}
