<?php

return [

    /**
     *Specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
    | and organization on your OpenAI dashboard, at https://openai.com.
     */
    'api_key' => env('OPENAI_API_KEY'),
    'organization' => env('OPENAI_ORGANIZATION'),
    /** ID of the model to use. you can find a list of models at https://platform.openai.com/docs/models */
    'model' => 'text-davinci-003',
    /**
     * What sampling temperature to use, between 0 and 2.
     * Higher values like 0.8 will make the output more random,
     * while lower values like 0.2 will make it more focused and deterministic.
     *
     * it is generally recommended to alter this or top_p but not both.
     */
    'temperature' => 0.3,
    /** An alternative to sampling with temperature, called nucleus sampling,
     * where the model considers the results of the tokens with top_p probability mass.
     * So 0.1 means only the tokens comprising the top 10% probability mass are considered.
     *
     * it is generally recommended to alter this or temperature but not both.
     */
    'top_p' => 1,
    /**
     *The maximum number of tokens to generate in the completion.
     * The token count of your prompt plus max_tokens cannot exceed the model's context length.
     * Most models have a context length of 2048 tokens (except for the newest models, which support 4096).
     */
    'max_tokens' => 400,
    /** If set, tokens will be sent as data-only server-sent events as they become available,
     *  with the stream terminated by a data: [DONE] message.
     */
    'stream' => false,
    /** Echo back the prompt in addition to the completion */
    'echo' => false,
    /** How many completions to generate for each prompt. */
    'n' => 1,
    /** Up to 4 sequences where the API will stop generating further tokens.
     * The returned text will not contain the stop sequence.
     */
    'stop' => null,
    /** This is the chatgpt model to use when using the chat completion */
    'chat_model' => 'gpt-3.5-turbo',
    /** The role of the ai process this message. it could be system, assistant or whatever you choose. */
    'ai_role' => 'assistant',
    /** The role of the author of this message. it could be user or whatever you choose. */
    'user_role' => 'user',
];
