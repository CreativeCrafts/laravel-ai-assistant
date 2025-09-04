<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\AiAssistant;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;

beforeEach(function (): void {
    // Ensure a clean virtual filesystem root for each test
    /** @var vfsStreamDirectory $root */
    $this->vfsRoot = vfsStream::setup('root');
});

it('throws for invalid audio file extension', function () {
    $file = vfsStream::newFile('audio.txt')
        ->withContent('hello world')
        ->at($this->vfsRoot);

    $assistant = AiAssistant::acceptPrompt($file->url());

    expect(fn () => $assistant->translateAudioTo())
        ->toThrow(FileOperationException::class);
});

it('throws for zero-length audio file', function () {
    $file = vfsStream::newFile('empty.mp3')
        ->withContent('')
        ->at($this->vfsRoot);

    $assistant = AiAssistant::acceptPrompt($file->url());

    expect(fn () => $assistant->translateAudioTo())
        ->toThrow(FileOperationException::class);
});

it('throws for oversize audio file (>25MB)', function () {
    // Create a file slightly larger than 25MB
    $size = (25 * 1024 * 1024) + 1024; // 25MB + 1KB
    // To avoid large string memory, write in chunks
    $file = vfsStream::newFile('big.mp3')->at($this->vfsRoot);
    $stream = fopen($file->url(), 'wb');
    $chunk = str_repeat('0', 1024 * 1024); // 1MB chunk
    for ($i = 0; $i < intdiv($size, strlen($chunk)); $i++) {
        fwrite($stream, $chunk);
    }
    $remainder = $size % strlen($chunk);
    if ($remainder > 0) {
        fwrite($stream, str_repeat('0', $remainder));
    }
    fclose($stream);

    $assistant = AiAssistant::acceptPrompt($file->url());

    expect(fn () => $assistant->translateAudioTo())
        ->toThrow(FileOperationException::class);
});

it('opens valid audio file and calls AssistantService::translateTo', function () {
    // Create a small valid MP3 file
    $file = vfsStream::newFile('valid.mp3')
        ->withContent(str_repeat('a', 1024)) // 1KB
        ->at($this->vfsRoot);

    $service = Mockery::mock(AssistantService::class);
    $service->shouldReceive('translateTo')
        ->once()
        ->andReturn('translated text');

    $assistant = AiAssistant::acceptPrompt($file->url())->client($service);

    $result = $assistant->translateAudioTo();

    expect($result)->toBe('translated text');
});
