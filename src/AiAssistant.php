<?php

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Tasks\ChatTextCompletion;
use CreativeCrafts\LaravelAiAssistant\Tasks\TextCompletion;
use CreativeCrafts\LaravelAiAssistant\Tasks\TextEditCompletion;
use OpenAI\Client;

class AiAssistant
{
    protected Client $client;

    protected array $textGeneratorConfig = [];

    protected array $chatTextGenerator = [];

    protected array $editTextGenerator = [];

    public function __construct(protected string $prompt)
    {
        $this->client = AppConfig::openAiClient();
        $this->textGeneratorConfig = AppConfig::textGeneratorConfig();
        $this->chatTextGenerator = AppConfig::chatTextGeneratorConfig();
        $this->editTextGenerator = AppConfig::editTextGeneratorConfig();
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
        $this->chatTextGenerator['messages'] = ChatTextCompletion::messages($this->prompt);

        return (new ChatTextCompletion())($this->chatTextGenerator);
    }

    public function spellingAndGrammarCorrection(): string
    {
        $this->editTextGenerator['input'] = $this->prompt;
        $this->editTextGenerator['instruction'] = 'Fix the spelling and grammar errors in the following text.';

        return (new TextEditCompletion())($this->editTextGenerator);
    }

    public function improveWriting(): string
    {
        $this->editTextGenerator['input'] = $this->prompt;
        $this->editTextGenerator['instruction'] = 'Edit the following text to make it more readable.';

        return (new TextEditCompletion())($this->editTextGenerator);
    }
}
