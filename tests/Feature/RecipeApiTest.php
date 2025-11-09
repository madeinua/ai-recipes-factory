<?php

namespace Tests\Feature;

use App\Jobs\GenerateRecipeJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecipeApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_read_recipe_after_generation(): void
    {
        $resp = $this->postJson('/api/v1/recipes/generate', ['ingredients' => 'water, salt'])
            ->assertStatus(202)
            ->json();

        dispatch_sync(new GenerateRecipeJob($resp['requestId']));

        $poll = $this->getJson('/api/v1/recipes/requests/' . $resp['requestId'])
            ->assertOk()
            ->json();

        $recipeId = $poll['recipe']['id'];

        $this->getJson('/api/v1/recipes/' . $recipeId)
            ->assertOk()
            ->assertJsonPath('data.id', $recipeId);
    }
}
