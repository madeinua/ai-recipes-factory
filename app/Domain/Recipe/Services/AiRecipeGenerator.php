<?php

namespace App\Domain\Recipe\Services;

interface AiRecipeGenerator
{
    /**
     * @param string[] $ingredients
     * @return array
     */
    public function generate(array $ingredients): array;
}
