<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ChatCompletionAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new ChatCompletionAdapter();
});

describe('End-to-end audio input in chat context flow', function () {
    it('processes audio input in chat completion request', function () {
        // Arrange: Create request with audio input in chat context
        $audioData = base64_encode('mock audio binary data');

        $unifiedRequest = [
            'model' => 'gpt-4o-audio-preview',
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Verify request transformation with audio input message
        expect($transformedRequest)->toBeArray()
            ->and($transformedRequest['model'])->toBe('gpt-4o-audio-preview')
            ->and($transformedRequest['messages'])->toBeArray()
            ->and($transformedRequest['messages'])->toHaveCount(1)
            ->and($transformedRequest['messages'][0]['role'])->toBe('user')
            ->and($transformedRequest['messages'][0]['content'])->toBeArray()
            ->and($transformedRequest['messages'][0]['content'][0]['type'])->toBe('input_audio')
            ->and($transformedRequest['messages'][0]['content'][0]['input_audio']['data'])->toBe($audioData)
            ->and($transformedRequest['messages'][0]['content'][0]['input_audio']['format'])->toBe('wav');

        // Simulate API response
        $apiResponse = [
            'id' => 'chatcmpl_audio_123',
            'object' => 'chat.completion',
            'created' => 1234567890,
            'model' => 'gpt-4o-audio-preview',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'I heard your audio input and here is my response.',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 150,
                'completion_tokens' => 20,
                'total_tokens' => 170,
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Verify response transformation
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->id)->toBe('chatcmpl_audio_123')
            ->and($responseDto->status)->toBe('completed')
            ->and($responseDto->text)->toBe('I heard your audio input and here is my response.')
            ->and($responseDto->type)->toBe('chat_completion')
            ->and($responseDto->audioContent)->toBeNull()
            ->and($responseDto->images)->toBeNull()
            ->and($responseDto->metadata['model'])->toBe('gpt-4o-audio-preview')
            ->and($responseDto->metadata['finish_reason'])->toBe('stop')
            ->and($responseDto->metadata['usage']['total_tokens'])->toBe(170)
            ->and($responseDto->isText())->toBeFalse()
            ->and($responseDto->isAudio())->toBeFalse()
            ->and($responseDto->isImage())->toBeFalse();
    });

    it('handles audio input with default format when not specified', function () {
        // Arrange: Audio input without format specification
        $audioData = base64_encode('audio data without format');

        $unifiedRequest = [
            'audio_input' => [
                'data' => $audioData,
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Should use default 'wav' format
        expect($transformedRequest['messages'][0]['content'][0]['input_audio']['format'])->toBe('wav');
    });

    it('handles audio input combined with text messages', function () {
        // Arrange: Request with both existing messages and audio input
        $audioData = base64_encode('combined audio');

        $unifiedRequest = [
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant.'],
                ['role' => 'user', 'content' => 'Here is some context.'],
            ],
            'audio_input' => [
                'data' => $audioData,
                'format' => 'mp3',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Audio input should be appended to existing messages
        expect($transformedRequest['messages'])->toHaveCount(3)
            ->and($transformedRequest['messages'][0]['role'])->toBe('system')
            ->and($transformedRequest['messages'][1]['role'])->toBe('user')
            ->and($transformedRequest['messages'][1]['content'])->toBe('Here is some context.')
            ->and($transformedRequest['messages'][2]['role'])->toBe('user')
            ->and($transformedRequest['messages'][2]['content'][0]['type'])->toBe('input_audio')
            ->and($transformedRequest['messages'][2]['content'][0]['input_audio']['format'])->toBe('mp3');
    });

    it('handles audio input with simple text input', function () {
        // Arrange: Request with simple text and audio input
        $audioData = base64_encode('audio with text');

        $unifiedRequest = [
            'input' => 'This is a text message',
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Both text and audio should be converted to messages
        expect($transformedRequest['messages'])->toHaveCount(2)
            ->and($transformedRequest['messages'][0]['role'])->toBe('user')
            ->and($transformedRequest['messages'][0]['content'])->toBe('This is a text message')
            ->and($transformedRequest['messages'][1]['role'])->toBe('user')
            ->and($transformedRequest['messages'][1]['content'][0]['type'])->toBe('input_audio');
    });

    it('handles audio input with different audio formats', function () {
        $formats = ['wav', 'mp3', 'webm', 'opus'];

        foreach ($formats as $format) {
            // Arrange: Request with specific audio format
            $audioData = base64_encode("audio data in {$format} format");

            $unifiedRequest = [
                'audio_input' => [
                    'data' => $audioData,
                    'format' => $format,
                ],
            ];

            // Act: Transform request
            $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

            // Assert: Format should be preserved
            expect($transformedRequest['messages'][0]['content'][0]['input_audio']['format'])->toBe($format);
        }
    });

    it('handles audio input without data field gracefully', function () {
        // Arrange: Audio input without data (invalid)
        $unifiedRequest = [
            'audio_input' => [
                'format' => 'wav',
            ],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Should not add audio message when data is missing
        expect($transformedRequest['messages'])->toBeEmpty();
    });

    it('includes optional chat parameters with audio input', function () {
        // Arrange: Request with audio input and optional parameters
        $audioData = base64_encode('audio with parameters');

        $unifiedRequest = [
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
            'model' => 'gpt-4o-audio-preview',
            'temperature' => 0.7,
            'max_tokens' => 500,
            'top_p' => 0.9,
            'frequency_penalty' => 0.5,
            'presence_penalty' => 0.3,
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: All parameters should be included
        expect($transformedRequest['model'])->toBe('gpt-4o-audio-preview')
            ->and($transformedRequest['temperature'])->toBe(0.7)
            ->and($transformedRequest['max_tokens'])->toBe(500)
            ->and($transformedRequest['top_p'])->toBe(0.9)
            ->and($transformedRequest['frequency_penalty'])->toBe(0.5)
            ->and($transformedRequest['presence_penalty'])->toBe(0.3);
    });

    it('handles tool calls with audio input', function () {
        // Arrange: Request with audio input and tools
        $audioData = base64_encode('audio with tools');

        $unifiedRequest = [
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_weather',
                        'description' => 'Get the current weather',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [
                                'location' => ['type' => 'string'],
                            ],
                        ],
                    ],
                ],
            ],
            'tool_choice' => 'auto',
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Tools should be included
        expect($transformedRequest['tools'])->toBeArray()
            ->and($transformedRequest['tools'])->toHaveCount(1)
            ->and($transformedRequest['tool_choice'])->toBe('auto');
    });

    it('handles response format parameter with audio input', function () {
        // Arrange: Request with audio and response format
        $audioData = base64_encode('audio with format');

        $unifiedRequest = [
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
            'response_format' => ['type' => 'json_object'],
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Response format should be included
        expect($transformedRequest['response_format'])->toBe(['type' => 'json_object']);
    });

    it('handles streaming parameter with audio input', function () {
        // Arrange: Request with audio and streaming
        $audioData = base64_encode('audio with streaming');

        $unifiedRequest = [
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
            'stream' => true,
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: Stream parameter should be included
        expect($transformedRequest['stream'])->toBeTrue();
    });

    it('handles user identifier with audio input', function () {
        // Arrange: Request with audio and user identifier
        $audioData = base64_encode('audio with user id');

        $unifiedRequest = [
            'audio_input' => [
                'data' => $audioData,
                'format' => 'wav',
            ],
            'user' => 'user-12345',
        ];

        // Act: Transform request
        $transformedRequest = $this->adapter->transformRequest($unifiedRequest);

        // Assert: User parameter should be included
        expect($transformedRequest['user'])->toBe('user-12345');
    });

    it('handles response with null content', function () {
        // Arrange: API response with null content (e.g., tool call)
        $apiResponse = [
            'id' => 'chatcmpl_audio_456',
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => null,
                        'tool_calls' => [
                            [
                                'id' => 'call_123',
                                'type' => 'function',
                                'function' => ['name' => 'get_weather', 'arguments' => '{"location":"NYC"}'],
                            ],
                        ],
                    ],
                    'finish_reason' => 'tool_calls',
                ],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle null content gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->metadata['finish_reason'])->toBe('tool_calls')
            ->and($responseDto->metadata['message']['tool_calls'])->toBeArray();
    });

    it('preserves raw API response in ResponseDto', function () {
        // Arrange: Complete API response
        $apiResponse = [
            'id' => 'chatcmpl_audio_789',
            'object' => 'chat.completion',
            'created' => 1234567890,
            'model' => 'gpt-4o-audio-preview',
            'choices' => [
                [
                    'index' => 0,
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Response text',
                    ],
                    'finish_reason' => 'stop',
                ],
            ],
            'usage' => [
                'prompt_tokens' => 100,
                'completion_tokens' => 50,
                'total_tokens' => 150,
            ],
            'custom_field' => 'custom_value',
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Raw response should be preserved
        expect($responseDto->raw)->toBe($apiResponse)
            ->and($responseDto->raw['custom_field'])->toBe('custom_value')
            ->and($responseDto->raw['object'])->toBe('chat.completion');
    });

    it('generates UUID when API response has no id', function () {
        // Arrange: API response without id
        $apiResponse = [
            'choices' => [
                [
                    'message' => [
                        'role' => 'assistant',
                        'content' => 'Response without id',
                    ],
                ],
            ],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should generate an id starting with 'chatcmpl_'
        expect($responseDto->id)->toStartWith('chatcmpl_')
            ->and($responseDto->text)->toBe('Response without id');
    });

    it('handles empty choices array in response', function () {
        // Arrange: API response with empty choices
        $apiResponse = [
            'id' => 'chatcmpl_empty',
            'choices' => [],
        ];

        // Act: Transform response
        $responseDto = $this->adapter->transformResponse($apiResponse);

        // Assert: Should handle empty choices gracefully
        expect($responseDto)->toBeInstanceOf(ResponseDto::class)
            ->and($responseDto->text)->toBeNull()
            ->and($responseDto->metadata['message'])->toBeEmpty();
    });
});
