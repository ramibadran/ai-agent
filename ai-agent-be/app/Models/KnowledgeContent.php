<?php

namespace App\Models;

use Database\Factories\KnowledgeContentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'created_by',
    'title',
    'slug',
    'body',
    'source_url',
    'status',
    'version',
    'content_checksum',
    'metadata',
    'published_at',
    'indexed_at',
])]
class KnowledgeContent extends Model
{
    /** @use HasFactory<KnowledgeContentFactory> */
    use HasFactory;

    /**
     * Get the user who created this knowledge content.
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get all index runs for this knowledge content.
     */
    public function indexRuns(): HasMany
    {
        return $this->hasMany(ContentIndexRun::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'published_at' => 'datetime',
            'indexed_at' => 'datetime',
        ];
    }
}
