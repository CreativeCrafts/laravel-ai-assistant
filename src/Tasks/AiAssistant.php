<?php

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Cache;
use OpenAI\Client;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

class AiAssistant
{
    protected Client $client;

    protected array $textCompletionAttributes = [];

    protected array $chatCompletionAttributes = [];

    public function __construct(protected string $prompt)
    {
        $this->client = AppConfig::openAiClient();
        $this->textCompletionAttributes = [
            'model' => config('ai-assistant.model'),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'echo' => config('ai-assistant.echo'),
            'n' => config('ai-assistant.n'),
        ];
        $this->chatCompletionAttributes = [
            'model' => config('ai-assistant.chat_model'),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'n' => config('ai-assistant.n'),
        ];
    }

    public static function acceptPrompt(string $prompt): self
    {
        return new self($prompt);
    }

    public function draft(): string
    {
        $this->textCompletionAttributes['prompt'] = $this->prompt;

        if ($this->textCompletionAttributes['stream']) {
            return $this->streamedCompletion($this->textCompletionAttributes);
        }

        try {
            return trim($this->client->completions()->create($this->textCompletionAttributes)->choices[0]->text);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    public function translateTo(string $language): string
    {
        $this->textCompletionAttributes['prompt'] = 'translate this'.". $this->prompt . ".'to'.$language;

        if ($this->textCompletionAttributes['stream']) {
            return $this->streamedCompletion($this->textCompletionAttributes);
        }

        try {
            return trim($this->client->completions()->create($this->textCompletionAttributes)->choices[0]->text);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    public function andRespond(): array
    {
        $this->chatCompletionAttributes['messages'] = $this->messages();

        if ($this->chatCompletionAttributes['stream']) {
            return $this->streamedChat($this->chatCompletionAttributes);
        }

        try {
            $response = $this->client->chat()->create($this->chatCompletionAttributes)->choices[0]->message->toArray();
            self::cacheChatConversation($response);

            return $response;
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    private function streamedCompletion(array $attributes): string
    {
        try {
            $streamResponses = $this->client->completions()->createStreamed($attributes);

            foreach ($streamResponses as $response) {
                if (isset($response->choices[0]->text)) {
                    return $response->choices[0]->text;
                }
            }

            return '';
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    private function streamedChat(array $attributes): array
    {
        try {
            $streamResponses = $this->client->chat()->createStreamed($attributes);

            foreach ($streamResponses as $response) {
                if (isset($response->choices[0])) {
                    return $response->choices[0]->toArray();
                }
            }

            return [];
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    private function messages(): array
    {
        if (Cache::has('userMessage')) {
            $userMessage[] = Cache::get('userMessage');
            $userMessage[] = [
                'role' => config('ai-assistant.user_role') ?? 'user',
                'content' => $this->prompt,
            ];
            self::cacheChatConversation($userMessage);

            return $userMessage;
        }

        $introMessage[] = [
            'role' => 'system',
            'content' => 'You are ChatGPT, a large language model trained by OpenAI. Answer as concisely as possible.',
        ];

        $introMessage[] = [
            'role' => config('ai-assistant.user_role') ?? 'user',
            'content' => 'Who won the world series in 2020?',
        ];

        $introMessage[] = [
            'role' => config('ai-assistant.ai_role') ?? 'assistant',
            'content' => 'The Los Angeles Dodgers won the World Series in 2020.',
        ];

        $introMessage[] = [
            'role' => config('ai-assistant.user_role') ?? 'user',
            'content' => $this->prompt,
        ];

        self::cacheChatConversation($introMessage);

        return $introMessage;
    }

    protected static function cacheChatConversation(array $conversation): void
    {
        Cache::put('userMessage', $conversation, 120);
    }
}
