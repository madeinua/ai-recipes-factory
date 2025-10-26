<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class Recipe extends Model
{
    use HasUuids;

    protected $table = 'recipes';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'title',
        'excerpt',
        'instructions',
        'number_of_persons',
        'time_to_cook',
        'time_to_prepare',
        'ingredients',
    ];

    protected $casts = [
        'instructions'      => 'array',
        'ingredients'       => 'array',
        'number_of_persons' => 'integer',
        'time_to_cook'      => 'integer',
        'time_to_prepare'   => 'integer',
    ];
}
