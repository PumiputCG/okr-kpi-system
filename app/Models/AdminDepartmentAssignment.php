<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminDepartmentAssignment extends Model
{
    protected $table = 'admin_department_assignments';

    protected $fillable = [
        'dept_abbr_hr',
        'target_user_id',
        'target_user_ids_json',
        'reviewer_user_id',
        'reviewer_user_ids_json',
        'assigned_by_admin_user_id',
    ];

    protected function casts(): array
    {
        return [
            'dept_abbr_hr' => PlainText::class,
            'target_user_id' => 'integer',
            'target_user_ids_json' => 'array',
            'reviewer_user_id' => 'integer',
            'reviewer_user_ids_json' => 'array',
            'assigned_by_admin_user_id' => 'integer',
        ];
    }

    public function targetUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'target_user_id');
    }

    public function reviewerUser(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'reviewer_user_id');
    }

    public function assignedByAdmin(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'assigned_by_admin_user_id');
    }
}
