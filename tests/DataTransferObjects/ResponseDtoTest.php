<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

covers(ResponseDto::class);

describe('ResponseDto', function () {
    it('constructs with all new properties', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Hello world',
            raw: ['data' => 'value'],
            conversationId: 'conv-123',
            audioContent: 'binary-audio-data',
            images: [
                ['url' => 'https://example.com/image1.png'],
                ['b64_json' => 'base64data'],
            ],
            type: 'image_generation',
            metadata: ['model' => 'dall-e-3'],
        );

        expect($dto->id)->toBe('test-id')
            ->and($dto->status)->toBe('completed')
            ->and($dto->text)->toBe('Hello world')
            ->and($dto->conversationId)->toBe('conv-123')
            ->and($dto->audioContent)->toBe('binary-audio-data')
            ->and($dto->images)->toHaveCount(2)
            ->and($dto->type)->toBe('image_generation')
            ->and($dto->metadata)->toBe(['model' => 'dall-e-3']);
    });

    it('creates from array with new properties', function () {
        $data = [
            'id' => 'resp-456',
            'status' => 'success',
            'output_text' => 'Generated text',
            'audio_content' => 'audio-binary',
            'images' => [['url' => 'https://example.com/img.png']],
            'type' => 'audio_speech',
            'metadata' => ['voice' => 'nova'],
            'conversationId' => 'conv-789',
        ];

        $dto = ResponseDto::fromArray($data);

        expect($dto->id)->toBe('resp-456')
            ->and($dto->status)->toBe('success')
            ->and($dto->text)->toBe('Generated text')
            ->and($dto->audioContent)->toBe('audio-binary')
            ->and($dto->images)->toHaveCount(1)
            ->and($dto->type)->toBe('audio_speech')
            ->and($dto->metadata)->toBe(['voice' => 'nova'])
            ->and($dto->conversationId)->toBe('conv-789');
    });

    it('converts to array with all properties', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Response text',
            raw: ['original' => 'data'],
            conversationId: 'conv-123',
            audioContent: 'audio-data',
            images: [['url' => 'https://example.com/image.png']],
            type: 'image_generation',
            metadata: ['size' => '1024x1024'],
        );

        $array = $dto->toArray();

        expect($array)->toHaveKey('id')
            ->and($array)->toHaveKey('status')
            ->and($array)->toHaveKey('text')
            ->and($array)->toHaveKey('conversation_id')
            ->and($array)->toHaveKey('audio_content')
            ->and($array)->toHaveKey('images')
            ->and($array)->toHaveKey('type')
            ->and($array)->toHaveKey('metadata')
            ->and($array)->toHaveKey('raw')
            ->and($array['audio_content'])->toBe('audio-data')
            ->and($array['images'])->toHaveCount(1)
            ->and($array['type'])->toBe('image_generation')
            ->and($array['metadata'])->toBe(['size' => '1024x1024']);
    });

    it('isText returns true for text responses', function () {
        $textDto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: 'Text response',
            raw: [],
            type: 'text',
        );

        expect($textDto->isText())->toBeTrue();
    });

    it('isText returns true for audio transcription', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: 'Transcribed text',
            raw: [],
            type: 'audio_transcription',
        );

        expect($dto->isText())->toBeTrue();
    });

    it('isText returns true for audio translation', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: 'Translated text',
            raw: [],
            type: 'audio_translation',
        );

        expect($dto->isText())->toBeTrue();
    });

    it('isText returns true when type is null (backward compatibility)', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: 'Legacy response',
            raw: [],
        );

        expect($dto->isText())->toBeTrue();
    });

    it('isAudio returns true for audio speech responses', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: 'binary-audio',
            type: 'audio_speech',
        );

        expect($dto->isAudio())->toBeTrue();
    });

    it('isAudio returns false for non-audio types', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: 'Text',
            raw: [],
            type: 'text',
        );

        expect($dto->isAudio())->toBeFalse();
    });

    it('isImage returns true for image_generation', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            images: [['url' => 'https://example.com/img.png']],
            type: 'image_generation',
        );

        expect($dto->isImage())->toBeTrue();
    });

    it('isImage returns true for image_edit', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_edit',
        );

        expect($dto->isImage())->toBeTrue();
    });

    it('isImage returns true for image_variation', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_variation',
        );

        expect($dto->isImage())->toBeTrue();
    });

    it('isImage returns false for non-image types', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: 'Text',
            raw: [],
            type: 'text',
        );

        expect($dto->isImage())->toBeFalse();
    });

    it('saveAudio returns false when audioContent is null', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
        );

        expect($dto->saveAudio('/tmp/test.mp3'))->toBeFalse();
    });

    it('saveAudio writes audio content to file', function () {
        $tempDir = sys_get_temp_dir() . '/test-audio-' . bin2hex(random_bytes(8));
        $audioPath = $tempDir . '/audio.mp3';

        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: 'test-audio-binary-content',
            type: 'audio_speech',
        );

        $result = $dto->saveAudio($audioPath);

        expect($result)->toBeTrue()
            ->and(file_exists($audioPath))->toBeTrue()
            ->and(file_get_contents($audioPath))->toBe('test-audio-binary-content');

        // Cleanup
        @unlink($audioPath);
        @rmdir($tempDir);
    });

    it('saveAudio creates directory if it does not exist', function () {
        $tempDir = sys_get_temp_dir() . '/test-nested-' . bin2hex(random_bytes(8)) . '/audio';
        $audioPath = $tempDir . '/output.mp3';

        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: 'audio-content',
            type: 'audio_speech',
        );

        $result = $dto->saveAudio($audioPath);

        expect($result)->toBeTrue()
            ->and(is_dir(dirname($audioPath)))->toBeTrue()
            ->and(file_exists($audioPath))->toBeTrue();

        // Cleanup
        @unlink($audioPath);
        @rmdir($tempDir);
        @rmdir(dirname($tempDir));
    });

    it('saveImages returns empty array when images is null', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
        );

        expect($dto->saveImages('/tmp'))->toBe([]);
    });

    it('saveImages returns empty array when images is empty', function () {
        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            images: [],
        );

        expect($dto->saveImages('/tmp'))->toBe([]);
    });

    it('saveImages saves base64 encoded images', function () {
        $tempDir = sys_get_temp_dir() . '/test-images-' . bin2hex(random_bytes(8));

        // Simple base64 encoded PNG (1x1 pixel)
        $base64Image = base64_encode('fake-image-data');

        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => $base64Image],
            ],
            type: 'image_generation',
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toHaveCount(1)
            ->and(file_exists($savedPaths[0]))->toBeTrue()
            ->and(file_get_contents($savedPaths[0]))->toBe('fake-image-data');

        // Cleanup
        foreach ($savedPaths as $path) {
            @unlink($path);
        }
        @rmdir($tempDir);
    });

    it('saveImages creates directory if it does not exist', function () {
        $tempDir = sys_get_temp_dir() . '/test-images-nested-' . bin2hex(random_bytes(8)) . '/images';

        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => base64_encode('image1')],
            ],
            type: 'image_generation',
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toHaveCount(1)
            ->and(is_dir($tempDir))->toBeTrue();

        // Cleanup
        foreach ($savedPaths as $path) {
            @unlink($path);
        }
        @rmdir($tempDir);
        @rmdir(dirname($tempDir));
    });

    it('saveImages handles multiple images', function () {
        $tempDir = sys_get_temp_dir() . '/test-multiple-' . bin2hex(random_bytes(8));

        $dto = new ResponseDto(
            id: 'id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => base64_encode('image1')],
                ['b64_json' => base64_encode('image2')],
                ['b64_json' => base64_encode('image3')],
            ],
            type: 'image_generation',
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toHaveCount(3);

        // Cleanup
        foreach ($savedPaths as $path) {
            @unlink($path);
        }
        @rmdir($tempDir);
    });

    it('maintains backward compatibility with existing text responses', function () {
        $data = [
            'id' => 'legacy-id',
            'status' => 'completed',
            'output_text' => 'Legacy response text',
        ];

        $dto = ResponseDto::fromArray($data);

        expect($dto->id)->toBe('legacy-id')
            ->and($dto->status)->toBe('completed')
            ->and($dto->text)->toBe('Legacy response text')
            ->and($dto->audioContent)->toBeNull()
            ->and($dto->images)->toBeNull()
            ->and($dto->type)->toBeNull()
            ->and($dto->isText())->toBeTrue()
            ->and($dto->isAudio())->toBeFalse()
            ->and($dto->isImage())->toBeFalse();
    });
});
