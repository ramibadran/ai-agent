<?php

namespace App\Filament\Resources\KnowledgeContents\Schemas;

use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class KnowledgeContentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('created_by')
                    ->numeric(),
                TextEntry::make('title'),
                TextEntry::make('slug'),
                TextEntry::make('body')
                    ->columnSpanFull(),
                TextEntry::make('source_url')
                    ->placeholder('-'),
                TextEntry::make('status'),
                TextEntry::make('version')
                    ->numeric(),
                TextEntry::make('content_checksum')
                    ->placeholder('-'),
                TextEntry::make('metadata')
                    ->placeholder('-')
                    ->columnSpanFull(),
                TextEntry::make('published_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('indexed_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }
}
