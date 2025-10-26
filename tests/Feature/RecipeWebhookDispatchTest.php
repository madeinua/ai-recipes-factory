<?php

namespace Tests\Feature;

use App\Domain\Recipe\Services\AiRecipeGenerator;
use App\Jobs\GenerateRecipeJob;
use App\Jobs\NotifyWebhookJob;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class RecipeWebhookDispatchTest extends TestCase
{
    use DatabaseMigrations;

    public function test_webhook_job_is_dispatched_on_completion(): void
    {
        Queue::fake();
        Http::fake();

        $webhook = 'https://example.test/hooks/recipes';

        $resp = $this->postJson('/api/v1/recipes/generate', [
            'ingredients' => 'tomato, basil, pasta',
            'webhook_url' => $webhook,
        ])
            ->assertStatus(202)
            ->json();

        $this->assertDatabaseHas('recipe_requests', [
            'id'          => $resp['requestId'],
            'webhook_url' => $webhook,
        ]);

        $job = new GenerateRecipeJob($resp['requestId']);
        app()->call([$job, 'handle']);

        Queue::assertPushedOn('webhooks', NotifyWebhookJob::class);
        Queue::assertPushed(NotifyWebhookJob::class, static function (NotifyWebhookJob $job) use ($resp, $webhook) {
            return $job->url === $webhook
                   && $job->requestId === $resp['requestId']
                   && $job->status === 'COMPLETED'
                   && !empty($job->recipeId);
        });
    }

    public function test_webhook_job_is_dispatched_on_failure(): void
    {
        Queue::fake();
        Http::fake();

        $this->mock(AiRecipeGenerator::class)
            ->shouldReceive('generate')
            ->andThrow(new \RuntimeException('AI provider down'));

        $webhook = 'https://example.test/hooks/recipes';

        $resp = $this->postJson('/api/v1/recipes/generate', [
            'ingredients' => 'bad, input',
            'webhook_url' => $webhook,
        ])
            ->assertStatus(202)
            ->json();

        $this->assertDatabaseHas('recipe_requests', [
            'id'          => $resp['requestId'],
            'webhook_url' => $webhook,
        ]);

        try {
            $job = new GenerateRecipeJob($resp['requestId']);
            app()->call([$job, 'handle']);
            $this->fail('GenerateRecipeJob should have thrown');
        } catch (\Throwable) {
        }

        Queue::assertPushedOn('webhooks', NotifyWebhookJob::class);
        Queue::assertPushed(NotifyWebhookJob::class, static function (NotifyWebhookJob $job) use ($resp, $webhook) {
            return $job->url === $webhook
                   && $job->requestId === $resp['requestId']
                   && $job->status === 'FAILED'
                   && $job->recipeId === null;
        });
    }
}
