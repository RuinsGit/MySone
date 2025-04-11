<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AICodingSession extends Model
{
    protected $fillable = [
        'session_id',
        'context',
        'conversation_history',
        'code_snippets',
        'active_language',
        'variables'
    ];

    protected $casts = [
        'conversation_history' => 'array',
        'code_snippets' => 'array',
        'variables' => 'array'
    ];

    public function codes()
    {
        return $this->belongsTo(AICoding::class);
    }
} 