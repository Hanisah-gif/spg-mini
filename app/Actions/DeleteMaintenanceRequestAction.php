<?php

namespace App\Actions;

use App\Models\MaintenanceRequest;
use Illuminate\Support\Facades\DB;

class DeleteMaintenanceRequestAction
{
    public function execute(MaintenanceRequest $request): void
    {
        DB::transaction(fn () => $request->delete());
    }
}
