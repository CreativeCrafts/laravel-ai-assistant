<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Support\MultipartFormData;
use GuzzleHttp\Psr7\Utils;

beforeEach(function () {
    $this->audioPath = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('encodes scalar, list and nested fields using OpenAI form conventions', function () {
    $parts = MultipartFormData::encode([
        'model' => 'gpt-4o-transcribe-diarize',
        'temperature' => 0.2,
        'stream' => true,
        'skip' => null,
        'known_speaker_names' => ['agent', 'customer'],
        'chunking_strategy' => ['type' => 'server_vad', 'threshold' => 0.5],
    ]);

    expect($parts)->toBe([
        ['name' => 'model', 'contents' => 'gpt-4o-transcribe-diarize'],
        ['name' => 'temperature', 'contents' => '0.2'],
        ['name' => 'stream', 'contents' => 'true'],
        ['name' => 'known_speaker_names[]', 'contents' => 'agent'],
        ['name' => 'known_speaker_names[]', 'contents' => 'customer'],
        ['name' => 'chunking_strategy[type]', 'contents' => 'server_vad'],
        ['name' => 'chunking_strategy[threshold]', 'contents' => '0.5'],
    ]);
});

it('opens file paths with filename and detected content type', function () {
    $parts = MultipartFormData::encode(['file' => $this->audioPath], ['file']);

    expect($parts)->toHaveCount(1)
        ->and($parts[0]['name'])->toBe('file')
        ->and($parts[0]['filename'])->toBe('test-audio.mp3')
        ->and(is_resource($parts[0]['contents']))->toBeTrue()
        ->and($parts[0]['headers']['Content-Type'] ?? null)->toBe('audio/mpeg');
});

it('sends lists of files as repeated name[] parts', function () {
    $parts = MultipartFormData::encode(['files' => [$this->audioPath, new SplFileInfo($this->audioPath)]], ['files']);

    expect(array_column($parts, 'name'))->toBe(['files[]', 'files[]'])
        ->and(array_column($parts, 'filename'))->toBe(['test-audio.mp3', 'test-audio.mp3']);
});

it('keeps explicit parts and raw contents', function () {
    $stream = Utils::streamFor('raw-bytes');
    $parts = MultipartFormData::encode([
        'data' => ['contents' => 'chunk-bytes', 'filename' => 'part.bin', 'content_type' => 'application/octet-stream'],
        'file' => $stream,
    ], ['data', 'file']);

    expect($parts[0])->toBe([
        'name' => 'data',
        'contents' => 'chunk-bytes',
        'filename' => 'part.bin',
        'headers' => ['Content-Type' => 'application/octet-stream'],
    ])->and($parts[1]['contents'])->toBe($stream);
});

it('throws for missing files', function () {
    MultipartFormData::encode(['file' => '/does/not/exist.mp3'], ['file']);
})->throws(FileValidationException::class, 'File not found');

it('builds data URLs for reference samples', function () {
    $dataUrl = MultipartFormData::toDataUrl($this->audioPath, 'audio/mpeg');

    expect($dataUrl)->toStartWith('data:audio/mpeg;base64,')
        ->and(base64_decode(substr($dataUrl, strlen('data:audio/mpeg;base64,')), true))
        ->toBe(file_get_contents($this->audioPath));
});
