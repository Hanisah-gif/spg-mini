<?php

namespace App\Actions;

use App\Models\MaintenanceRequest;

class UpdateMaintenanceRequestAction
{
    /**
     * Update the editable details of a request. Status, technician and owner are never changed here.
     *
     * @param  array<string, mixed>  $data  validated fields
     */
    public function execute(MaintenanceRequest $request, array $data): MaintenanceRequest
    {
        $request->update(collect($data)->only([
            'title',
            'category',
            'block_id',
            'level',
            'room',
            'description',
        ])->all());

        return $request;
    }
}
