<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'conversation_id',
    'role',
    'content',
    'retrieval_context',
    'model',
    'prompt_tokens',
    'completion_tokens',
    'total_tokens',
    'latency_ms',
    'cost_usd',
    'status',
    'error_message',
])]
class ConversationMessage extends Model
{
    /**
     * Get the conversation this message belongs to.
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'retrieval_context' => 'array',
            'cost_usd' => 'decimal:8',
        ];
    }
}
