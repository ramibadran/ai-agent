<?php

namespace App\Filament\Resources\KnowledgeContents\Schemas;

use Filament\Forms\Components\Textarea;
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
                Textarea::make('body')
                    ->required()
                    ->rows(16)
                    ->maxLength(100000)
                    ->columnSpanFull(),
                TextInput::make('source_url')
                    ->url()
                    ->maxLength(2048),
            ]);
    }
}
