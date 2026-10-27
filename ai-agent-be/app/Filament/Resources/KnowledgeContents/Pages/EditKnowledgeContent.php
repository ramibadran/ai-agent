<?php

namespace App\Filament\Resources\KnowledgeContents\Pages;

use App\Filament\Resources\KnowledgeContents\KnowledgeContentResource;
use App\Models\KnowledgeContent;
use App\Services\V1\KnowledgeContentService;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditKnowledgeContent extends EditRecord
{
    protected static string $resource = KnowledgeContentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make()
                ->action(fn (KnowledgeContent $record) => app(KnowledgeContentService::class)->delete($record)),
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return app(KnowledgeContentService::class)->update($record, $data);
    }
}
