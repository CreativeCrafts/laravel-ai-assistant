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

    public function __construct(protected string $prompt)
    {
        $this->client = AppConfig::openAiClient();
    }

    public static function acceptPrompt(string $prompt): self
    {
        return new self($prompt);
    }

    public function brainstorm(): string
    {
        $attributes = [
            'model' => config('ai-assistant.model'),
            'prompt' => $this->prompt,
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'echo' => config('ai-assistant.echo'),
        ];

        try {
            return trim($this->client->completions()->create($attributes)->choices[0]->text);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    public function translateTo(string $language): string
    {
        $prompt = 'translate this'.". $this->prompt . ".'to'. $language;

        $attributes = [
            'model' => config('ai-assistant.model'),
            'prompt' => $prompt,
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'echo' => config('ai-assistant.echo'),
        ];

        try {
            return trim($this->client->completions()->create($attributes)->choices[0]->text);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    public function andRespond(): array
    {
        $attributes = [
            'model' => config('ai-assistant.chat_model'),
            'messages' => $this->messages(),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'n' => config('ai-assistant.n'),
        ];

        try {
            $response = $this->client->chat()->create($attributes)->choices[0]->message->toArray();
            self::cacheChatConversation($response);
            return $response;
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
