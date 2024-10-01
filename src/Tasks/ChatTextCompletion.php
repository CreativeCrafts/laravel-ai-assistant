<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Contract\ChatTextCompletionContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use Illuminate\Support\Facades\Cache;
use OpenAI\Client;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class ChatTextCompletion implements ChatTextCompletionContract
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? AppConfig::openAiClient();
    }

    public function __invoke(array $payload): array
    {
        if (isset($payload['stream']) && $payload['stream']) {
            $response = $this->streamedChat($payload);
        } else {
            $response = $this->chatTextCompletion($payload);
        }

        self::cacheChatConversation($response);

        return $response;
    }

    public static function messages(string $prompt): array
    {
        if (Cache::has('userMessage')) {
            $userMessage[] = Cache::get('userMessage');
            $userMessage[] = [
                'role' => config('ai-assistant.user_role') ?? 'user',
                'content' => $prompt,
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
            'content' => $prompt,
        ];

        self::cacheChatConversation($introMessage);

        return $introMessage;
    }

    public static function cacheChatConversation(array $conversation): void
    {
        Cache::put('userMessage', $conversation, 120);
    }

    public function chatTextCompletion(array $payload): array
    {
        try {
            $response = $this->client->chat()->create($payload)->choices[0]->message->toArray();
            self::cacheChatConversation($response);

            return $response;
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    public function streamedChat(array $payload): array
    {
        try {
            $streamResponses = $this->client->chat()->createStreamed($payload);

            foreach ($streamResponses as $response) {
                /** @var Response $response */
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
}
