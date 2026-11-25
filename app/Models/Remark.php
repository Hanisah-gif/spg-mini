<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

// STAND-IN for Person 2's shared schema. On merge, keep Person 2's version.
class Remark extends Model
{
    use HasFactory;

    protected $fillable = ['maintenance_request_id', 'user_id', 'body'];

    public function maintenanceRequest(): BelongsTo
    {
        return $this->belongsTo(MaintenanceRequest::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
