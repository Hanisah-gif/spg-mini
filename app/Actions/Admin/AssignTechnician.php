<?php

namespace App\Actions\Admin;

use App\Models\MaintenanceRequest;
use App\Models\User;
use InvalidArgumentException;

/**
 * Single responsibility: assign (or reassign) a technician to a request.
 */
class AssignTechnician
{
    public function execute(MaintenanceRequest $request, User $technician): MaintenanceRequest
    {
        // Defensive check — the Form Request already validates this,
        // but the Action must stay correct if it is called from elsewhere.
        if (! $technician->isTechnician()) {
            throw new InvalidArgumentException('Only technicians can be assigned to a request.');
        }

        if ($request->isCompleted()) {
            throw new InvalidArgumentException('A completed request cannot be reassigned.');
        }

        $request->update([
            'technician_id' => $technician->id,
        ]);

        return $request->fresh(['technician']);
    }
}
