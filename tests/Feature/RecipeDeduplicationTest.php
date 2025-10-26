<?php

namespace Tests\Feature;

use App\Jobs\GenerateRecipeJob;
use App\Models\Recipe;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;

class RecipeDeduplicationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_recipes(): void
    {
        ### First request
        $first = $this->postJson('/api/v1/recipes/generate', ['ingredients' => 'chicken, rice, garlic'])
            ->assertStatus(202)
            ->json();

        dispatch_sync(new GenerateRecipeJob($first['requestId']));

        $poll1 = $this->getJson('/api/v1/recipes/requests/' . $first['requestId'])
            ->assertOk()
            ->assertJsonPath('status', 'COMPLETED')
            ->json();

        $firstRecipeId = $poll1['recipe']['id'];
        $this->assertNotEmpty($firstRecipeId);

        ### Duplicate request after completion
        Bus::fake();

        $second = $this->postJson('/api/v1/recipes/generate', ['ingredients' => 'GARLIC,   Chicken, rice'])
            ->assertStatus(202)
            ->assertJsonPath('deduped', true)
            ->assertJsonPath('status', 'COMPLETED')
            ->json();

        Bus::assertNotDispatched(GenerateRecipeJob::class);

        ### Poll the duplicate
        $poll2 = $this->getJson('/api/v1/recipes/requests/' . $second['requestId'])
            ->assertOk()
            ->assertJsonPath('status', 'COMPLETED')
            ->json();

        $this->assertSame($firstRecipeId, $poll2['recipe']['id'], 'Duplicate must alias to the original recipe');
        $this->assertSame(1, Recipe::query()->count(), 'Only one recipe should exist in DB');
    }

    public function test_duplicate_recipes_2(): void
    {
        ### Only the first POST should dispatch a job
        Bus::fake();

        $first = $this->postJson('/api/v1/recipes/generate', ['ingredients' => 'beef, onion'])
            ->assertStatus(202)
            ->json();

        $second = $this->postJson('/api/v1/recipes/generate', ['ingredients' => 'ONION,   beef'])
            ->assertStatus(202)
            ->assertJsonPath('deduped', true)
            ->json();

        Bus::assertDispatchedTimes(GenerateRecipeJob::class);

        ### Both are pending before the job runs
        $this->getJson('/api/v1/recipes/requests/' . $first['requestId'])
            ->assertOk()
            ->assertJsonPath('status', 'PENDING');

        $this->getJson('/api/v1/recipes/requests/' . $second['requestId'])
            ->assertOk()
            ->assertJsonPath('status', 'PENDING');

        $job = new GenerateRecipeJob($first['requestId']);
        app()->call([$job, 'handle']);

        ### Now both must be COMPLETED and share the same recipe
        $poll1 = $this->getJson('/api/v1/recipes/requests/' . $first['requestId'])
            ->assertOk()
            ->assertJsonPath('status', 'COMPLETED')
            ->json();

        $poll2 = $this->getJson('/api/v1/recipes/requests/' . $second['requestId'])
            ->assertOk()
            ->assertJsonPath('status', 'COMPLETED')
            ->json();

        $this->assertSame($poll1['recipe']['id'], $poll2['recipe']['id'], 'Both requests must reference the same recipe');
        $this->assertSame(1, Recipe::query()->count(), 'Only one recipe row should exist');
    }
}
