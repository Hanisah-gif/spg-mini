<?php

namespace App\Actions;

use App\Models\MaintenanceRequest;
use App\Models\Remark;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AddRemark
{
    /**
     * Save a technician remark in the remarks table inside a database transaction.
     */
    public function execute(MaintenanceRequest $request, User $technician, string $body): Remark
    {
        return DB::transaction(function () use ($request, $technician, $body) {
            return Remark::create([
                'maintenance_request_id' => $request->id,
                'technician_id' => $technician->id,
                'remark' => trim($body),
            ]);
        });
    }
}
