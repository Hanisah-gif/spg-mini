<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// STAND-IN for Person 2's shared schema. On merge, keep Person 2's version.
class StatusLog extends Model
{
    use HasFactory;

    protected $fillable = ['maintenance_request_id', 'from_status', 'status', 'changed_by'];

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
