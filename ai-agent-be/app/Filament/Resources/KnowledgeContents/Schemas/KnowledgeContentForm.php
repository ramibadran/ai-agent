<?php

namespace App\Filament\Resources\KnowledgeContents\Schemas;

use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class KnowledgeContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('title')
                    ->required(),
                MarkdownEditor::make('body')
                    ->label('Document body')
                    ->required()
                    ->minHeight('28rem')
                    ->maxHeight('48rem')
                    ->maxLength(100000)
                    ->columnSpanFull(),
                TextInput::make('source_url')
                    ->url()
                    ->maxLength(2048),
            ]);
    }
}
