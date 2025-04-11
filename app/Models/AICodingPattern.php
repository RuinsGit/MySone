<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AICodingPattern extends Model
{
    protected $fillable = [
        'pattern_name',
        'pattern_description',
        'code_template',
        'language',
        'variables',
        'use_cases',
        'usage_count'
    ];

    protected $casts = [
        'variables' => 'array',
        'use_cases' => 'array',
        'usage_count' => 'integer'
    ];

    public function codes()
    {
        return $this->hasMany(AICoding::class, 'language', 'language');
    }
} 