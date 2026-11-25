<?php

namespace App\Policies;

use App\Models\MaintenanceRequest;
use App\Models\User;

class MaintenanceRequestPolicy
{
    // --- Person 2's abilities (kept) -------------------------------------

    public function viewAny(User $user): bool
    {
        return $user->isAdmin();
    }

    /** Assign / reassign a technician. A completed job can no longer be reassigned. */
    public function assign(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin() && ! $request->isCompleted();
    }

    public function overrideStatus(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin();
    }

    // --- Person 1's abilities --------------------------------------------

    public function view(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin()
            || $this->ownsRequest($user, $request)
            || $this->isAssignedTechnician($user, $request);
    }

    /** Only residents submit requests. */
    public function create(User $user): bool
    {
        return $user->isResident();
    }

    /** Resident edits only while Pending and unassigned. Technicians cannot touch a completed job. */
    public function update(User $user, MaintenanceRequest $request): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        if ($this->isAssignedTechnician($user, $request)) {
            return ! $request->isCompleted();
        }

        return $this->residentCanChange($user, $request);
    }

    /** Resident deletes only while Pending and unassigned. */
    public function delete(User $user, MaintenanceRequest $request): bool
    {
        return $user->isAdmin() || $this->residentCanChange($user, $request);
    }

    /** Progress and remarks: assigned technician only, never on a completed job. */
    public function updateProgress(User $user, MaintenanceRequest $request): bool
    {
        return $this->isAssignedTechnician($user, $request) && ! $request->isCompleted();
    }

    private function ownsRequest(User $user, MaintenanceRequest $request): bool
    {
        return $user->isResident() && (int) $request->resident_id === (int) $user->id;
    }

    private function isAssignedTechnician(User $user, MaintenanceRequest $request): bool
    {
        return $user->isTechnician()
            && $request->technician_id !== null
            && (int) $request->technician_id === (int) $user->id;
    }

    private function residentCanChange(User $user, MaintenanceRequest $request): bool
    {
        return $this->ownsRequest($user, $request)
            && $request->isPending()
            && $request->isUnassigned();
    }
}
