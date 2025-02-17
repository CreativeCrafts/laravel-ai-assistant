<?php

declare(strict_types=1);

return [
    /**
     *Specify your OpenAI API Key and organization. This will be
    | and organization on your OpenAI dashboard, at https://openai.com.
     */
    'api_key' => env('OPENAI_API_KEY', null),
    'organization' => env('OPENAI_ORGANIZATION', null),

    /** ID of the model to use. you can find a list of models at https://platform.openai.com/docs/models */
    'model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),

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
    'max_completion_tokens' => 400,

    /** If set, tokens will be sent as data-only server-sent events as they become available,
     *  with the stream terminated by a data: [DONE] message.
     */
    'stream' => false,

    /** Echo back the prompt in addition to the completion */
    'echo' => false,

    /**
     * How many completions to generate for each prompt. (optional)
     * Note: Because this parameter generates many completions, it can quickly consume your token quota.
     * Use carefully and ensure that you have reasonable settings for max_tokens and stop
     */
    'n' => 1,

    /** Up to 4 sequences where the API will stop generating further tokens.
     * The returned text will not contain the stop sequence. e.g. ["\n", "Human:", "AI:"]
     * (optional)
     */
    'stop' => null,

    /**
     * The suffix that comes after a completion of inserted text. it is a string (optional)
     */
    'suffix' => null,

    /**
     * Number between -2.0 and 2.0.
     * Positive values penalize new tokens based on whether they appear in the text so far,
     * increasing the model's likelihood to talk about new topics.
     */
    'presence_penalty' => 0,

    /**
     * Number between -2.0 and 2.0.
     * Positive values penalize new tokens based on their existing frequency in the text so far,
     * decreasing the model's likelihood to repeat the same line verbatim.
     */
    'frequency_penalty' => 0,

    /**
     * Generates best_of completions server-side and returns the "best" (the one with the highest log probability per token). Results cannot be streamed.
     * When used with n, best_of controls the number of candidate completions and n specifies how many to return.
     * best_of must be greater than n.
     * Note: Because this parameter generates many completions, it can quickly consume your token quota.
     * Use carefully and ensure that you have reasonable settings for max_tokens and stop.
     */
    'best_of' => 1,

    /** This is the chatgpt model to use when using the chat completion */
    'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-3.5-turbo'),

    /** The role of the ai process this message. it could be system, assistant or whatever you choose. */
    'ai_role' => 'assistant',

    /** The role of the author of this message. it could be user or whatever you choose. */
    'user_role' => 'user',

    /**
     * ID of the model to use. You can use the gpt-4o or gpt-3.5-turbo model with this endpoint.
     */
    'edit_model' => 'gpt-4o',

    /**
     * ID of the model to use. Only whisper-1 is currently available.
     */
    'audio_model' => 'whisper-1',
];
