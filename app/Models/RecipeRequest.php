<?php

namespace App\Models;

use App\Domain\Recipe\Enums\RecipeRequestStatus;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class RecipeRequest extends Model
{
    use HasUuids;

    protected $table = 'recipe_requests';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'ingredients_csv',
        'ingredients_hash',
        'status',
        'recipe_id',
        'error_message',
        'webhook_url',
    ];

    protected $casts = [
        'recipe_id' => 'string',
        'status'    => RecipeRequestStatus::class,
    ];
}
