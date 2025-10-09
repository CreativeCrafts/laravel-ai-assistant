<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Enums\Modality;

final readonly class ModelConfig
{
    public function __construct(
        public Modality $modality,
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

    public static function fromArray(array $data): self
    {
        $modality = Modality::from($data['modality'] ?? 'text');

        return new self(
            modality: $modality,
            model: isset($data['model']) && is_string($data['model']) ? $data['model'] : null,
            maxTokens: isset($data['max_tokens']) && is_numeric($data['max_tokens']) ? (int)$data['max_tokens'] : null,
            temperature: isset($data['temperature']) && is_numeric($data['temperature']) ? (float)$data['temperature'] : null,
            stream: isset($data['stream']) ? (bool)$data['stream'] : null,
            echo: isset($data['echo']) ? (bool)$data['echo'] : null,
            n: isset($data['n']) && is_numeric($data['n']) ? (int)$data['n'] : null,
            suffix: isset($data['suffix']) && is_string($data['suffix']) ? $data['suffix'] : null,
            topP: isset($data['top_p']) && is_numeric($data['top_p']) ? (float)$data['top_p'] : null,
            presencePenalty: isset($data['presence_penalty']) && is_numeric($data['presence_penalty']) ? (float)$data['presence_penalty'] : null,
            frequencyPenalty: isset($data['frequency_penalty']) && is_numeric($data['frequency_penalty']) ? (float)$data['frequency_penalty'] : null,
            bestOf: isset($data['best_of']) && is_numeric($data['best_of']) ? (int)$data['best_of'] : null,
            stop: $data['stop'] ?? null,
            responseFormat: isset($data['response_format']) && is_string($data['response_format']) ? $data['response_format'] : null,
        );
    }

    public function toArray(): array
    {
        return match ($this->modality) {
            Modality::Text => [
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
            ],

            Modality::Chat => [
                'model' => $this->model,
                'max_tokens' => $this->maxTokens,
                'temperature' => $this->temperature,
                'stream' => $this->stream,
                'n' => $this->n,
                'top_p' => $this->topP,
                'presence_penalty' => $this->presencePenalty,
                'frequency_penalty' => $this->frequencyPenalty,
                'stop' => $this->stop,
            ],

            Modality::Edit => [
                'model' => $this->model,
                'temperature' => $this->temperature,
                'top_p' => $this->topP,
            ],

            Modality::AudioToText => [
                'model' => $this->model,
                'temperature' => $this->temperature,
                'response_format' => $this->responseFormat,
            ],

            Modality::Image => [
                'model' => $this->model,
                'n' => $this->n,
                'response_format' => $this->responseFormat,
            ],
        };
    }
}
