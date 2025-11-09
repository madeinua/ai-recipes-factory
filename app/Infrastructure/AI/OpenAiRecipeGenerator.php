<?php

namespace App\Infrastructure\AI;

use App\Domain\Recipe\Services\AiRecipeGenerator;
use App\Domain\Recipe\ValueObjects\Ingredient;
use OpenAI\Laravel\Facades\OpenAI;

final class OpenAiRecipeGenerator implements AiRecipeGenerator
{
    /**
     * @param array $ingredients
     * @return array
     */
    public function generate(array $ingredients): array
    {
        $ingredients = array_values(array_filter(array_map('trim', $ingredients), static fn($s) => $s !== ''));
        if ($ingredients === []) {
            throw new \InvalidArgumentException('Ingredients array must not be empty.');
        }

        $language = $this->resolveLanguage();
        $system = $this->systemPrompt($language);
        $user = $this->userPrompt($ingredients);
        $model = config('openai.recipe_model', 'gpt-4o-mini');
        $temperature = (float) config('openai.temperature', 0.5);
        $maxTokens = (int) config('openai.max_output_tokens', 1200);

        $params = [
            'model'           => $model,
            'response_format' => ['type' => 'json_object'],
            'messages'        => [
                [
                    'role'    => 'system',
                    'content' => $system,
                ],
                [
                    'role'    => 'user',
                    'content' => $user,
                ]
            ],
            'max_tokens'      => $maxTokens,
            'temperature'     => $temperature,
        ];

        $response = OpenAI::chat()->create($params);
        if (!isset($response['choices'][0]['message'])) {
            throw new \RuntimeException('OpenAI response missing message.');
        }

        $recipeData = json_decode($response['choices'][0]['message']['content'], true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($recipeData)) {
            throw new \RuntimeException('OpenAI returned invalid JSON: ' . json_last_error_msg());
        }

        if (!isset($recipeData['success']) || $recipeData['success'] !== true) {
            throw new \RuntimeException('OpenAI indicated recipe generation was not successful.');
        }

        if (!empty($recipeData['finishReason']) && $recipeData['finishReason'] !== 'stop') {
            throw new \RuntimeException('OpenAI did not finish the response properly.');
        }

        $title = (string) ($recipeData['title'] ?? '');
        $excerpt = (string) ($recipeData['excerpt'] ?? '');
        $instructions = array_values(array_filter($recipeData['instructions'] ?? [], 'is_string'));
        $prep = (int) ($recipeData['preparation_time'] ?? 0);
        $cook = (int) ($recipeData['cook_time'] ?? 0);

        $outputIngredients = [];
        foreach (($recipeData['ingredients'] ?? []) as $name => $qty) {
            [$value, $measure] = $this->parseMeasure((string) $qty);
            $outputIngredients[] = new Ingredient(lcfirst($name), $value, (string) $measure);
        }

        return [
            'title'           => $title,
            'excerpt'         => $excerpt,
            'instructions'    => $instructions,
            'numberOfPersons' => 2,
            'timeToCook'      => $cook,
            'timeToPrepare'   => $prep,
            'ingredients'     => $outputIngredients,
        ];
    }

    /**
     * @return string
     */
    private function resolveLanguage(): string
    {
        $locale = app()->getLocale();

        return match (true) {
            str_starts_with($locale, 'de') => 'German (formal)',
            str_starts_with($locale, 'en') => 'English (British)',
            default => 'German (formal)',
        };
    }

