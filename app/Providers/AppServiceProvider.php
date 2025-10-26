<?php

namespace App\Providers;

use App\Domain\Recipe\Repositories\RecipeRepository;
use App\Domain\Recipe\Repositories\RecipeRequestRepository;
use App\Domain\Recipe\Services\AiRecipeGenerator;
use App\Infrastructure\AI\OpenAiRecipeGenerator;
use App\Infrastructure\Repositories\EloquentRecipeRepository;
use App\Infrastructure\Repositories\EloquentRecipeRequestRepository;
use App\Infrastructure\AI\FakeAiRecipeGenerator;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(RecipeRepository::class, EloquentRecipeRepository::class);
        $this->app->bind(RecipeRequestRepository::class, EloquentRecipeRequestRepository::class);

        $this->app->bind(AiRecipeGenerator::class, function () {
            $driver = env('AI_DRIVER', 'fake');

            return match ($driver) {
                'openai' => $this->app->make(OpenAiRecipeGenerator::class),
                default => $this->app->make(FakeAiRecipeGenerator::class),
            };
        });
    }

    public function boot(): void
    {
        $this->configureRateLimiting();
    }

    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', static function (Request $request) {
            return Limit::perMinute(60)->by($request->ip());
        });

        RateLimiter::for('recipes-generate', static function (Request $request) {
            $key = 'recipes-generate:' . $request->ip();
            return [
                Limit::perMinute(10)->by($key),
                Limit::perHour(100)->by($key),
            ];
        });
    }
}
