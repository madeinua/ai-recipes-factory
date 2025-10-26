<?php

use App\Http\Middleware\ForceJsonResponse;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecipeGenerationController;
use App\Http\Controllers\RecipeController;

Route::prefix('v1')->middleware([ForceJsonResponse::class, 'throttle:api'])->group(function () {

    Route::post('/recipes/generate', [RecipeGenerationController::class, 'generate'])
        ->name('recipes.generate')
        ->middleware('throttle:recipes-generate');

    Route::get('/recipes/requests/{id}', [RecipeGenerationController::class, 'showRequest'])
        ->name('recipes.requests.show');

    Route::get('/recipes/{id}', [RecipeController::class, 'show']);
});
