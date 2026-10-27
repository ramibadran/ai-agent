<?php

namespace App\Filament\Resources\KnowledgeContents\Pages;

use App\Filament\Resources\KnowledgeContents\KnowledgeContentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListKnowledgeContents extends ListRecords
{
    protected static string $resource = KnowledgeContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
