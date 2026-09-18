<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CycleMonth extends Model
{
    protected $table = 'cycle_months';

    protected $fillable = [
        'cycle_id',
        'month_no',
        'open_at',
        'is_active',
    ];

    protected $casts = [
        'month_no' => 'integer',
        'open_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }
}
