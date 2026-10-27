<?php

namespace App\Filament\Resources\KnowledgeContents\Tables;

use App\Models\KnowledgeContent;
use App\Services\V1\KnowledgeContentService;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class KnowledgeContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('version')
                    ->numeric()
                    ->sortable(),
                IconColumn::make('indexed_at')
                    ->label('Indexed')
                    ->boolean(),
                TextColumn::make('index_runs_count')
                    ->counts('indexRuns')
                    ->label('Index runs'),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')->options([
                    'draft' => 'Draft',
                    'published' => 'Published',
                    'unpublished' => 'Unpublished',
                ]),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->action(fn (KnowledgeContent $record) => app(KnowledgeContentService::class)->delete($record)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([]),
            ]);
    }
}
