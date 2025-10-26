<?php

namespace Tests\Feature;

use App\Jobs\GenerateRecipeJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeGenerationFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_end_to_end_without_worker(): void
    {
        // #1 POST -> 202 + requestId
        $resp = $this->postJson('/api/v1/recipes/generate', ['ingredients' => 'chicken, rice, garlic'])
            ->assertStatus(202)
            ->json();

        $requestId = $resp['requestId'];

        // #2 Run the job inline (no queue worker)
        dispatch_sync(new GenerateRecipeJob($requestId));

        // #3 Poll request -> COMPLETED + recipe
        $poll = $this->getJson("/api/v1/recipes/requests/{$requestId}")
            ->assertOk()
            ->assertJsonPath('status', 'COMPLETED')
            ->json();

        $recipeId = $poll['recipe']['id'];

        // #4 Read the recipe directly
        $this->getJson("/api/v1/recipes/{$recipeId}")
            ->assertOk()
            ->assertJsonPath('id', $recipeId);
    }
}