    /**
     * @param string $language
     * @return string
     */
    private function systemPrompt(string $language): string
    {
        return <<<END
You are a helpful assistant. You are a chef with extensive knowledge of various cuisines.
Your task is to generate a recipe based on ingredients from "Input Ingredients:" and return "Output recipe:" as a JSON object.

The provided ingredients are separated by commas or spaces.
Number of Ingredients: Use between 2 and 20 ingredients.
The ingredients are provided in German and English languages.
Non-edible Ingredients: Ignore inedible ingredients (e.g., "Kerzen").
Typos: If an ingredient has a typo, map it to the closest valid ingredient and proceed.

The recipe must be written in this language: {$language}.
If an ingredient is too broad (e.g., "Fleisch"), you may pick a specific type that fits best.
Prefer using all provided ingredients; you may drop up to 33% if it improves the recipe. You may add minor extras but keep the provided ones as the main focus.
Aim for a healthy recipe.
Measures must be for two people.

The JSON must contain ONLY: "success", "title", "ingredients", "excerpt", "instructions", "preparation_time", "cook_time".
- "success": boolean.
- "title": max 15 words.
- "ingredients": object { name -> measure string }, listed roughly by predominance; example values: "250 g", "1 EL", "2 Zehen", "300 ml", "" if seasoning. The measure string must not be longer than 45 characters.
- "excerpt": 15–50 words.
- "instructions": array of step strings (≤20).
- "preparation_time": integer minutes.
- "cook_time": integer minutes.

On error, return {"success": false, "error": "...reason..."} with at least 2 sentences.

Output must be valid JSON, no trailing commas. END;
END;
    }

    /**
     * @param array $ingredients
     * @return string
     */
    private function userPrompt(array $ingredients): string
    {
        $searchQuery = implode(', ', $ingredients);

        return <<<END
Input Ingredients: Pasta, Brokkoli, Walnüsse
Output recipe: {
  "success": true,
  "title": "Cremige Pasta mit Brokkoli und Walnüssen",
  "ingredients": {
    "Pasta": "250 g",
    "Brokkoli": "400 g",
    "Walnüsse": "40 g",
    "Parmesan, gerieben": "40 g",
    "Olivenöl": "1 EL",
    "Zehe Knoblauch": "1",
    "kleine Zwiebel oder Schalotte": "1",
    "Schmand": "2 EL",
    "Gemüsebrühe": "300 ml",
    "Salz und Pfeffer": ""
  },
  "excerpt": "Pasta schmeckt doch fast allen ... vegetarische Soßen-Variation.",
  "instructions": [
    "Zwiebel und Knoblauch schälen ...",
    "Brokkoli waschen ...",
    "In einer Pfanne das Olivenöl erhitzen ...",
    "Währenddessen die Pasta ...",
    "Wenn der Brokkoli gar ist ...",
    "Nun die pürierte Sauce ...",
    "Die fertigen Nudeln ...",
    "Guten Appetit!"
  ],
  "preparation_time": 15,
  "cook_time": 20
}

Input Ingredients: Bärlauch, Parmesan
Output recipe: {
  "success": true,
  "title": "Leckeres und schnelles Bärlauchpesto",
  "ingredients": {
    "Bärlauch": "80 g",
    "Olivenöl": "100 ml",
    "Parmesan, frisch gerieben": "50 g",
    "Pinienkerne": "50 g",
    "Salz und Pfeffer": ""
  },
  "excerpt": "Jetzt im Frühling ist es wieder so weit ...",
  "instructions": [
    "Die Pinienkerne in einer Pfanne anrösten ...",
    "Bärlauch gründlich waschen ...",
    "Geriebenen Parmesan, Olivenöl ...",
    "Schmeckt hervorragend zu Pasta ...",
    "Servieren und genießen!"
  ],
  "preparation_time": 20,
  "cook_time": 5
}

Input Ingredients: {$searchQuery}
Output recipe:
END;
    }

    /**
     * Parse a measure string like "250 g", "1 EL", "2 Zehen", "300 ml", or "".
     * Returns [value(float), measure(string)]
     */
    private function parseMeasure(string $q): array
    {
        $q = trim($q);
        if ($q === '') {
            return [0.0, ''];
        }

        // Normalize comma decimals
        $q = str_replace(',', '.', $q);

        // Common patterns: "250 g", "1 EL", "2 Zehen", "1 Stk", "0.5 TL"
        if (preg_match('/^\s*(\d+(?:\.\d+)?)\s*([^\d]+)?\s*$/u', $q, $m)) {
            $value = isset($m[1]) ? (float) $m[1] : 0.0;
            $measure = isset($m[2]) ? trim($m[2]) : '';
            $measure = preg_replace('/[^ \p{L}µ.%]/u', '', $measure) ?? '';
            $measure = preg_replace('/\s+/', ' ', $measure);
            return [$value, $measure];
        }

        // Fallback: non-numeric descriptions like "Prise", "nach Geschmack"
        return [0.0, $q];
    }
}
