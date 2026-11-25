<?php

namespace App\Http\Requests;

use App\Models\MaintenanceRequest;

/**
 * Same fields and rules as the submit form, so create and edit can never drift apart.
 * Only authorize() differs: the resident must be allowed to update this request.
 */
class UpdateMaintenanceRequest extends StoreMaintenanceRequest
{
    public function authorize(): bool
    {
        $maintenanceRequest = $this->route('request');

        return $maintenanceRequest instanceof MaintenanceRequest
            && ($this->user()?->can('update', $maintenanceRequest) ?? false);
    }
}
