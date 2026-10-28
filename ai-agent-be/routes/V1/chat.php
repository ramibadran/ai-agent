<?php

use App\Http\Controllers\API\V1\Chat\ConversationController;
use Illuminate\Support\Facades\Route;

Route::name('chat.')
    ->prefix('chat')
    ->middleware('jwtAuth')
    ->group(function (): void {
        Route::get('conversations', [ConversationController::class, 'index'])->name('conversations.index');
        Route::post('conversations', [ConversationController::class, 'store'])->name('conversations.store');
        Route::get('conversations/{conversation}', [ConversationController::class, 'show'])->name('conversations.show');
        Route::post('conversations/{conversation}/messages', [ConversationController::class, 'storeMessage'])->name('conversations.messages.store');
        Route::delete('conversations/{conversation}', [ConversationController::class, 'destroy'])->name('conversations.destroy');
    });
