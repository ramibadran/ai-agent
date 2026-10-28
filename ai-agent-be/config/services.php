<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Resend, Postmark, AWS, and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY'),
        'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
        'embedding_model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'embedding_dimensions' => (int) env('OPENAI_EMBEDDING_DIMENSIONS', 1536),
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-5.4-mini'),
        'chat_max_output_tokens' => (int) env('OPENAI_CHAT_MAX_OUTPUT_TOKENS', 800),
        'chat_input_cost_per_million' => (float) env('OPENAI_CHAT_INPUT_COST_PER_MILLION', 0.75),
        'chat_output_cost_per_million' => (float) env('OPENAI_CHAT_OUTPUT_COST_PER_MILLION', 4.50),
        'chat_history_message_limit' => (int) env('OPENAI_CHAT_HISTORY_MESSAGE_LIMIT', 10),
    ],

    'qdrant' => [
        'url' => env('QDRANT_URL', 'http://127.0.0.1:6333'),
        'api_key' => env('QDRANT_API_KEY'),
        'collection' => env('QDRANT_COLLECTION', 'knowledge_chunks'),
        'vector_size' => (int) env('QDRANT_VECTOR_SIZE', 1536),
        'chunk_size' => (int) env('QDRANT_CHUNK_SIZE', 800),
        'chunk_overlap' => (int) env('QDRANT_CHUNK_OVERLAP', 200),
        'retrieval_limit' => (int) env('QDRANT_RETRIEVAL_LIMIT', 5),
        'minimum_score' => (float) env('QDRANT_MINIMUM_SCORE', 0.30),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

];
