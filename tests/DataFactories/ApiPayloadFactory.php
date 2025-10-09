<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

final class ApiPayloadFactory
{
    public static function completionPayload(array $overrides = []): array
    {
        return array_merge([
            'model' => 'gpt-3.5-turbo',
            'prompt' => 'Write a short story about artificial intelligence.',
            'max_tokens' => 150,
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stop' => null,
        ], $overrides);
    }

    public static function chatCompletionPayload(array $overrides = []): array
    {
        return array_merge([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are helpful and concise.',
                ],
                [
                    'role' => 'user',
                    'content' => 'Hello, how are you?',
                ],
            ],
            'max_tokens' => 150,
            'temperature' => 0.7,
            'top_p' => 1.0,
            'frequency_penalty' => 0,
            'presence_penalty' => 0,
            'stream' => false,
        ], $overrides);
    }

    /**
     * @param resource $file
     */
    public static function audioPayload($file, array $overrides = []): array
    {
        return array_merge([
            'file' => $file,
            'model' => 'whisper-1',
            'language' => 'en',
            'response_format' => 'text',
            'temperature' => 0,
        ], $overrides);
    }

    /**
     * Create a small in-memory file resource for audio tests.
     * @return resource
     */
    public static function createTestAudioFile()
    {
        return fopen('data://text/plain;base64,' . base64_encode('audio'), 'r');
    }
}
