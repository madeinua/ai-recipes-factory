<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Recipe\Entities\RecipeRequest as RequestEntity;
use App\Domain\Recipe\Enums\RecipeRequestStatus;
use App\Domain\Recipe\Repositories\RecipeRequestRepository;
use App\Domain\Recipe\Support\IngredientsHelper;
use App\Models\RecipeRequest as RequestModel;
use Illuminate\Support\Str;

final class EloquentRecipeRequestRepository implements RecipeRequestRepository
{
    /**
     * @throws \Exception
     * @param string $id
     * @return RequestEntity|null
     */
    public function findById(string $id): ?RequestEntity
    {
        $m = RequestModel::query()->find($id);

        return $m ? $this->toEntity($m) : null;
    }

    /**
     * @param string $ingredientsCsv
     * @param string|null $webhookUrl
     * @return string
     */
    public function createPending(string $ingredientsCsv, ?string $webhookUrl = null): string
    {
        $id = (string) Str::uuid();
        $hash = IngredientsHelper::hash($ingredientsCsv);

        $m = new RequestModel();
        $m->id = $id;
        $m->ingredients_csv = $ingredientsCsv;
        $m->ingredients_hash = $hash;
        $m->status = RecipeRequestStatus::PENDING;
        $m->webhook_url = $webhookUrl;
        $m->save();

        return $id;
    }

    /**
     * @param string $id
     * @return void
     */
    public function markProcessing(string $id): void
    {
        RequestModel::query()->whereKey($id)->update([
            'status' => RecipeRequestStatus::PROCESSING->value
        ]);
    }

    /**
     * @param string $id
     * @param string $recipeId
     * @return void
     */
    public function markCompleted(string $id, string $recipeId): void
    {
        RequestModel::query()->whereKey($id)->update([
            'status'        => RecipeRequestStatus::COMPLETED->value,
            'recipe_id'     => $recipeId,
            'error_message' => null,
        ]);
    }

    /**
     * @param string $id
     * @param string $errorMessage
     * @return void
     */
    public function markFailed(string $id, string $errorMessage): void
    {
        RequestModel::query()->whereKey($id)->update([
            'status'        => RecipeRequestStatus::FAILED->value,
            'error_message' => mb_strimwidth($errorMessage, 0, 1000),
        ]);
    }

    /**
     * @throws \Exception
     * @param string $rawIngredientsCsv
     * @return RequestEntity|null
     */
    public function findCompletedByIngredients(string $rawIngredientsCsv): ?RequestEntity
    {
        $hash = IngredientsHelper::hash($rawIngredientsCsv);

        $m = RequestModel::query()
            ->where('ingredients_hash', $hash)
            ->where('status', RecipeRequestStatus::COMPLETED->value)
            ->orderByDesc('created_at')
            ->first();

        return $m ? $this->toEntity($m) : null;
    }

    /**
     * @param string $hash
     * @param string|null $excludeRequestId
     * @return bool
     */
    public function existsActiveByHash(string $hash, ?string $excludeRequestId = null): bool
    {
        $q = RequestModel::query()
            ->where('ingredients_hash', $hash)
            ->whereIn('status', [
                RecipeRequestStatus::PENDING->value,
                RecipeRequestStatus::PROCESSING->value,
            ]);

        if ($excludeRequestId) {
            $q->where('id', '!=', $excludeRequestId);
        }

        return $q->exists();
    }

    /**
     * @param string $hash
     * @param string $recipeId
     * @return int
     */
    public function markAllByHashCompleted(string $hash, string $recipeId): int
    {
        return RequestModel::query()
            ->where('ingredients_hash', $hash)
            ->whereIn('status', [
                RecipeRequestStatus::PENDING->value,
                RecipeRequestStatus::PROCESSING->value,
            ])
            ->update([
                'status'        => RecipeRequestStatus::COMPLETED->value,
                'recipe_id'     => $recipeId,
                'error_message' => null,
                'updated_at'    => now(),
            ]);
    }

    /**
     * @throws \Exception
     * @param RequestModel $m
     * @return RequestEntity
     */
    private function toEntity(RequestModel $m): RequestEntity
    {
        return RequestEntity::create(
            id: (string) $m->id,
            ingredientsCsv: (string) $m->ingredients_csv,
            ingredientsHash: (string) $m->ingredients_hash,
            webhookUrl: $m->webhook_url ? (string) $m->webhook_url : null,
            status: $m->status,
            recipeId: $m->recipe_id ? (string) $m->recipe_id : null,
            errorMessage: $m->error_message ? (string) $m->error_message : null,
            createdAt: new \DateTimeImmutable($m->created_at?->toAtomString() ?? 'now'),
            updatedAt: new \DateTimeImmutable($m->updated_at?->toAtomString() ?? 'now'),
        );
    }
}
