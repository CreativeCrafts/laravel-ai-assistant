<?php

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use Illuminate\Support\Facades\Cache;
use OpenAI;

class AiAssistant
{
    public function __construct(protected string $conversationString)
    {
    }

    public static function acceptQuestion(string $conversationString): self
    {
        return new self($conversationString);
    }

    public function andRespondWith(): array
    {
        $apiKey = config('ai-assistant.api_key');
        $organisation = config('ai-assistant.organization');

        $attributes = [
            'model' => config('ai-assistant.chat_model'),
            'messages' => $this->messages(),
            'max_tokens' => config('ai-assistant.max_tokens'),
            'temperature' => config('ai-assistant.temperature'),
            'stream' => config('ai-assistant.stream'),
            'n' => config('ai-assistant.n'),
        ];

        $client = OpenAI::client($apiKey, $organisation);

        $response = $client->chat()->create($attributes)->choices[0]->message->toArray();
        self::cacheChatConversation($response);

        return $response;
    }

    private function messages(): array
    {
        if (Cache::has('userMessage')) {
            $userMessage = Cache::get('userMessage');
            $userMessage[] = [
                'role' => config('ai-assistant.user_role') ?? 'user',
                'content' => $this->conversationString,
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
            'content' => $this->conversationString,
        ];

        self::cacheChatConversation($introMessage);
        return $introMessage;
    }

    protected static function cacheChatConversation(array $conversation): void
    {
        Cache::put('userMessage', $conversation, 60);
    }
}
