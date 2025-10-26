<?php

return [
    'api_key'           => env('OPENAI_API_KEY'),
    'organization'      => env('OPENAI_ORGANIZATION'),
    'project'           => env('OPENAI_PROJECT'),
    'base_uri'          => env('OPENAI_BASE_URL'),
    'request_timeout'   => env('OPENAI_REQUEST_TIMEOUT', 30),
    'recipe_model'      => env('OPENAI_RECIPE_MODEL', 'gpt-4o-mini'),
    'temperature'       => env('OPENAI_TEMPERATURE', 0.5),
    'max_output_tokens' => env('OPENAI_MAX_OUTPUT_TOKENS', 1200),
];
