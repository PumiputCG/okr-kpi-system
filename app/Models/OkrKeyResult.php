<?php

namespace App\Models;

use App\Casts\PlainText;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OkrKeyResult extends Model
{
    protected $table = 'okr_key_results';

    protected $fillable = [
        'okr_objective_id',
        'dept_abbr_hr',
        'sort_no',
        'title',
        'detail',
        'file_path',
        'file_original_name',
    ];

    protected function casts(): array
    {
        return [
            'okr_objective_id' => 'integer',
            'sort_no' => 'integer',
            'title' => PlainText::class,
            'detail' => PlainText::class.':nullable',
            'file_original_name' => PlainText::class.':nullable',
        ];
    }

    public function objective(): BelongsTo
    {
        return $this->belongsTo(OkrObjective::class, 'okr_objective_id');
    }
}
