<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use App\Domain\Recipe\Services\AiRecipeGenerator;
use App\Infrastructure\AI\FakeAiRecipeGenerator;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->app->bind(AiRecipeGenerator::class, FakeAiRecipeGenerator::class);
    }
}
