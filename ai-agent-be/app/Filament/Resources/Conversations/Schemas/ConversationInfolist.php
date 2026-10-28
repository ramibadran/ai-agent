<?php

namespace App\Filament\Resources\Conversations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;

class ConversationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('title')->columnSpanFull(),
                TextEntry::make('user.name')->label('User'),
                TextEntry::make('last_message_at')->dateTime(),
                RepeatableEntry::make('messages')
                    ->schema([
                        TextEntry::make('role')->badge(),
                        TextEntry::make('content')->columnSpanFull(),
                        TextEntry::make('model')->placeholder('-'),
                        TextEntry::make('total_tokens')->label('Tokens')->placeholder('-'),
                        TextEntry::make('latency_ms')->label('Latency (ms)')->placeholder('-'),
                        TextEntry::make('cost_usd')->label('Estimated cost (USD)')->placeholder('-'),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('retrieval_context')
                            ->label('Retrieved sources')
                            ->formatStateUsing(function (mixed $state): string {
                                if (is_string($state)) {
                                    $state = json_decode($state, true);
                                }

                                if (! is_array($state) || $state === []) {
                                    return '-';
                                }

                                return collect($state)
                                    ->map(function (mixed $source): string {
                                        if (is_string($source)) {
                                            $source = json_decode($source, true);
                                        }

                                        if (! is_array($source)) {
                                            return 'Unknown source';
                                        }

                                        $title = (string) ($source['title'] ?? 'Untitled source');
                                        $score = isset($source['score']) ? number_format((float) $source['score'], 3) : '-';

                                        return "{$title} (score: {$score})";
                                    })
                                    ->implode('; ');
                            })
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),
            ]);
    }
}
