# A handy package to access and interact with Openai end point

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

This package will provide a simple way to access and interact with Openai end point. it provides features such as translation, summarization, question answering, text generation, chat, transcribing and translating audio file to text and more.
The package abstract the complexity of the Openai API and provides a simple interface to interact with it. 


## Installation

You can install the package via composer:

```bash
composer require creativecrafts/laravel-ai-assistant
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="ai-assistant-config"
```

This is the contents of the published config file:

```php
return [

    /**
     *Specify your OpenAI API Key and organization. This will be
    | used to authenticate with the OpenAI API - you can find your API key
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
    'max_tokens' => 400,

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

    /**
     * The format of the transcript output, in one of these options: json, text, srt, verbose_json, or vtt.
     */
    'response_format' => 'verbose_json',
];
```

## Usage

```php
//translate text to a specific language
use CreativeCrafts\LaravelAiAssistant\AiAssistant;use CreativeCrafts\LaravelAiAssistant\AiAssistant;
$translatedText = AiAssistant::acceptPrompt()->translateTo('How are you?')->toLanguageName('swedish');

//response will be a string
//Hur mår du?

// Chat with the AI
$chat = AiAssistant::acceptPrompt('Who is Jane Austen?')->andRespond();

//response will be an array. The role is based on your configuration
[
  "role" => "assistant"
  "content" => "Jane Austen was an English novelist known for her witty and insightful portrayals of English middle-class life in the late 18th and early 19th centuries."
]

// You can ask a follow up question
$chat = AiAssistant::acceptPrompt('did she win any award for her work?')->andRespond();

//response
[
  "role" => "assistant"
  "content" => "No, Jane Austen did not win any awards during her lifetime as literary awards did not exist in the way they do today. However, her novels have received numerous accolades and critical acclaim since their publication, and she is widely regarded as one of the greatest writers in English literature.
]

//brainstorming: generate ideas for a blog post for example
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;
$ideas = AiAssistant::acceptPrompt('Write a blog about AI?')->draft();

// response
"""
Artificial Intelligence (AI) is a rapidly growing field of technology that is revolutionizing the way we interact with the world around us. From self-driving cars to voice-activated home assistants, AI is making our lives easier and more efficient. But what exactly is AI, and how is it changing our lives?
AI is a branch of computer science that focuses on creating intelligent machines that can think and act like humans. AI systems are designed to learn from their environment and make decisions based on what they learn. This means that AI can be used to automate tasks, such as recognizing faces or driving cars, and can even be used to create new products and services.
AI is already being used in a variety of industries, from healthcare to finance. In healthcare, AI is being used to diagnose diseases and provide personalized treatments. In finance, AI is being used to detect fraud and improve customer service. AI is also being used in retail to create personalized shopping experiences and in manufacturing to automate tasks and increase efficiency.
AI is also being used to improve our lives in more subtle ways. For example, AI can be used to create virtual assistants that can help us with everyday tasks, such as scheduling appointments or ordering groceries. AI can also be used to create more efficient search engines and to improve the accuracy of online translations.
AI is an exciting and rapidly evolving field of technology that is changing the way we interact with the world around us. As AI continues to develop, it will open up new possibilities for how we live our lives and interact with each other.
"""

// Spell check and grammar correction
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;

$text = 'Artificial Intellagence (AI) is a rapidly growng field of technlogy that is revolutinizing the way we interact with the world arund us.';
$correctedText = AiAssistant::acceptPrompt($text)->spellingAndGrammarCorrection();

// response
"Artificial Intelligence (AI) is a rapidly growing field of technology that is revolutionizing the way we interact with the world around us."

// You can also improving the readability of your text by calling this method
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;

$text = 'Artificial Intelligence (AI) is a rapidly growing field of technology that is revolutionizing the way we interact with the world around us.';
$improvedText = AiAssistant::acceptPrompt($text)->improveWriting();

// Transcribe an audio file to text
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;

// The audio file to transcribe, must be in one of these formats: mp3, mp4, mpeg, mpga, m4a, wav, or webm.
$audioFilePath = 'path/to/audio/file.mp3';
$optionalText = 'An optional text to guide the model's style or continue a previous audio segment. The prompt should match the audio language.

// The language of the input audio. Supplying the input language in ISO-639-1 format will improve accuracy and latency.
$language = 'en';

$transcription = AiAssistant::acceptPrompt($audioFilePath)->transcribe($language, $optionalText);

// The response will be a text format of the audio file

// Translate an audio file to english text
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;

// The audio file to transcribe, must be in one of these formats: mp3, mp4, mpeg, mpga, m4a, wav, or webm.
$audioFilePath = 'path/to/audio/german.mp3';

$translatedText = AiAssistant::acceptPrompt($audioFilePath)->translateAudioTo();

// The response will be a text format of the audio file in english
```
## Important Update

This fork includes critical updates to address compatibility issues with the latest OpenAI API changes. The main modifications are:

1. Replaced the deprecated `/v1/edits` endpoint with the chat completion endpoint in the `TextEditCompletion` class.
2. Updated the configuration to use the chat model for text editing tasks.

### Changes in Detail

#### TextEditCompletion.php

The `TextEditCompletion` class has been modified to use the chat completion endpoint instead of the deprecated edits endpoint. This change ensures compatibility with the current OpenAI API.

#### Configuration

The `ai-assistant.php` configuration file now includes a new `chat_model` option.

### Cmpatibility

This fork is compatible with OpenAI API as of August 2024. It uses the gpt-3.5-turbo model by default, but you can specify gpt-4 or other available models in your configuration if you have access to them.

## Add to .env

```bash
OPENAI_API_KEY=
OPENAI_ORGANIZATION=
OPENAI_CHAT_MODEL=
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Godspower Oduose](https://github.com/rockblings)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
