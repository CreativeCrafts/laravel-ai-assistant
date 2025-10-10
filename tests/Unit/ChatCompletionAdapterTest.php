<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ChatCompletionAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new ChatCompletionAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with messages array', function () {
        $unifiedRequest = [
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Hello!'],
            ],
            'model' => 'gpt-4',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'model' => 'gpt-4',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ]);
    });

    it('applies default model when not provided', function () {
        $unifiedRequest = [
            'messages' => [
                ['role' => 'user', 'content' => 'Hello!'],
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('gpt-4o-mini');
        expect($result['messages'])->toBe([
            ['role' => 'user', 'content' => 'Hello!'],
        ]);
    });

    it('transforms simple text input to messages array', function () {
        $unifiedRequest = [
            'input' => 'What is the weather today?',
            'model' => 'gpt-4',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['messages'])->toBe([
            ['role' => 'user', 'content' => 'What is the weather today?'],
        ]);
    });

    it('includes optional temperature parameter', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'temperature' => 0.7,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['temperature'])->toBe(0.7);
    });

    it('includes optional max_tokens parameter', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'max_tokens' => 100,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['max_tokens'])->toBe(100);
    });

    it('includes optional top_p parameter', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'top_p' => 0.9,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['top_p'])->toBe(0.9);
    });

    it('includes optional frequency_penalty parameter', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'frequency_penalty' => 0.5,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['frequency_penalty'])->toBe(0.5);
    });

    it('includes optional presence_penalty parameter', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'presence_penalty' => 0.6,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['presence_penalty'])->toBe(0.6);
    });

    it('includes tools parameter when provided', function () {
        $tools = [
            [
                'type' => 'function',
                'function' => [
                    'name' => 'get_weather',
                    'description' => 'Get current weather',
                    'parameters' => [
                        'type' => 'object',
                        'properties' => [
                            'location' => ['type' => 'string'],
                        ],
                    ],
                ],
            ],
        ];

        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Weather?']],
            'tools' => $tools,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['tools'])->toBe($tools);
    });

    it('includes tool_choice parameter when provided', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'tool_choice' => 'auto',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['tool_choice'])->toBe('auto');
    });

    it('includes response_format parameter when provided', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'response_format' => ['type' => 'json_object'],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['response_format'])->toBe(['type' => 'json_object']);
    });

    it('includes stream parameter when provided', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'stream' => true,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['stream'])->toBeTrue();
    });

    it('includes user parameter when provided', function () {
        $unifiedRequest = [
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'user' => 'user-123',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['user'])->toBe('user-123');
    });

    it('handles audio_input by converting to message format', function () {
        $unifiedRequest = [
            'audio_input' => [
                'data' => 'base64_encoded_audio_data',
                'format' => 'wav',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['messages'])->toBe([
            [
                'role' => 'user',
                'content' => [
                    [
                        'type' => 'input_audio',
                        'input_audio' => [
                            'data' => 'base64_encoded_audio_data',
                            'format' => 'wav',
                        ],
                    ],
                ],
            ],
        ]);
    });

    it('uses default format for audio_input when not provided', function () {
        $unifiedRequest = [
            'audio_input' => [
                'data' => 'base64_encoded_audio_data',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['messages'][0]['content'][0]['input_audio']['format'])->toBe('wav');
    });

    it('handles empty audio_input gracefully', function () {
        $unifiedRequest = [
            'audio_input' => [],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['messages'])->toBe([]);
    });

    it('combines messages and audio_input', function () {
        $unifiedRequest = [
            'messages' => [
                ['role' => 'system', 'content' => 'You are helpful.'],
            ],
            'audio_input' => [
                'data' => 'base64_audio',
                'format' => 'mp3',
            ],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['messages'])->toHaveCount(2);
        expect($result['messages'][0])->toBe(['role' => 'system', 'content' => 'You are helpful.']);
        expect($result['messages'][1]['role'])->toBe('user');
        expect($result['messages'][1]['content'][0]['type'])->toBe('input_audio');
    });

    it('transforms all parameters together', function () {
        $unifiedRequest = [
            'model' => 'gpt-4',
            'messages' => [['role' => 'user', 'content' => 'Hello']],
            'temperature' => 0.8,
            'max_tokens' => 150,
            'top_p' => 0.95,
            'frequency_penalty' => 0.3,
            'presence_penalty' => 0.4,
            'stream' => false,
            'user' => 'test-user',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('gpt-4');
        expect($result['temperature'])->toBe(0.8);
        expect($result['max_tokens'])->toBe(150);
        expect($result['top_p'])->toBe(0.95);
        expect($result['frequency_penalty'])->toBe(0.3);
        expect($result['presence_penalty'])->toBe(0.4);
        expect($result['stream'])->toBeFalse();
        expect($result['user'])->toBe('test-user');
    });
});

describe('transformResponse', function () {
    it('transforms OpenAI Chat Completion response with all fields', function () {
        $apiResponse = [
            'id' => 'chatcmpl-123',
            'object' => 'chat.completion',
            'created' => 1677652288,
            'model' => 'gpt-4',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Hello! How can I help you today?',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->id)->toBe('chatcmpl-123');
        expect($result->status)->toBe('completed');
        expect($result->text)->toBe('Hello! How can I help you today?');
        expect($result->type)->toBe('chat_completion');
        expect($result->conversationId)->toBeNull();
        expect($result->audioContent)->toBeNull();
        expect($result->images)->toBeNull();
        expect($result->metadata)->toBe([
            'model' => 'gpt-4',
            'created' => 1677652288,
            'finish_reason' => 'stop',
            'message' => [
                'role' => 'assistant',
                'content' => 'Hello! How can I help you today?',
            ],
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
        ]);
        expect($result->raw)->toBe($apiResponse);
    });

    it('transforms response with minimal fields', function () {
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Simple response',
                    ],
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->text)->toBe('Simple response');
        expect($result->status)->toBe('completed');
        expect($result->type)->toBe('chat_completion');
    });

    it('generates ID when not provided in response', function () {
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'content' => 'Test',
                    ],
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->id)->toStartWith('chatcmpl_');
    });

    it('handles null content gracefully', function () {
        $apiResponse = [
            'id' => 'chatcmpl-456',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                    ],
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
    });

    it('handles empty choices array', function () {
        $apiResponse = [
            'id' => 'chatcmpl-789',
            'choices' => [],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
        expect($result->metadata['message'])->toBe([]);
        expect($result->metadata['finish_reason'])->toBeNull();
    });

    it('handles missing message in choice', function () {
        $apiResponse = [
            'choices' => [
                [],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
        expect($result->metadata['message'])->toBe([]);
    });

    it('includes finish_reason in metadata', function () {
        $apiResponse = [
            'choices' => [
                [
                    'message' => ['content' => 'Done'],
                    'finish_reason' => 'length',
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->metadata['finish_reason'])->toBe('length');
    });

    it('includes usage statistics in metadata', function () {
        $apiResponse = [
            'choices' => [
                [
                    'message' => ['content' => 'Response'],
                ],
            ],
            'usage' => [
                'prompt_tokens' => 15,
                'completion_tokens' => 25,
                'total_tokens' => 40,
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->metadata['usage'])->toBe([
            'prompt_tokens' => 15,
            'completion_tokens' => 25,
            'total_tokens' => 40,
        ]);
    });

    it('handles response with tool calls in message', function () {
        $apiResponse = [
            'id' => 'chatcmpl-tool',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
                                'type' => 'function',
                                'function' => [
                                    'name' => 'get_weather',
                                    'arguments' => '{"location":"London"}',
                                ],
                            ],
                        ],
                    ],
                    'finish_reason' => 'tool_calls',
                ],
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
        expect($result->metadata['message']['tool_calls'])->toBeArray();
        expect($result->metadata['finish_reason'])->toBe('tool_calls');
    });
});
