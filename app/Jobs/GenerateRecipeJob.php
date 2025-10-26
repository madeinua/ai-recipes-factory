<?php

namespace App\Jobs;

use App\Domain\Recipe\Enums\RecipeRequestStatus;
use App\Domain\Recipe\Repositories\RecipeRepository;
use App\Domain\Recipe\Repositories\RecipeRequestRepository;
use App\Domain\Recipe\Services\AiRecipeGenerator;
use App\Domain\Recipe\Entities\Recipe as RecipeEntity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

final class GenerateRecipeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    /**
     * @param string $requestId
     */
    public function __construct(
        public string $requestId
    ) {
    }

    /**
     * @throws \Throwable
     * @param RecipeRepository $recipes
     * @param AiRecipeGenerator $ai
     * @param RecipeRequestRepository $requests
     * @return void
     */
    public function handle(
        RecipeRequestRepository $requests,
        RecipeRepository $recipes,
        AiRecipeGenerator $ai,
    ): void {

        $req = $requests->findById($this->requestId);

        // Nothing to process
        if (!$req || $req->status !== RecipeRequestStatus::PENDING) {
            return;
        }

        // Similar request already completed (by ingredients)
        $existing = $requests->findCompletedByIngredients($req->ingredientsCsv);
        if ($existing && $existing->recipeId) {
            $requests->markAllByHashCompleted($req->ingredientsHash, $existing->recipeId);
            return;
        }

        $requests->markProcessing($req->id);

        try {
            $gen = $ai->generate(
                array_filter(array_map('trim', explode(',', $req->ingredientsCsv)))
            );

            $now = new \DateTimeImmutable();
            $recipe = new RecipeEntity(
                id: (string) Str::uuid(),
                title: $gen['title'],
                excerpt: $gen['excerpt'],
                instructions: $gen['instructions'],
                numberOfPersons: $gen['numberOfPersons'],
                timeToCook: $gen['timeToCook'],
                timeToPrepare: $gen['timeToPrepare'],
                ingredients: $gen['ingredients'],
                createdAt: $now,
                updatedAt: $now
            );

            $recipeId = $recipes->save($recipe);

            $fresh = $requests->findById($req->id);
            if ($fresh && $fresh->webhookUrl) {
                NotifyWebhookJob::dispatch(
                    $fresh->webhookUrl,
                    $fresh->id,
                    RecipeRequestStatus::COMPLETED->value,
                    $recipeId
                )->afterCommit()->onQueue('webhooks');
            }

            $requests->markAllByHashCompleted($req->ingredientsHash, $recipeId);

        } catch (\Throwable $e) {
            $requests->markFailed($req->id, $e->getMessage());

            $fresh = $requests->findById($req->id);
            if ($fresh && $fresh->webhookUrl) {
                NotifyWebhookJob::dispatch(
                    $fresh->webhookUrl,
                    $fresh->id,
                    RecipeRequestStatus::FAILED->value,
                    null
                )->afterCommit()->onQueue('webhooks');
            }

            throw $e;
        }
    }
}
