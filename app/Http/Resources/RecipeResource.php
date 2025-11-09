<?php

namespace App\Http\Resources;

use App\Domain\Recipe\Entities\Recipe;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Recipe
 */
class RecipeResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param Request $request
     * @return array
     */
    public function toArray(Request $request): array
    {
        /** @var Recipe $recipe */
        $recipe = $this->resource;

        return [
            'id'              => $recipe->id,
            'title'           => $recipe->title,
            'excerpt'         => $recipe->excerpt,
            'instructions'    => $recipe->instructions,
            'numberOfPersons' => $recipe->numberOfPersons,
            'timeToCook'      => $recipe->timeToCook,
            'timeToPrepare'   => $recipe->timeToPrepare,
            'ingredients'     => array_map(
                static fn($ingredient) => $ingredient->toArray(),
                $recipe->ingredients
            ),
        ];
    }
}
