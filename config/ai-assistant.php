<?php

return [

    /**
     *Here you may specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
     */
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    'model' => 'text-davinci-003',
    'temperature' => 0.3,
    'max_tokens' => 400,
    'stream' => false,
    'echo' => false,
];
