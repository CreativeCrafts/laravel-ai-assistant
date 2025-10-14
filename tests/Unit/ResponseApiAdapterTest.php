<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\ResponseApiAdapter;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

beforeEach(function () {
    $this->adapter = new ResponseApiAdapter();
});

describe('transformRequest', function () {
    it('transforms unified request with input field', function () {
        $unifiedRequest = [
            'input' => 'Hello, how are you?',
            'model' => 'gpt-4',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'model' => 'gpt-4',
            'input' => 'Hello, how are you?',
        ]);
    });

    it('applies default model when not provided', function () {
        $unifiedRequest = [
            'input' => 'Test input',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('gpt-4o-mini');
        expect($result['input'])->toBe('Test input');
    });

    it('transforms request with messages array', function () {
        $unifiedRequest = [
            'messages' => [
                ['role' => 'system', 'content' => 'You are helpful.'],
                ['role' => 'user', 'content' => 'Hello!'],
            ],
            'model' => 'gpt-4o',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['messages'])->toBe([
            ['role' => 'system', 'content' => 'You are helpful.'],
            ['role' => 'user', 'content' => 'Hello!'],
        ]);
    });

    it('includes conversation_id when provided', function () {
        $unifiedRequest = [
            'input' => 'Continue conversation',
            'conversation_id' => 'conv_12345',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['conversation_id'])->toBe('conv_12345');
    });

    it('includes modalities when provided', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'modalities' => ['text', 'audio'],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['modalities'])->toBe(['text', 'audio']);
    });

    it('includes optional temperature parameter', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'temperature' => 0.7,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['temperature'])->toBe(0.7);
    });

    it('includes optional max_tokens parameter', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'max_tokens' => 150,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['max_tokens'])->toBe(150);
    });

    it('includes optional top_p parameter', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'top_p' => 0.9,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['top_p'])->toBe(0.9);
    });

    it('includes optional frequency_penalty parameter', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'frequency_penalty' => 0.5,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['frequency_penalty'])->toBe(0.5);
    });

    it('includes optional presence_penalty parameter', function () {
        $unifiedRequest = [
            'input' => 'Test',
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
                    'description' => 'Get weather information',
                ],
            ],
        ];

        $unifiedRequest = [
            'input' => 'Weather?',
            'tools' => $tools,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['tools'])->toBe($tools);
    });

    it('includes tool_choice parameter when provided', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'tool_choice' => 'auto',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['tool_choice'])->toBe('auto');
    });

    it('includes response_format parameter when provided', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'response_format' => ['type' => 'json_object'],
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['text'])->toBe(['format' => ['type' => 'json_object']]);
    });

    it('includes stream parameter when provided', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'stream' => true,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['stream'])->toBeTrue();
    });

    it('includes user parameter when provided', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'user' => 'user-456',
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['user'])->toBe('user-456');
    });

    it('includes metadata parameter when provided', function () {
        $metadata = ['session_id' => 'session-123', 'source' => 'mobile'];

        $unifiedRequest = [
            'input' => 'Test',
            'metadata' => $metadata,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['metadata'])->toBe($metadata);
    });

    it('includes store parameter when provided', function () {
        $unifiedRequest = [
            'input' => 'Test',
            'store' => true,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['store'])->toBeTrue();
    });

    it('transforms all parameters together', function () {
        $unifiedRequest = [
            'model' => 'gpt-4',
            'input' => 'Complex request',
            'conversation_id' => 'conv_789',
            'modalities' => ['text'],
            'temperature' => 0.8,
            'max_tokens' => 200,
            'top_p' => 0.95,
            'frequency_penalty' => 0.3,
            'presence_penalty' => 0.4,
            'stream' => false,
            'user' => 'test-user',
            'metadata' => ['key' => 'value'],
            'store' => true,
        ];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result['model'])->toBe('gpt-4');
        expect($result['input'])->toBe('Complex request');
        expect($result['conversation_id'])->toBe('conv_789');
        expect($result['modalities'])->toBe(['text']);
        expect($result['temperature'])->toBe(0.8);
        expect($result['max_tokens'])->toBe(200);
        expect($result['top_p'])->toBe(0.95);
        expect($result['frequency_penalty'])->toBe(0.3);
        expect($result['presence_penalty'])->toBe(0.4);
        expect($result['stream'])->toBeFalse();
        expect($result['user'])->toBe('test-user');
        expect($result['metadata'])->toBe(['key' => 'value']);
        expect($result['store'])->toBeTrue();
    });

    it('handles empty request with only model', function () {
        $unifiedRequest = [];

        $result = $this->adapter->transformRequest($unifiedRequest);

        expect($result)->toBe([
            'model' => 'gpt-4o-mini',
        ]);
    });
});

