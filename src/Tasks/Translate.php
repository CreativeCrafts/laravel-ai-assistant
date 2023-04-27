<?php

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use OpenAI;

class Translate
{
    public function __construct(protected string $translationString)
    {
    }

    public static function text(string $translationString): self
    {
        return new self($translationString);
    }

    public function toLanguageName(string $language): string
    {
        $apiKey = config('ai-assistant.api_key');
        $organisation = config('ai-assistant.organization');

        $prompt = 'translate this'.". $this->translationString . ".'to'.$language;

        $attributes = [
            'model' => config('ai-assistant.model'),
            'prompt' => $prompt,
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'echo' => config('ai-assistant.echo'),
        ];

        $client = OpenAI::client($apiKey, $organisation);

        return trim($client->completions()->create($attributes)->choices[0]->text);
    }
}
