<?php

namespace App\Infrastructure\Repositories;

use App\Domain\Recipe\Entities\Recipe as RecipeEntity;
use App\Domain\Recipe\ValueObjects\Ingredient;
use App\Models\Recipe as RecipeModel;

final class EloquentRecipeRepository implements \App\Domain\Recipe\Repositories\RecipeRepository
{
    /**
     * @param string $id
     * @return RecipeEntity|null
     */
    public function findById(string $id): ?RecipeEntity
    {
        $model = RecipeModel::query()->find($id);

        return $model ? $this->toEntity($model) : null;
    }

    /**
     * @param RecipeEntity $recipe
     * @return string
     */
    public function save(RecipeEntity $recipe): string
    {
        $model = RecipeModel::query()->find($recipe->id) ?? new RecipeModel();

        $model->id = $recipe->id;
        $model->title = $recipe->title;
        $model->excerpt = $recipe->excerpt;
        $model->instructions = $recipe->instructions;
        $model->number_of_persons = $recipe->numberOfPersons;
        $model->time_to_cook = $recipe->timeToCook;
        $model->time_to_prepare = $recipe->timeToPrepare;
        $model->ingredients = array_map(static fn(Ingredient $i) => $i->toArray(), $recipe->ingredients);

        $model->save();

        return (string) $model->id;
    }

    /**
     * @throws \Exception
     * @param RecipeModel $m
     * @return RecipeEntity
     */
    private function toEntity(RecipeModel $m): RecipeEntity
    {
        return RecipeEntity::create(
            id: $m->id,
            title: $m->title,
            excerpt: $m->excerpt,
            instructions: $m->instructions,
            numberOfPersons: $m->number_of_persons,
            timeToCook: $m->time_to_cook,
            timeToPrepare: $m->time_to_prepare,
            ingredients: array_map(static fn(array $row) => Ingredient::fromArray($row), $m->ingredients),
            createdAt: new \DateTimeImmutable($m->created_at?->toAtomString() ?? 'now'),
            updatedAt: new \DateTimeImmutable($m->updated_at?->toAtomString() ?? 'now'),
        );
    }
}