describe('transformResponse', function () {
    it('transforms response with output_text field', function () {
        $apiResponse = [
            'id' => 'resp-123',
            'status' => 'completed',
            'output_text' => 'This is the response text',
            'model' => 'gpt-4',
            'created' => 1677652288,
            'usage' => [
                'prompt_tokens' => 10,
                'completion_tokens' => 20,
                'total_tokens' => 30,
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->id)->toBe('resp-123');
        expect($result->status)->toBe('completed');
        expect($result->text)->toBe('This is the response text');
        expect($result->type)->toBe('response_api');
        expect($result->conversationId)->toBeNull();
        expect($result->audioContent)->toBeNull();
        expect($result->images)->toBeNull();
        expect($result->metadata['model'])->toBe('gpt-4');
        expect($result->metadata['created'])->toBe(1677652288);
        expect($result->metadata['usage'])->toBe([
            'prompt_tokens' => 10,
            'completion_tokens' => 20,
            'total_tokens' => 30,
        ]);
        expect($result->raw)->toBe($apiResponse);
    });

    it('transforms response with content field', function () {
        $apiResponse = [
            'id' => 'resp-456',
            'content' => 'Response via content field',
            'status' => 'completed',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBe('Response via content field');
        expect($result->id)->toBe('resp-456');
    });

    it('transforms response with text field', function () {
        $apiResponse = [
            'id' => 'resp-789',
            'text' => 'Response via text field',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBe('Response via text field');
    });

    it('transforms response with messages field as string', function () {
        $apiResponse = [
            'id' => 'resp-msg',
            'messages' => 'Message as string',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBe('Message as string');
    });

    it('generates ID when not provided in response', function () {
        $apiResponse = [
            'output_text' => 'Test response',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->id)->toStartWith('resp_');
    });

    it('applies default status when not provided', function () {
        $apiResponse = [
            'output_text' => 'Test',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->status)->toBe('completed');
    });

    it('extracts conversationId from conversationId field', function () {
        $apiResponse = [
            'id' => 'resp-conv',
            'output_text' => 'Test',
            'conversationId' => 'conv_12345',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->conversationId)->toBe('conv_12345');
    });

    it('extracts conversationId from conversation.id field', function () {
        $apiResponse = [
            'id' => 'resp-conv2',
            'output_text' => 'Test',
            'conversation' => [
                'id' => 'conv_67890',
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->conversationId)->toBe('conv_67890');
    });

    it('handles missing text content gracefully', function () {
        $apiResponse = [
            'id' => 'resp-empty',
            'status' => 'completed',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
    });

    it('includes metadata in response', function () {
        $apiResponse = [
            'id' => 'resp-meta',
            'output_text' => 'Test',
            'metadata' => [
                'session_id' => 'session-123',
                'source' => 'api',
            ],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->metadata['metadata'])->toBe([
            'session_id' => 'session-123',
            'source' => 'api',
        ]);
    });

    it('includes usage statistics in metadata', function () {
        $apiResponse = [
            'output_text' => 'Test',
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

    it('sets audioContent to null for text responses', function () {
        $apiResponse = [
            'id' => 'resp-text',
            'output_text' => 'Text only response',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->audioContent)->toBeNull();
    });

    it('sets images to null for text responses', function () {
        $apiResponse = [
            'id' => 'resp-text2',
            'output_text' => 'Another text response',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->images)->toBeNull();
    });

    it('always sets type to response_api', function () {
        $apiResponse = [
            'id' => 'resp-type',
            'output_text' => 'Test',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->type)->toBe('response_api');
    });

    it('transforms minimal response', function () {
        $apiResponse = [];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result)->toBeInstanceOf(ResponseDto::class);
        expect($result->id)->toStartWith('resp_');
        expect($result->status)->toBe('completed');
        expect($result->text)->toBeNull();
        expect($result->type)->toBe('response_api');
    });

    it('prefers output_text over other text fields', function () {
        $apiResponse = [
            'output_text' => 'From output_text',
            'content' => 'From content',
            'text' => 'From text',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBe('From output_text');
    });

    it('falls back to content when output_text is missing', function () {
        $apiResponse = [
            'content' => 'From content',
            'text' => 'From text',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBe('From content');
    });

    it('falls back to text when output_text and content are missing', function () {
        $apiResponse = [
            'text' => 'From text',
            'messages' => 'From messages',
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBe('From text');
    });

    it('handles non-string text values', function () {
        $apiResponse = [
            'text' => 123,
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
    });

    it('handles non-string content values', function () {
        $apiResponse = [
            'content' => ['array' => 'value'],
        ];

        $result = $this->adapter->transformResponse($apiResponse);

        expect($result->text)->toBeNull();
    });
});
