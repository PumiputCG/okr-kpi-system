<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppNotification extends Model
{
    protected $table = 'app_notifications';

    protected $fillable = [
        'recipient_user_id',
        'actor_user_id',
        'type',
        'title',
        'message',
        'link_url',
        'payload_json',
        'is_read',
        'read_at',
    ];

    protected $casts = [
        'recipient_user_id' => 'integer',
        'actor_user_id' => 'integer',
        'title' => PlainText::class,
        'message' => PlainText::class.':required,preserve',
        'payload_json' => 'array',
        'is_read' => 'boolean',
        'read_at' => 'datetime',
    ];

    public function recipient(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'recipient_user_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'actor_user_id');
    }
}
