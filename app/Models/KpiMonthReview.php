<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KpiMonthReview extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';

    protected $table = 'kpi_month_reviews';

    protected $fillable = [
        'kpi_month_score_id',
        'status',
        'reviewed_by_user_id',
        'reviewed_at',
        'reject_detail',
    ];

    protected $casts = [
        'reject_detail' => PlainText::class.':nullable',
        'kpi_month_score_id' => 'integer',
        'reviewed_by_user_id' => 'integer',
        'reviewed_at' => 'datetime',
    ];

    public function monthScore(): BelongsTo
    {
        return $this->belongsTo(KpiMonthScore::class, 'kpi_month_score_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'reviewed_by_user_id');
    }
}
