<?php

namespace App\Filament\Resources\KnowledgeContents\Pages;

use App\Filament\Resources\KnowledgeContents\KnowledgeContentResource;
use App\Services\V1\KnowledgeContentService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateKnowledgeContent extends CreateRecord
{
    protected static string $resource = KnowledgeContentResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(KnowledgeContentService::class)->create(auth()->user(), $data);
    }
}
