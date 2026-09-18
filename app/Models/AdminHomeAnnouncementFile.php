<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminHomeAnnouncementFile extends Model
{
    protected $table = 'admin_home_announcement_files';

    protected $fillable = [
        'announcement_id',
        'uploaded_by',
        'original_name',
        'storage_path',
        'mime_type',
        'size_bytes',
    ];

    protected $casts = [
        'announcement_id' => 'integer',
        'uploaded_by' => 'integer',
        'original_name' => PlainText::class,
        'size_bytes' => 'integer',
    ];

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(AdminHomeAnnouncement::class, 'announcement_id');
    }
}
