<?php

namespace App\Actions;

use App\Models\MaintenanceRequest;
use App\Models\StatusLog;
use Illuminate\Support\Facades\DB;

class CreateMaintenanceRequest
{
    /**
     * Create a Pending request and its first status log in one transaction.
     *
     * @param  array<string, mixed>  $data  validated fields plus resident_id
     */
    public function execute(array $data): MaintenanceRequest
    {
        return DB::transaction(function () use ($data) {
            $request = MaintenanceRequest::create([
                ...$data,
                'status' => MaintenanceRequest::STATUS_PENDING,
            ]);

            StatusLog::create([
                'maintenance_request_id' => $request->id,
                'from_status' => null,
                'status' => MaintenanceRequest::STATUS_PENDING,
                'changed_by' => $request->resident_id,
            ]);

            return $request;
        });
    }
}
