<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class KpiMonthScore extends Model
{
    protected $table = 'kpi_month_scores';

    protected $fillable = [
        'kpi_meta_id',
        'app_user_id',
        'cycle_id',
        'month_no',
        'objective',
        'detail',
        'target_departments',
        'okr_objective_id',
        'okr_key_result_id',
        'parent_target_kpi_id',
        'target_value',
        'kpi_unit_id',
        'criteria_operator',
        'has_criteria',
        'mode_type',
        'score_value',
        'is_pass',
        'result',
        'evidence_files',
        'action_plan_files',
        'submitted_at',
    ];

    protected $casts = [
        'kpi_meta_id' => 'integer',
        'app_user_id' => 'integer',
        'cycle_id' => 'integer',
        'month_no' => 'integer',
        'objective' => PlainText::class,
        'detail' => PlainText::class,
        'target_departments' => 'array',
        'okr_objective_id' => 'integer',
        'okr_key_result_id' => 'integer',
        'parent_target_kpi_id' => 'integer',
        'target_value' => 'decimal:10',
        'kpi_unit_id' => 'integer',
        'has_criteria' => 'boolean',
        'score_value' => 'decimal:10',
        'is_pass' => 'boolean',
        'result' => 'decimal:2',
        'evidence_files' => 'array',
        'action_plan_files' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'app_user_id');
    }

    public function cycle(): BelongsTo
    {
        return $this->belongsTo(Cycle::class, 'cycle_id');
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(KpiUnit::class, 'kpi_unit_id');
    }

    public function review(): HasOne
    {
        return $this->hasOne(KpiMonthReview::class, 'kpi_month_score_id');
    }
}
