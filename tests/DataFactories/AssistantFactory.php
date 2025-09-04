<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

/**
 * Factory for generating test data for AI Assistant operations.
 *
 * This factory provides methods to create realistic test data for
 * assistants, threads, messages, and API responses.
 */
class AssistantFactory
{
    /**
     * Generate a realistic assistant configuration.
     *
     * @param array $overrides Override default values
     * @return array Assistant configuration
     */
    public static function assistantConfig(array $overrides = []): array
    {
        return array_merge([
            'model' => 'gpt-4',
            'name' => 'Test Assistant',
            'instructions' => 'You are a helpful assistant that provides accurate and concise responses.',
            'tools' => [
                ['type' => 'code_interpreter'],
                ['type' => 'file_search'],
            ],
            'temperature' => 0.7,
            'top_p' => 1.0,
            'metadata' => [
                'created_by' => 'test',
                'version' => '1.0',
            ],
        ], $overrides);
    }

    /**
     * Generate a valid assistant ID.
     *
     * @return string Assistant ID in proper format
     */
    public static function assistantId(): string
    {
        return 'asst_' . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    /**
     * Generate a valid thread ID.
     *
     * @return string Thread ID in proper format
     */
    public static function threadId(): string
    {
        return 'thread_' . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    /**
     * Generate a valid run ID.
     *
     * @return string Run ID in proper format
     */
    public static function runId(): string
    {
        return 'run_' . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    /**
     * Generate a valid message ID.
     *
     * @return string Message ID in proper format
     */
    public static function messageId(): string
    {
        return 'msg_' . str_pad(bin2hex(random_bytes(12)), 24, '0');
    }

    /**
     * Generate thread creation parameters.
     *
     * @param array $overrides Override default values
     * @return array Thread parameters
     */
    public static function threadParams(array $overrides = []): array
    {
        return array_merge([
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Hello, I need help with a programming question.',
                ],
            ],
            'metadata' => [
                'created_by' => 'test',
                'purpose' => 'testing',
            ],
        ], $overrides);
    }

    /**
     * Generate message data for thread operations.
     *
     * @param string $role Message role (user, assistant, system)
     * @param string $content Message content
     * @param array $overrides Override default values
     * @return array Message data
     */
    public static function messageData(string $role = 'user', string $content = 'Test message', array $overrides = []): array
    {
        return array_merge([
            'role' => $role,
            'content' => $content,
            'metadata' => [
                'created_at' => time(),
                'source' => 'test',
            ],
        ], $overrides);
    }

    /**
     * Generate run parameters for thread execution.
     *
     * @param string $assistantId Assistant ID to use
     * @param array $overrides Override default values
     * @return array Run parameters
     */
    public static function runParams(string $assistantId, array $overrides = []): array
    {
        return array_merge([
            'assistant_id' => $assistantId,
            'instructions' => 'Please provide helpful and accurate responses.',
            'additional_instructions' => 'Be concise and clear.',
            'tools' => [
                ['type' => 'code_interpreter'],
            ],
            'metadata' => [
                'test_run' => true,
                'created_by' => 'test',
            ],
        ], $overrides);
    }

    /**
     * Generate text completion payload.
     *
     * @param array $overrides Override default values
     * @return array Completion payload
     */
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

    /**
     * Generate chat completion payload.
     *
     * @param array $overrides Override default values
     * @return array Chat completion payload
     */
    public static function chatCompletionPayload(array $overrides = []): array
    {
        return array_merge([
            'model' => 'gpt-3.5-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'You are a helpful assistant.',
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
     * Generate audio transcription payload.
     *
     * @param mixed $file File resource or mock
     * @param array $overrides Override default values
     * @return array Audio payload
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
     * Generate mock API response for assistant creation.
     *
     * @param string|null $assistantId Custom assistant ID
     * @param array $overrides Override default values
     * @return array Mock assistant response
     */
    public static function assistantResponse(?string $assistantId = null, array $overrides = []): array
    {
        return array_merge([
            'id' => $assistantId ?? self::assistantId(),
            'object' => 'assistant',
            'created_at' => time(),
            'name' => 'Test Assistant',
            'description' => null,
            'model' => 'gpt-4',
            'instructions' => 'You are a helpful assistant.',
            'tools' => [
                ['type' => 'code_interpreter'],
            ],
            'file_ids' => [],
            'metadata' => [],
        ], $overrides);
    }

    /**
     * Generate mock API response for thread creation.
     *
     * @param string|null $threadId Custom thread ID
     * @param array $overrides Override default values
     * @return array Mock thread response
     */
    public static function threadResponse(?string $threadId = null, array $overrides = []): array
    {
        return array_merge([
            'id' => $threadId ?? self::threadId(),
            'object' => 'thread',
            'created_at' => time(),
            'metadata' => [],
        ], $overrides);
    }

    /**
     * Generate mock API response for message listing.
     *
     * @param array $messages Array of messages
     * @param array $overrides Override default values
     * @return array Mock messages list response
     */
    public static function messagesListResponse(array $messages = [], array $overrides = []): array
    {
        if (empty($messages)) {
            $messages = [
                [
                    'id' => self::messageId(),
                    'object' => 'thread.message',
                    'created_at' => time(),
                    'thread_id' => self::threadId(),
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => [
                                'value' => 'Hello! How can I help you today?',
                                'annotations' => [],
                            ],
                        ],
                    ],
                    'file_ids' => [],
                    'assistant_id' => self::assistantId(),
                    'run_id' => self::runId(),
                    'metadata' => [],
                ],
            ];
        }

        return array_merge([
            'object' => 'list',
            'data' => $messages,
            'first_id' => $messages[0]['id'] ?? null,
            'last_id' => $messages[count($messages) - 1]['id'] ?? null,
            'has_more' => false,
        ], $overrides);
    }

    /**
     * Generate mock completion response.
     *
     * @param string $text Generated text
     * @param array $overrides Override default values
     * @return array Mock completion response
     */
    public static function completionResponse(string $text = 'Generated text response', array $overrides = []): array
    {
        return array_merge([
            'id' => 'cmpl-' . bin2hex(random_bytes(12)),
            'object' => 'text_completion',
            'created' => time(),
            'model' => 'gpt-3.5-turbo',
            'choices' => [
                [
                    'text' => $text,
                    'index' => 0,
                    'logprobs' => null,
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
        ], $overrides);
    }

    /**
     * Generate mock chat completion response.
     *
     * @param string $content Response content
     * @param array $overrides Override default values
     * @return array Mock chat completion response
     */
    public static function chatCompletionResponse(string $content = 'Hello! How can I help you?', array $overrides = []): array
    {
        return array_merge([
            'id' => 'chatcmpl-' . bin2hex(random_bytes(12)),
            'object' => 'chat.completion',
            'created' => time(),
            'model' => 'gpt-3.5-turbo',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => $content,
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 15,
                'completion_tokens' => 25,
                'total_tokens' => 40,
            ],
        ], $overrides);
    }

    /**
     * Generate mock audio transcription response.
     *
     * @param string $text Transcribed text
     * @param array $overrides Override default values
     * @return array Mock transcription response
     */
    public static function transcriptionResponse(string $text = 'This is a transcribed audio file.', array $overrides = []): array
    {
        return array_merge([
            'text' => $text,
        ], $overrides);
    }

    /**
     * Generate a temporary test audio file resource.
     *
     * @param string $content Mock audio content
     * @return resource File resource
     */
    public static function createTestAudioFile(string $content = 'test audio content')
    {
        return fopen('data://text/plain;base64,' . base64_encode($content), 'r');
    }

    /**
     * Generate realistic test prompts for various scenarios.
     *
     * @param string $type Type of prompt (coding, writing, analysis, etc.)
     * @return string Test prompt
     */
    public static function testPrompt(string $type = 'general'): string
    {
        $prompts = [
            'general' => 'Explain the concept of artificial intelligence in simple terms.',
            'coding' => 'Write a Python function to calculate the factorial of a number.',
            'writing' => 'Write a professional email requesting a meeting with a client.',
            'analysis' => 'Analyze the pros and cons of remote work.',
            'creative' => 'Write a short poem about the changing seasons.',
            'technical' => 'Explain how blockchain technology works.',
            'translation' => 'Hello, how are you today? I hope you are doing well.',
        ];

        return $prompts[$type] ?? $prompts['general'];
    }

    /**
     * Generate error scenarios for testing.
     *
     * @param string $errorType Type of error to simulate
     * @return array Error scenario data
     */
    public static function errorScenario(string $errorType): array
    {
        $scenarios = [
            'invalid_api_key' => [
                'error' => [
                    'message' => 'Invalid API key provided.',
                    'type' => 'invalid_request_error',
                    'code' => 'invalid_api_key',
                ],
            ],
            'rate_limit' => [
                'error' => [
                    'message' => 'Rate limit exceeded.',
                    'type' => 'rate_limit_error',
                    'code' => 'rate_limit_exceeded',
                ],
            ],
            'insufficient_quota' => [
                'error' => [
                    'message' => 'You exceeded your current quota.',
                    'type' => 'insufficient_quota',
                    'code' => 'insufficient_quota',
                ],
            ],
            'model_not_found' => [
                'error' => [
                    'message' => 'The model does not exist.',
                    'type' => 'invalid_request_error',
                    'code' => 'model_not_found',
                ],
            ],
        ];

        return $scenarios[$errorType] ?? $scenarios['invalid_api_key'];
    }
}
