<?php

namespace App\Actions;

use App\Models\MaintenanceRequest;
use App\Models\Remark;
use App\Models\StatusLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class UpdateProgress
{
    /**
     * Transition status (pending -> in_progress -> completed), optionally save a remark,
     * and record a status log inside a single database transaction.
     */
    public function execute(
        MaintenanceRequest $request,
        User $technician,
        string $nextStatus,
        ?string $remarks = null
    ): MaintenanceRequest {
        $allowedNext = match ($request->status) {
            MaintenanceRequest::STATUS_PENDING => [MaintenanceRequest::STATUS_IN_PROGRESS],
            MaintenanceRequest::STATUS_IN_PROGRESS => [MaintenanceRequest::STATUS_COMPLETED],
            default => [],
        };

        if (! in_array($nextStatus, $allowedNext, true)) {
            throw new InvalidArgumentException(
                sprintf('Cannot transition request from [%s] to [%s].', $request->status, $nextStatus)
            );
        }

        return DB::transaction(function () use ($request, $technician, $nextStatus, $remarks) {
            $previousStatus = $request->status;

            $request->update([
                'status' => $nextStatus,
            ]);

            StatusLog::create([
                'maintenance_request_id' => $request->id,
                'from_status' => $previousStatus,
                'status' => $nextStatus,
                'changed_by' => $technician->id,
            ]);

            $trimmedRemark = trim((string) $remarks);
            if ($trimmedRemark !== '') {
                Remark::create([
                    'maintenance_request_id' => $request->id,
                    'technician_id' => $technician->id,
                    'remark' => $trimmedRemark,
                ]);
            }

            return $request->fresh(['statusLogs.changer', 'remarks.technician', 'block', 'resident']);
        });
    }
}