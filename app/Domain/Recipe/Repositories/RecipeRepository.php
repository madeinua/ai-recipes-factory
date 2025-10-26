<?php

namespace App\Domain\Recipe\Repositories;

use App\Domain\Recipe\Entities\Recipe;

interface RecipeRepository
{
    public function findById(string $id): ?Recipe;

    public function save(Recipe $recipe): string;
}
