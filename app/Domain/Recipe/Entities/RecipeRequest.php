<?php

namespace App\Domain\Recipe\Entities;

use App\Domain\Recipe\Enums\RecipeRequestStatus;

final readonly class RecipeRequest
{
    /**
     * @param string $id
     * @param string $ingredientsCsv
     * @param string $ingredientsHash
     * @param string|null $webhookUrl
     * @param RecipeRequestStatus $status
     * @param string|null $recipeId
     * @param string|null $errorMessage
     * @param \DateTimeImmutable $createdAt
     * @param \DateTimeImmutable $updatedAt
     */
    public function __construct(
        public string $id,
        public string $ingredientsCsv,
        public string $ingredientsHash,
        public ?string $webhookUrl,
        public RecipeRequestStatus $status,
        public ?string $recipeId,
        public ?string $errorMessage,
        public \DateTimeImmutable $createdAt,
        public \DateTimeImmutable $updatedAt,
    ) {
    }
}
