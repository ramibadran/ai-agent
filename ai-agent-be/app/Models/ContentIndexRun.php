<?php

namespace App\Models;

use Database\Factories\ContentIndexRunFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'knowledge_content_id',
    'status',
    'content_version',
    'content_checksum',
    'vector_count',
    'started_at',
    'completed_at',
    'error_message',
])]
class ContentIndexRun extends Model
{
    /** @use HasFactory<ContentIndexRunFactory> */
    use HasFactory;

    /**
     * Get the content that this run indexed.
     */
    public function knowledgeContent(): BelongsTo
    {
        return $this->belongsTo(KnowledgeContent::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
