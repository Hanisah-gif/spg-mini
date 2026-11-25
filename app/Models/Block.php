<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

// STAND-IN for Person 2's shared schema. On merge, keep Person 2's version.
class Block extends Model
{
    use HasFactory;

    protected $fillable = ['name'];
}
