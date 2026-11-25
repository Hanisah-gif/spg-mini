<?php

namespace App\Models;

use Database\Factories\MaintenanceRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MaintenanceRequest extends Model
{
    /** @use HasFactory<MaintenanceRequestFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    /** Status value => label. Use this everywhere, never raw strings. */
    public const STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In Progress',
        self::STATUS_COMPLETED => 'Completed',
    ];

    public const CATEGORIES = ['Electrical', 'Plumbing', 'Furniture', 'Cleaning', 'Other'];

    protected $fillable = [
        'resident_id',
        'technician_id',
        'block_id',
        'room',
        'level',
        'title',
        'description',
        'category',
        'status',
        'admin_remarks',
    ];

    /** @return BelongsTo<User, $this> */
    public function resident(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resident_id');
    }

    /** @return BelongsTo<User, $this> */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /** @return BelongsTo<Block, $this> */
    public function block(): BelongsTo
    {
        return $this->belongsTo(Block::class);
    }

    /** @return HasMany<StatusLog, $this> */
    public function statusLogs(): HasMany
    {
        return $this->hasMany(StatusLog::class)->latest('id');
    }

    /** @return HasMany<Remark, $this> */
    public function remarks(): HasMany
    {
        return $this->hasMany(Remark::class)->latest('id');
    }

    public function locationLabel(): string
    {
        return sprintf(
            '%s, Level %s, Room %s',
            $this->block->block_name ?? 'Unknown block',
            $this->level,
            $this->room,
        );
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isUnassigned(): bool
    {
        return $this->technician_id === null;
    }
}
