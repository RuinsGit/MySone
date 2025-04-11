<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AICoding extends Model
{
    protected $fillable = [
        'code_content',
        'language',
        'category',
        'description',
        'usage_example',
        'parameters',
        'dependencies',
        'complexity',
        'usage_count',
        'success_rate',
        'tags',
        'is_tested',
        'test_results'
    ];

    protected $casts = [
        'parameters' => 'array',
        'dependencies' => 'array',
        'tags' => 'array',
        'test_results' => 'array',
        'complexity' => 'float',
        'success_rate' => 'float',
        'is_tested' => 'boolean'
    ];

    public function patterns()
    {
        return $this->hasMany(AICodingPattern::class, 'language', 'language');
    }

    public function sessions()
    {
        return $this->hasMany(AICodingSession::class);
    }
} 