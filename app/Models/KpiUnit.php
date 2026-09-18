<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KpiUnit extends Model
{
    protected $table = 'kpi_units';

    protected $fillable = [
        'code',
        'name_th',
        'name_en',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'code' => PlainText::class,
        'name_th' => PlainText::class,
        'name_en' => PlainText::class,
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function scores(): HasMany
    {
        return $this->hasMany(KpiMonthScore::class, 'kpi_unit_id');
    }
}
