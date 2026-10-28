<?php

namespace App\Filament\Resources\Conversations\Tables;

use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ConversationsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')->searchable()->limit(60),
                TextColumn::make('user.name')->label('User')->searchable(),
                TextColumn::make('messages_count')->counts('messages')->label('Messages')->sortable(),
                TextColumn::make('last_message_at')->dateTime()->sortable(),
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('user_id')->relationship('user', 'email')->label('User'),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
