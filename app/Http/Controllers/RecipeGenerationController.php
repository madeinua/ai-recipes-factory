<?php

namespace App\Http\Controllers;

use App\Domain\Recipe\Enums\RecipeRequestStatus;
use App\Domain\Recipe\Repositories\RecipeRepository;
use App\Domain\Recipe\Repositories\RecipeRequestRepository;
use App\Domain\Recipe\Support\IngredientsHelper;
use App\Http\Requests\Recipe\GenerateRecipeRequest;
use App\Http\Resources\RecipeResource;
use App\Jobs\GenerateRecipeJob;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;

final class RecipeGenerationController extends Controller
{
    /**
     * @param GenerateRecipeRequest $request
     * @param RecipeRequestRepository $requests
     * @return JsonResponse
     */
    public function generate(GenerateRecipeRequest $request, RecipeRequestRepository $requests): JsonResponse
    {
        $ingredientsCsv = $request->validated()['ingredients'];
        $hash = IngredientsHelper::hash($ingredientsCsv);
        $lock = Cache::lock("recipe-req:{$hash}", 5);

        try {
            return $lock->block(3, function () use ($requests, $request, $ingredientsCsv, $hash) {
                $webhookUrl = $request->validated()['webhook_url'] ?? null;
                $requestId = $requests->createPending($ingredientsCsv, $webhookUrl);

                // Find recipe with the same ingredients
                $completed = $requests->findCompletedByHash($hash);
                if ($completed && $completed->recipeId) {
                    $requests->markCompleted($requestId, $completed->recipeId);

                    return response()->json([
                        'requestId' => $requestId,
                        'status'    => RecipeRequestStatus::COMPLETED->value,
                        'deduped'   => true,
                        'location'  => route('recipes.requests.show', ['id' => $requestId]),
                    ], 202);
                }

                // Check for active requests with the same ingredients - and return without creating a new job
                if ($requests->existsActiveByHash($hash, $requestId)) {
                    return response()->json([
                        'requestId' => $requestId,
                        'status'    => RecipeRequestStatus::PENDING->value,
                        'deduped'   => true,
                        'location'  => route('recipes.requests.show', ['id' => $requestId]),
                    ], 202);
                }

                GenerateRecipeJob::dispatch($requestId)->afterCommit();

                return response()->json([
                    'requestId' => $requestId,
                    'status'    => RecipeRequestStatus::PENDING->value,
                    'deduped'   => false,
                    'location'  => route('recipes.requests.show', ['id' => $requestId]),
                ], 202);
            });
        } catch (LockTimeoutException) {
            // Lock timeout means another process is handling the same ingredients
            // Check for existing requests instead of creating unnecessary duplicates
            $webhookUrl = $request->validated()['webhook_url'] ?? null;

            // First check if a completed recipe already exists
            $completed = $requests->findCompletedByHash($hash);
            $requestId = $requests->createPending($ingredientsCsv, $webhookUrl);
            if ($completed && $completed->recipeId) {
                $requests->markCompleted($requestId, $completed->recipeId);

                return response()->json([
                    'requestId' => $requestId,
                    'status'    => RecipeRequestStatus::COMPLETED->value,
                    'deduped'   => true,
                    'location'  => route('recipes.requests.show', ['id' => $requestId]),
                ], 202);
            }

            // Otherwise, create pending request without dispatching job
            // (another process is already working on it)
            return response()->json([
                'requestId' => $requestId,
                'status'    => RecipeRequestStatus::PENDING->value,
                'deduped'   => true,
                'location'  => route('recipes.requests.show', ['id' => $requestId]),
            ], 202);
        }
    }

    /**
     * @param string $id
     * @param RecipeRequestRepository $requests
     * @param RecipeRepository $recipes
     * @return JsonResponse
     */
    public function showRequest(string $id, RecipeRequestRepository $requests, RecipeRepository $recipes): JsonResponse
    {
        $req = $requests->findById($id);
        if (!$req) {
            return response()->json(['message' => 'Not found'], 404);
        }

        if ($req->status === RecipeRequestStatus::COMPLETED && $req->recipeId) {
            $recipe = $recipes->findById($req->recipeId);

            return response()->json([
                'id'     => $req->id,
                'status' => $req->status->value,
                'recipe' => $recipe ? RecipeResource::make($recipe)->resolve() : null,
            ]);
        }

        return response()->json([
            'id'           => $req->id,
            'status'       => $req->status->value,
            'errorMessage' => $req->errorMessage,
        ]);
    }
}
