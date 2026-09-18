<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OkrObjective extends Model
{
    protected $table = 'okr_objectives';

    protected $fillable = [
        'cycle_id',
        'sort_no',
        'title',
        'detail',
        'file_path',
        'file_original_name',
        'created_by_admin_id',
    ];

    protected function casts(): array
    {
        return [
            'cycle_id' => 'integer',
            'sort_no' => 'integer',
            'created_by_admin_id' => 'integer',
            'title' => PlainText::class,
            'detail' => PlainText::class.':nullable',
            'file_original_name' => PlainText::class.':nullable',
        ];
    }

    public function keyResults(): HasMany
    {
        return $this->hasMany(OkrKeyResult::class, 'okr_objective_id');
    }
}
