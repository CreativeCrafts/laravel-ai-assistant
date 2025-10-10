<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;

describe('isText', function () {
    it('returns true for null type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test text',
            raw: [],
            type: null,
        );

        expect($dto->isText())->toBeTrue();
    });

    it('returns true for text type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test text',
            raw: [],
            type: 'text',
        );

        expect($dto->isText())->toBeTrue();
    });

    it('returns true for audio_transcription type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Transcribed text',
            raw: [],
            type: 'audio_transcription',
        );

        expect($dto->isText())->toBeTrue();
    });

    it('returns true for audio_translation type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Translated text',
            raw: [],
            type: 'audio_translation',
        );

        expect($dto->isText())->toBeTrue();
    });

    it('returns false for audio_speech type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'audio_speech',
        );

        expect($dto->isText())->toBeFalse();
    });

    it('returns false for image types', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_generation',
        );

        expect($dto->isText())->toBeFalse();
    });
});

describe('isAudio', function () {
    it('returns true for audio_speech type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'audio_speech',
        );

        expect($dto->isAudio())->toBeTrue();
    });

    it('returns false for audio_transcription type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Transcribed text',
            raw: [],
            type: 'audio_transcription',
        );

        expect($dto->isAudio())->toBeFalse();
    });

    it('returns false for text type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test text',
            raw: [],
            type: 'text',
        );

        expect($dto->isAudio())->toBeFalse();
    });

    it('returns false for image types', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_generation',
        );

        expect($dto->isAudio())->toBeFalse();
    });

    it('returns false for null type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test',
            raw: [],
            type: null,
        );

        expect($dto->isAudio())->toBeFalse();
    });
});

describe('isImage', function () {
    it('returns true for image_generation type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_generation',
        );

        expect($dto->isImage())->toBeTrue();
    });

    it('returns true for image_edit type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_edit',
        );

        expect($dto->isImage())->toBeTrue();
    });

    it('returns true for image_variation type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'image_variation',
        );

        expect($dto->isImage())->toBeTrue();
    });

    it('returns false for audio types', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            type: 'audio_speech',
        );

        expect($dto->isImage())->toBeFalse();
    });

    it('returns false for text type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test',
            raw: [],
            type: 'text',
        );

        expect($dto->isImage())->toBeFalse();
    });

    it('returns false for null type', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test',
            raw: [],
            type: null,
        );

        expect($dto->isImage())->toBeFalse();
    });
});

describe('saveAudio', function () {
    it('saves audio content to file', function () {
        $tempDir = sys_get_temp_dir() . '/test_audio_' . uniqid('', true);
        $filePath = $tempDir . '/output.mp3';

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: 'fake audio binary data',
        );

        $result = $dto->saveAudio($filePath);

        expect($result)->toBeTrue();
        expect(file_exists($filePath))->toBeTrue();
        expect(file_get_contents($filePath))->toBe('fake audio binary data');

        unlink($filePath);
        rmdir($tempDir);
    });

    it('creates directory if it does not exist', function () {
        $tempDir = sys_get_temp_dir() . '/test_audio_nested_' . uniqid('', true);
        $filePath = $tempDir . '/subdir/output.mp3';

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: 'audio data',
        );

        $result = $dto->saveAudio($filePath);

        expect($result)->toBeTrue();
        expect(file_exists($filePath))->toBeTrue();

        unlink($filePath);
        rmdir($tempDir . '/subdir');
        rmdir($tempDir);
    });

    it('returns false when audio content is null', function () {
        $filePath = sys_get_temp_dir() . '/output.mp3';

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: null,
        );

        $result = $dto->saveAudio($filePath);

        expect($result)->toBeFalse();
        expect(file_exists($filePath))->toBeFalse();
    });

    it('overwrites existing file', function () {
        $filePath = sys_get_temp_dir() . '/output_' . uniqid('', true) . '.mp3';
        file_put_contents($filePath, 'old content');

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            audioContent: 'new audio data',
        );

        $result = $dto->saveAudio($filePath);

        expect($result)->toBeTrue();
        expect(file_get_contents($filePath))->toBe('new audio data');

        unlink($filePath);
    });
});

