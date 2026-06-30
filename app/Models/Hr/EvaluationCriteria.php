<?php

namespace App\Models\Hr;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EvaluationCriteria extends Model
{
    use SoftDeletes;

    protected $table = 'evaluation_criteria';

    protected $fillable = [
        'company_id', 'name', 'description', 'weight', 'is_active'
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_active' => 'boolean',
    ];
}