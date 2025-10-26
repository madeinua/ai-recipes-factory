<?php

namespace App\Http\Requests\Recipe;

use Illuminate\Foundation\Http\FormRequest;

class GenerateRecipeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ingredients' => ['required', 'string', 'min:2', 'max:3000'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ];
    }
}
