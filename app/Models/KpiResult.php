<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiResult extends Model
{
    protected $table = 'kpi_result';

    protected $fillable = [
        'app_user_id',
        'cycle_id',
        'dept_abbr_hr',
        'result',
    ];

    protected $casts = [
        'app_user_id' => 'integer',
        'cycle_id' => 'integer',
        'dept_abbr_hr' => 'string',
        'result' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }
}
