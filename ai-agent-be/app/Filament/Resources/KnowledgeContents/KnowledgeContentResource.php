<?php

namespace App\Filament\Resources\KnowledgeContents;

use App\Filament\Resources\KnowledgeContents\Pages\CreateKnowledgeContent;
use App\Filament\Resources\KnowledgeContents\Pages\EditKnowledgeContent;
use App\Filament\Resources\KnowledgeContents\Pages\ListKnowledgeContents;
use App\Filament\Resources\KnowledgeContents\Pages\ViewKnowledgeContent;
use App\Filament\Resources\KnowledgeContents\Schemas\KnowledgeContentForm;
use App\Filament\Resources\KnowledgeContents\Schemas\KnowledgeContentInfolist;
use App\Filament\Resources\KnowledgeContents\Tables\KnowledgeContentsTable;
use App\Models\KnowledgeContent;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class KnowledgeContentResource extends Resource
{
    protected static ?string $model = KnowledgeContent::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema
    {
        return KnowledgeContentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return KnowledgeContentInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return KnowledgeContentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListKnowledgeContents::route('/'),
            'create' => CreateKnowledgeContent::route('/create'),
            'view' => ViewKnowledgeContent::route('/{record}'),
            'edit' => EditKnowledgeContent::route('/{record}/edit'),
        ];
    }
}
