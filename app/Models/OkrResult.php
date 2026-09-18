<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OkrResult extends Model
{
    protected $table = 'okr_result';

    protected $fillable = [
        'dept_abbr_hr',
        'cycle_id',
        'result',
    ];

    protected $casts = [
        'cycle_id' => 'integer',
        'result' => 'decimal:2',
    ];

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }
}
