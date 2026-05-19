<?php

return [

    'enabled' => env('SMART_CHATBOT_ENABLED', true),

    'bot_name' => env('SMART_CHATBOT_NAME', 'Website Assistant'),

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://127.0.0.1:11434'),
        'model' => env('OLLAMA_MODEL', 'llama3.2:3b'),
        'timeout' => env('OLLAMA_TIMEOUT', 180),
    ],

    'route' => [
        'prefix' => env('SMART_CHATBOT_ROUTE_PREFIX', 'smart-chatbot'),
        'middleware' => ['web', 'throttle:30,1'],
    ],

    'memory' => [
        'enabled' => true,
        'history_limit' => 6,
    ],

    'context' => [
        'max_characters' => 12000,
        'max_results' => 5,
    ],

    'website' => [
        'name' => env('SMART_CHATBOT_WEBSITE_NAME', 'Website'),
        'assistant_name' => env('SMART_CHATBOT_NAME', 'Website Assistant'),
        'business_type' => env('SMART_CHATBOT_BUSINESS_TYPE', 'business'),
    ],

    'general_knowledge' => env('SMART_CHATBOT_GENERAL_KNOWLEDGE', ''),

    'allow_ai_without_database_context' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Sources
    |--------------------------------------------------------------------------
    | This is configured per website by running:
    | php artisan smart-chatbot:install
    */

    'sources' => [],

    'fallback_message' => 'Sorry, I could not find that information right now. Please contact our team for more details.',

];