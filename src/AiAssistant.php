<?php

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contract\AiAssistantContract;
use CreativeCrafts\LaravelAiAssistant\Tasks\AudioResource;
use CreativeCrafts\LaravelAiAssistant\Tasks\ChatTextCompletion;
use CreativeCrafts\LaravelAiAssistant\Tasks\TextCompletion;
use CreativeCrafts\LaravelAiAssistant\Tasks\TextEditCompletion;
use OpenAI\Client;

class AiAssistant implements AiAssistantContract
{
    protected Client $client;

    protected array $textGeneratorConfig = [];

    protected array $chatTextGeneratorConfig = [];

    protected array $editTextGeneratorConfig = [];

    protected array $audioToTextGeneratorConfig = [];

    public function __construct(protected string $prompt)
    {
        $this->client = AppConfig::openAiClient();
        $this->textGeneratorConfig = AppConfig::textGeneratorConfig();
        $this->chatTextGeneratorConfig = AppConfig::chatTextGeneratorConfig();
        $this->editTextGeneratorConfig = AppConfig::editTextGeneratorConfig();
        $this->audioToTextGeneratorConfig = AppConfig::audioToTextGeneratorConfig();
    }

    public static function acceptPrompt(string $prompt): self
    {
        return new self($prompt);
    }

    public function draft(): string
    {
        $this->textGeneratorConfig['prompt'] = $this->prompt;

        return (new TextCompletion())($this->textGeneratorConfig);
    }

    public function translateTo(string $language): string
    {
        $this->textGeneratorConfig['prompt'] = 'translate this'.". $this->prompt . ".'to'.$language;

        return (new TextCompletion())($this->textGeneratorConfig);
    }

    public function andRespond(): array
    {
        $this->chatTextGeneratorConfig['messages'] = ChatTextCompletion::messages($this->prompt);
        $this->chatTextGeneratorConfig['functions'] = ChatTextCompletion::customFunction();

        return (new ChatTextCompletion())($this->chatTextGeneratorConfig);
    }

    public function spellingAndGrammarCorrection(): string
    {
        $this->editTextGeneratorConfig['input'] = $this->prompt;
        $this->editTextGeneratorConfig['instruction'] = 'Fix the spelling and grammar errors in the following text.';

        return (new TextEditCompletion())($this->editTextGeneratorConfig);
    }

    public function improveWriting(): string
    {
        $this->editTextGeneratorConfig['input'] = $this->prompt;
        $this->editTextGeneratorConfig['instruction'] = 'Edit the following text to make it more readable.';

        return (new TextEditCompletion())($this->editTextGeneratorConfig);
    }

    public function transcribeTo(string $language, ?string $optionalText = ''): string
    {
        $this->audioToTextGeneratorConfig['file'] = fopen($this->prompt, 'rb');
        if ($optionalText !== '') {
            $this->audioToTextGeneratorConfig['prompt'] = $optionalText;
        }
        $this->audioToTextGeneratorConfig['language'] = $language;

        return (new AudioResource())->transcribeTo($this->audioToTextGeneratorConfig);
    }

    public function translateAudioTo(): string
    {
        $this->audioToTextGeneratorConfig['file'] = fopen($this->prompt, 'rb');

        return (new AudioResource())->translateTo($this->audioToTextGeneratorConfig);
    }
}
