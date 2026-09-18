<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AdminHomeAnnouncement extends Model
{
    protected $table = 'admin_home_announcements';

    protected $fillable = [
        'admin_user_id',
        'posted_by_user_id',
        'level_no',
        'parent_announcement_id',
        'dept_abbr_hr',
        'title',
        'detail',
        'posted_at',
    ];

    protected $casts = [
        'admin_user_id' => 'integer',
        'posted_by_user_id' => 'integer',
        'level_no' => 'integer',
        'parent_announcement_id' => 'integer',
        'dept_abbr_hr' => 'string',
        'title' => PlainText::class.':nullable',
        'detail' => PlainText::class.':nullable,preserve',
        'posted_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'admin_user_id');
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'posted_by_user_id');
    }

    public function files(): HasMany
    {
        return $this->hasMany(AdminHomeAnnouncementFile::class, 'announcement_id')->orderByDesc('id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_announcement_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_announcement_id');
    }
}
