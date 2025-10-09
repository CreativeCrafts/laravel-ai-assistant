<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

final readonly class ModelOptions
{
    public function __construct(
        public ?string $model = null,
        public ?int $maxTokens = null,
        public ?float $temperature = null,
        public ?bool $stream = null,
        public ?bool $echo = null,
        public ?int $n = null,
        public ?string $suffix = null,
        public ?float $topP = null,
        public ?float $presencePenalty = null,
        public ?float $frequencyPenalty = null,
        public ?int $bestOf = null,
        public string|array|null $stop = null,
        public ?string $responseFormat = null,
    ) {
    }

    public static function fromArray(array $options): self
    {
        return new self(
            model: isset($options['model']) && is_string($options['model']) ? $options['model'] : null,
            maxTokens: isset($options['max_tokens']) && is_numeric($options['max_tokens']) ? (int)$options['max_tokens'] : null,
            temperature: isset($options['temperature']) && is_numeric($options['temperature']) ? (float)$options['temperature'] : null,
            stream: isset($options['stream']) ? (bool)$options['stream'] : null,
            echo: isset($options['echo']) ? (bool)$options['echo'] : null,
            n: isset($options['n']) && is_numeric($options['n']) ? (int)$options['n'] : null,
            suffix: isset($options['suffix']) && is_string($options['suffix']) ? $options['suffix'] : null,
            topP: isset($options['top_p']) && is_numeric($options['top_p']) ? (float)$options['top_p'] : null,
            presencePenalty: isset($options['presence_penalty']) && is_numeric($options['presence_penalty']) ? (float)$options['presence_penalty'] : null,
            frequencyPenalty: isset($options['frequency_penalty']) && is_numeric($options['frequency_penalty']) ? (float)$options['frequency_penalty'] : null,
            bestOf: isset($options['best_of']) && is_numeric($options['best_of']) ? (int)$options['best_of'] : null,
            stop: $options['stop'] ?? null,
            responseFormat: isset($options['response_format']) && is_string($options['response_format']) ? $options['response_format'] : null,
        );
    }

    public static function fromConfig(): self
    {
        $temperature = config('ai-assistant.temperature');
        $topP = config('ai-assistant.top_p');
        $n = config('ai-assistant.n');
        $suffix = config('ai-assistant.suffix');
        $presencePenalty = config('ai-assistant.presence_penalty');
        $frequencyPenalty = config('ai-assistant.frequency_penalty');
        $bestOf = config('ai-assistant.best_of');
        $stop = config('ai-assistant.stop');
        $maxTokens = config('ai-assistant.max_completion_tokens');
        $responseFormat = config('ai-assistant.response_format');

        return new self(
            temperature: is_numeric($temperature) ? (float)$temperature : null,
            topP: is_numeric($topP) ? (float)$topP : null,
            stream: (bool)config('ai-assistant.stream'),
            echo: (bool)config('ai-assistant.echo'),
            n: is_numeric($n) ? (int)$n : null,
            suffix: is_string($suffix) ? $suffix : null,
            presencePenalty: is_numeric($presencePenalty) ? (float)$presencePenalty : null,
            frequencyPenalty: is_numeric($frequencyPenalty) ? (float)$frequencyPenalty : null,
            bestOf: is_numeric($bestOf) ? (int)$bestOf : null,
            stop: is_string($stop) || is_array($stop) ? $stop : null,
            maxTokens: is_numeric($maxTokens) ? (int)$maxTokens : null,
            responseFormat: is_string($responseFormat) ? $responseFormat : null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'temperature' => $this->temperature,
            'stream' => $this->stream,
            'echo' => $this->echo,
            'n' => $this->n,
            'suffix' => $this->suffix,
            'top_p' => $this->topP,
            'presence_penalty' => $this->presencePenalty,
            'frequency_penalty' => $this->frequencyPenalty,
            'best_of' => $this->bestOf,
            'stop' => $this->stop,
            'response_format' => $this->responseFormat,
        ], fn ($value) => $value !== null);
    }
}
