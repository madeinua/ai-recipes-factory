<?php

namespace App\Infrastructure\AI;

use App\Domain\Recipe\Services\AiRecipeGenerator;
use App\Domain\Recipe\ValueObjects\Ingredient;

final class FakeAiRecipeGenerator implements AiRecipeGenerator
{
    /**
     * @param array $ingredients
     * @return array
     */
    public function generate(array $ingredients): array
    {
        $outputIngredients = [];
        foreach ($ingredients as $ingredient) {
            $outputIngredients[] = new Ingredient(
                name: $ingredient,
                value: 100.0,
                measure: 'mg',
            );
        }

        return [
            'title'           => 'Recipe with ' . implode(', ', $ingredients),
            'excerpt'         => 'Auto-generated demo recipe.',
            'instructions'    => [
                'Prepare all ingredients.',
                'Combine thoughtfully.',
                'Cook until done.',
                'Serve and enjoy.',
            ],
            'numberOfPersons' => 2,
            'timeToCook'      => 15,
            'timeToPrepare'   => 8,
            'ingredients'     => $outputIngredients,
        ];
    }
}
