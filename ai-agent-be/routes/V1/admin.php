<?php

use App\Http\Controllers\API\V1\Admin\KnowledgeContentController;
use Illuminate\Support\Facades\Route;

Route::name('admin.')
    ->prefix('admin')
    ->middleware(['jwtAuth', 'admin'])
    ->group(function (): void {
        Route::apiResource('knowledge-contents', KnowledgeContentController::class)
            ->parameters(['knowledge-contents' => 'knowledgeContent']);
    });
