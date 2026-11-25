<?php

namespace App\Actions\Admin;

use App\Models\MaintenanceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Single responsibility: let an admin override a request's status
 * and record the change in status_logs (who + when).
 */
class OverrideRequestStatus
{
    public function execute(
        MaintenanceRequest $request,
        string $newStatus,
        User $admin,
        ?string $adminRemarks = null,
    ): MaintenanceRequest {
        if (! array_key_exists($newStatus, MaintenanceRequest::STATUSES)) {
            throw ValidationException::withMessages([
                'newStatus' => 'Invalid status selected.',
            ]);
        }

        if ($newStatus === $request->status) {
            throw ValidationException::withMessages([
                'newStatus' => 'The request is already '.MaintenanceRequest::STATUSES[$newStatus].'.',
            ]);
        }

        // Status update and audit log succeed or fail together.
        DB::transaction(function () use ($request, $newStatus, $admin, $adminRemarks) {
            $oldStatus = $request->status;

            $request->update(array_filter([
                'status' => $newStatus,
                'admin_remarks' => $adminRemarks,
            ], fn ($value) => $value !== null));

            $request->statusLogs()->create([
                'from_status' => $oldStatus,
                'status' => $newStatus,
                'changed_by' => $admin->id,
            ]);
        });

        return $request->fresh(['statusLogs']);
    }
}
