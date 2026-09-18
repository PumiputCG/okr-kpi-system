<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminHomeHierarchyConfig extends Model
{
    protected $table = 'admin_home_hierarchy_configs';

    protected $fillable = [
        'admin_user_id',
        'levels_count',
        'layout_json',
        'last_saved_at',
    ];

    protected $casts = [
        'admin_user_id' => 'integer',
        'levels_count' => 'integer',
        'layout_json' => 'array',
        'last_saved_at' => 'datetime',
    ];

    public function admin(): BelongsTo
    {
        return $this->belongsTo(AppUser::class, 'admin_user_id');
    }
}