describe('saveImages', function () {
    it('saves images from b64_json format', function () {
        $tempDir = sys_get_temp_dir() . '/test_images_' . uniqid('', true);

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => base64_encode('fake image data 1')],
                ['b64_json' => base64_encode('fake image data 2')],
            ],
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toHaveCount(2);
        expect(file_exists($savedPaths[0]))->toBeTrue();
        expect(file_exists($savedPaths[1]))->toBeTrue();
        expect(file_get_contents($savedPaths[0]))->toBe('fake image data 1');
        expect(file_get_contents($savedPaths[1]))->toBe('fake image data 2');

        foreach ($savedPaths as $path) {
            unlink($path);
        }
        rmdir($tempDir);
    });

    it('creates directory if it does not exist', function () {
        $tempDir = sys_get_temp_dir() . '/test_images_new_' . uniqid('', true);

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => base64_encode('test data')],
            ],
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toHaveCount(1);
        expect(is_dir($tempDir))->toBeTrue();

        unlink($savedPaths[0]);
        rmdir($tempDir);
    });

    it('returns empty array when images is null', function () {
        $tempDir = sys_get_temp_dir() . '/test_images_' . uniqid('', true);

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            images: null,
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toBeEmpty();
        expect(is_dir($tempDir))->toBeFalse();
    });

    it('returns empty array when images is empty', function () {
        $tempDir = sys_get_temp_dir() . '/test_images_' . uniqid('', true);

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            images: [],
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toBeEmpty();
        expect(is_dir($tempDir))->toBeFalse();
    });

    it('generates unique filenames for each image', function () {
        $tempDir = sys_get_temp_dir() . '/test_images_' . uniqid('', true);

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => base64_encode('image 1')],
                ['b64_json' => base64_encode('image 2')],
            ],
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths[0])->not->toBe($savedPaths[1]);
        expect(basename($savedPaths[0]))->toContain('image_0_');
        expect(basename($savedPaths[1]))->toContain('image_1_');

        foreach ($savedPaths as $path) {
            unlink($path);
        }
        rmdir($tempDir);
    });

    it('skips images with invalid b64_json', function () {
        $tempDir = sys_get_temp_dir() . '/test_images_' . uniqid('', true);

        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
            images: [
                ['b64_json' => 'invalid!!!base64'],
                ['b64_json' => base64_encode('valid image')],
            ],
        );

        $savedPaths = $dto->saveImages($tempDir);

        expect($savedPaths)->toHaveCount(1);

        foreach ($savedPaths as $path) {
            unlink($path);
        }
        rmdir($tempDir);
    });
});

describe('fromArray', function () {
    it('creates ResponseDto from array with all fields', function () {
        $data = [
            'id' => 'test-123',
            'status' => 'completed',
            'output_text' => 'Test output',
            'conversationId' => 'conv-456',
            'audio_content' => 'audio data',
            'images' => [['url' => 'https://example.com/image.png']],
            'type' => 'text',
            'metadata' => ['key' => 'value'],
        ];

        $dto = ResponseDto::fromArray($data);

        expect($dto->id)->toBe('test-123');
        expect($dto->status)->toBe('completed');
        expect($dto->text)->toBe('Test output');
        expect($dto->conversationId)->toBe('conv-456');
        expect($dto->audioContent)->toBe('audio data');
        expect($dto->images)->toBe([['url' => 'https://example.com/image.png']]);
        expect($dto->type)->toBe('text');
        expect($dto->metadata)->toBe(['key' => 'value']);
        expect($dto->raw)->toBe($data);
    });

    it('extracts text from output_text field', function () {
        $data = ['output_text' => 'Text from output_text'];

        $dto = ResponseDto::fromArray($data);

        expect($dto->text)->toBe('Text from output_text');
    });

    it('extracts text from messages field', function () {
        $data = ['messages' => 'Text from messages'];

        $dto = ResponseDto::fromArray($data);

        expect($dto->text)->toBe('Text from messages');
    });

    it('extracts text from content field', function () {
        $data = ['content' => 'Text from content'];

        $dto = ResponseDto::fromArray($data);

        expect($dto->text)->toBe('Text from content');
    });

    it('extracts conversationId from conversation.id', function () {
        $data = ['conversation' => ['id' => 'nested-conv-id']];

        $dto = ResponseDto::fromArray($data);

        expect($dto->conversationId)->toBe('nested-conv-id');
    });

    it('extracts status from response.status', function () {
        $data = ['response' => ['status' => 'pending']];

        $dto = ResponseDto::fromArray($data);

        expect($dto->status)->toBe('pending');
    });

    it('defaults to unknown status when not provided', function () {
        $data = [];

        $dto = ResponseDto::fromArray($data);

        expect($dto->status)->toBe('unknown');
    });

    it('handles missing optional fields', function () {
        $data = ['id' => 'minimal-id'];

        $dto = ResponseDto::fromArray($data);

        expect($dto->id)->toBe('minimal-id');
        expect($dto->conversationId)->toBeNull();
        expect($dto->audioContent)->toBeNull();
        expect($dto->images)->toBeNull();
        expect($dto->type)->toBeNull();
        expect($dto->metadata)->toBe([]);
    });
});

describe('toArray', function () {
    it('converts ResponseDto to array', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: 'Test text',
            raw: ['original' => 'data'],
            conversationId: 'conv-123',
            audioContent: 'audio data',
            images: [['url' => 'https://example.com/image.png']],
            type: 'text',
            metadata: ['key' => 'value'],
        );

        $array = $dto->toArray();

        expect($array)->toBe([
            'id' => 'test-id',
            'status' => 'completed',
            'text' => 'Test text',
            'conversation_id' => 'conv-123',
            'audio_content' => 'audio data',
            'images' => [['url' => 'https://example.com/image.png']],
            'type' => 'text',
            'metadata' => ['key' => 'value'],
            'raw' => ['original' => 'data'],
        ]);
    });

    it('handles null values correctly', function () {
        $dto = new ResponseDto(
            id: 'test-id',
            status: 'completed',
            text: null,
            raw: [],
        );

        $array = $dto->toArray();

        expect($array['text'])->toBeNull();
        expect($array['conversation_id'])->toBeNull();
        expect($array['audio_content'])->toBeNull();
        expect($array['images'])->toBeNull();
        expect($array['type'])->toBeNull();
    });
});
