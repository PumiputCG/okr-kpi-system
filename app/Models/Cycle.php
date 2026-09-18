<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Cycle extends Model
{
    protected $table = 'cycles';

    public const STATUS_OPEN = 'open';
    public const STATUS_CLOSED = 'closed';

    protected $fillable = [
        'name',
        'code',
        'start_date',
        'end_date',
        'status',
        'is_active',
        'opened_at',
        'closed_at',
        'note',
    ];

    protected $casts = [
        'name' => PlainText::class,
        'note' => PlainText::class.':nullable',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    public static function activeId(): ?int
    {
        return static::query()
            ->where('is_active', true)
            ->where('status', self::STATUS_OPEN)
            ->orderByDesc('id')
            ->value('id');
    }

    public static function active(): ?self
    {
        return static::query()
            ->where('is_active', true)
            ->where('status', self::STATUS_OPEN)
            ->orderByDesc('id')
            ->first();
    }

    public function months(): HasMany
    {
        return $this->hasMany(CycleMonth::class, 'cycle_id');
    }
}
