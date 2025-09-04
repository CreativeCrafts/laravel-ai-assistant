<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatMessageDataContract;
use Illuminate\Support\Facades\Cache;

final class ChatMessageData implements ChatMessageDataContract
{
    protected array $conversations = [];

    public function __construct(
        protected readonly string $prompt
    ) {
    }

    /**
     * Retrieves or builds the conversation messages array for the AI assistant.
     *
     * If a cached conversation exists, it appends the current user prompt to the existing
     * conversation. Otherwise, it initialises a new conversation with system instructions,
     * example exchanges, and the current user prompt.
     *
     * @return array An array of conversation messages, where each message contains 'role' and 'content' keys
     */
    public function messages(): array
    {
        if (Cache::has('userMessage')) {
            $this->conversations[] = Cache::get('userMessage');
            $this->conversations[] = [
                'role' => config('ai-assistant.user_role') ?? 'user',
                'content' => $this->prompt,
            ];
            $this->cacheConversation($this->conversations);

            return $this->conversations;
        }

        $this->conversations[] = [
            'role' => 'system',
            'content' => 'You are ChatGPT, a large language model trained by OpenAI. Answer as concisely as possible.',
        ];

        $this->conversations[] = [
            'role' => config('ai-assistant.user_role') ?? 'user',
            'content' => 'Who won the world series in 2020?',
        ];

        $this->conversations[] = [
            'role' => config('ai-assistant.ai_role') ?? 'assistant',
            'content' => 'The Los Angeles Dodgers won the World Series in 2020.',
        ];

        $this->conversations[] = [
            'role' => config('ai-assistant.user_role') ?? 'user',
            'content' => $this->prompt,
        ];

        $this->cacheConversation($this->conversations);

        return $this->conversations;
    }

    public function setAssistantInstructions(string $instructions): array
    {
        $this->conversations[] = [
            'role' => config('ai-assistant.ai_role', 'assistant'),
            'content' => $instructions,
        ];

        $this->conversations[] = [
            'role' => config('ai-assistant.user_role', 'user'),
            'content' => $instructions . "\n\nText to edit: " . $this->prompt,
        ];
        return $this->conversations;
    }

    public function cacheConversation(array $conversation): void
    {
        Cache::put('userMessage', $conversation, 120);
    }
}
