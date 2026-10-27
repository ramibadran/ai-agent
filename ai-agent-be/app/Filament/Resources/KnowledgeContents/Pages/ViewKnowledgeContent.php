<?php

namespace App\Filament\Resources\KnowledgeContents\Pages;

use App\Filament\Resources\KnowledgeContents\KnowledgeContentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewKnowledgeContent extends ViewRecord
{
    protected static string $resource = KnowledgeContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
