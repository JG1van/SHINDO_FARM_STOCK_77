<?php

use App\Models\ActivityLog;

if (!function_exists('logActivity')) {
    /**
     * Catat aktivitas manual ke activity_logs.
     * Aksi tanpa login di-skip total.
     */
    function logActivity(string $action, ?string $model = null, $dataId = null, string $description = ''): void
    {
        if (!auth()->check()) {
            return;
        }

        ActivityLog::create([
    'user_id' => auth()->id(),
    'action' => $action,
    'logable_type' => $modelName,
    'logable_id' => $id,
    'description' => $description,
    'ip_address' => request()->ip(),
]);
    }
}
