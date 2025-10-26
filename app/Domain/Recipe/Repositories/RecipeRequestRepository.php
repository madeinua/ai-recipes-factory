<?php

namespace App\Domain\Recipe\Repositories;

use App\Domain\Recipe\Entities\RecipeRequest;

interface RecipeRequestRepository
{
    public function findById(string $id): ?RecipeRequest;

    public function createPending(string $ingredientsCsv, ?string $webhookUrl = null): string;

    public function markProcessing(string $id): void;

    public function markCompleted(string $id, string $recipeId): void;

    public function markFailed(string $id, string $errorMessage): void;

    public function findCompletedByIngredients(string $rawIngredientsCsv): ?RecipeRequest;

    public function findCompletedByHash(string $hash): ?RecipeRequest;

    public function existsActiveByHash(string $hash, ?string $excludeRequestId = null): bool;

    public function markAllByHashCompleted(string $hash, string $recipeId): int;
}
