<?php

namespace App\Http\Controllers;

use App\Domain\Recipe\Repositories\RecipeRepository;
use Illuminate\Http\JsonResponse;

final class RecipeController extends Controller
{
    /**
     * @param string $id
     * @param RecipeRepository $repo
     * @return JsonResponse
     */
    public function show(string $id, RecipeRepository $repo): JsonResponse
    {
        $recipe = $repo->findById($id);
        if (!$recipe) {
            return response()->json(['message' => 'Not found'], 404);
        }

        return response()->json($recipe->toArray());
    }
}
