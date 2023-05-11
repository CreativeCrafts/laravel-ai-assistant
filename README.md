# A handy package to access and interact with Openai end point

[![Latest Version on Packagist](https://img.shields.io/packagist/v/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/creativecrafts/laravel-ai-assistant/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/creativecrafts/laravel-ai-assistant/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/creativecrafts/laravel-ai-assistant.svg?style=flat-square)](https://packagist.org/packages/creativecrafts/laravel-ai-assistant)

This package will provide a simple way to access and interact with Openai end point. it provides features such as translation, summarization, question answering, text generation, chat and more.

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
```

## Usage

```php
//translate text to a specific language
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;
$translatedText = AiAssistant::acceptPrompt()->translateTo('How are you?')->toLanguageName('swedish');

//response will be a string
//Hur mår du?

// Chat with the AI
use CreativeCrafts\LaravelAiAssistant\Tasks\AiAssistant;
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
$ideas = AiAssistant::acceptPrompt('How to make money online?')->brainstorm();

// response
[
  "role" => "assistant"
  "content" => """
    There are various ways to make money online, including:
    1. Freelancing: Offer your skills and services on freelance platforms like Upwork, Fiverr, or Freelancer.
    2. Online surveys: Participate in online surveys and get paid for your opinions on websites like Swagbucks, Survey Junkie, or Vindale Research.
    3. Affiliate marketing: Promote other people's products and earn a commission for each sale made through your unique affiliate link.
    4. Online tutoring: Teach students online through platforms like Chegg, TutorMe, or VIPKid.
    5. Selling products: Sell products online through marketplaces like Amazon, eBay, or Etsy.
    6. Blogging: Start a blog and monetize it through advertising, sponsored content, or affiliate marketing.
    7. Online courses: Create and sell online courses on platforms like Udemy, Teachable, or Skillshare.
    Remember, making money online requires effort, time, and dedication.
    """
]

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
