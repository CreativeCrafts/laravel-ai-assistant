<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Adapters\AdapterFactory;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;
use CreativeCrafts\LaravelAiAssistant\Http\MultipartRequestBuilder;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;

describe('Performance Optimization', function () {
    describe('Adapter Caching', function () {
        it('reuses adapter instances within the same factory', function () {
            $factory = new AdapterFactory();

            $adapter1 = $factory->make(OpenAiEndpoint::AudioTranscription);
            $adapter2 = $factory->make(OpenAiEndpoint::AudioTranscription);

            expect($adapter1)->toBe($adapter2);
        });

        it('creates different adapters for different endpoints', function () {
            $factory = new AdapterFactory();

            $audioAdapter = $factory->make(OpenAiEndpoint::AudioTranscription);
            $imageAdapter = $factory->make(OpenAiEndpoint::ImageGeneration);

            expect($audioAdapter)->not->toBe($imageAdapter);
        });

        it('caches adapters for all endpoint types', function () {
            $factory = new AdapterFactory();

            $endpoints = [
                OpenAiEndpoint::AudioTranscription,
                OpenAiEndpoint::AudioTranslation,
                OpenAiEndpoint::AudioSpeech,
                OpenAiEndpoint::ImageGeneration,
                OpenAiEndpoint::ImageEdit,
                OpenAiEndpoint::ImageVariation,
                OpenAiEndpoint::ChatCompletion,
                OpenAiEndpoint::ResponseApi,
            ];

            foreach ($endpoints as $endpoint) {
                $adapter1 = $factory->make($endpoint);
                $adapter2 = $factory->make($endpoint);

                expect($adapter1)->toBe($adapter2);
            }
        });
    });

    describe('Progress Callback Support', function () {
        it('accepts progress callback parameter in GuzzleOpenAITransport', function () {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['status' => 'ok'])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $guzzle = new GuzzleClient(['handler' => $handlerStack]);
            $transport = new GuzzleOpenAITransport($guzzle);

            $progressCalled = false;
            $progressCallback = function ($downloadTotal, $downloaded, $uploadTotal, $uploaded) use (&$progressCalled) {
                $progressCalled = true;
            };

            $result = $transport->postMultipart(
                '/v1/test',
                ['field' => 'value'],
                [],
                null,
                false,
                $progressCallback
            );

            expect($result)->toHaveKey('status');
            expect($result['status'])->toBe('ok');
        });

        it('works without progress callback', function () {
            $mock = new MockHandler([
                new Response(200, [], json_encode(['status' => 'ok'])),
            ]);

            $handlerStack = HandlerStack::create($mock);
            $guzzle = new GuzzleClient(['handler' => $handlerStack]);
            $transport = new GuzzleOpenAITransport($guzzle);

            $result = $transport->postMultipart(
                '/v1/test',
                ['field' => 'value'],
                [],
                null,
                false,
                null
            );

            expect($result)->toHaveKey('status');
            expect($result['status'])->toBe('ok');
        });
    });

    describe('Request Size Tracking', function () {
        it('tracks total request size when adding files', function () {
            $builder = new MultipartRequestBuilder();

            // Create a temporary test file
            $tempFile = tempnam(sys_get_temp_dir(), 'test_');
            $testContent = str_repeat('a', 1024); // 1KB
            file_put_contents($tempFile, $testContent);

            $builder->addFile('file', $tempFile, null, null, null);

            expect($builder->getTotalRequestSize())->toBe(1024);

            unlink($tempFile);
        });

        it('accumulates size for multiple files', function () {
            $builder = new MultipartRequestBuilder();

            // Create temporary test files
            $tempFile1 = tempnam(sys_get_temp_dir(), 'test_');
            $tempFile2 = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($tempFile1, str_repeat('a', 1024)); // 1KB
            file_put_contents($tempFile2, str_repeat('b', 2048)); // 2KB

            $builder->addFile('file1', $tempFile1, null, null, null);
            $builder->addFile('file2', $tempFile2, null, null, null);

            expect($builder->getTotalRequestSize())->toBe(3072); // 3KB

            unlink($tempFile1);
            unlink($tempFile2);
        });

        it('resets size counter when cleared', function () {
            $builder = new MultipartRequestBuilder();

            $tempFile = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($tempFile, str_repeat('a', 1024));

            $builder->addFile('file', $tempFile, null, null, null);
            expect($builder->getTotalRequestSize())->toBe(1024);

            $builder->clear();
            expect($builder->getTotalRequestSize())->toBe(0);

            unlink($tempFile);
        });

        it('reports size in bytes for accurate monitoring', function () {
            $builder = new MultipartRequestBuilder();

            $tempFile = tempnam(sys_get_temp_dir(), 'test_');
            $size = 10485760; // 10MB
            file_put_contents($tempFile, str_repeat('x', $size));

            $builder->addFile('file', $tempFile, null, 'application/octet-stream', null);

            expect($builder->getTotalRequestSize())->toBe($size);

            unlink($tempFile);
        });
    });

    describe('Memory Efficiency', function () {
        it('uses file paths without loading into memory', function () {
            $builder = new MultipartRequestBuilder();

            $tempFile = tempnam(sys_get_temp_dir(), 'test_');
            file_put_contents($tempFile, str_repeat('x', 1024 * 1024)); // 1MB

            $initialMemory = memory_get_usage();

            $builder->addFile('file', $tempFile, null, 'application/octet-stream', null);
            $multipartData = $builder->build();

            $memoryIncrease = memory_get_usage() - $initialMemory;

            // Memory increase should be minimal (less than 100KB for metadata)
            expect($memoryIncrease)->toBeLessThan(102400);

            unlink($tempFile);
        });
    });
});
