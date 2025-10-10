<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Support\InputBuilder;

describe('InputBuilder', function () {
    it('can be instantiated via make method', function () {
        $builder = InputBuilder::make();

        expect($builder)->toBeInstanceOf(InputBuilder::class);
    });

    it('returns empty array initially', function () {
        $builder = InputBuilder::make();

        expect($builder->toArray())->toBe([]);
    });

    describe('message()', function () {
        it('adds a text message to the unified request', function () {
            $builder = InputBuilder::make()->message('Hello, world!');

            expect($builder->toArray())->toBe([
                'message' => 'Hello, world!',
            ]);
        });

        it('is immutable and returns a new instance', function () {
            $original = InputBuilder::make();
            $modified = $original->message('Test message');

            expect($original->toArray())->toBe([]);
            expect($modified->toArray())->toBe([
                'message' => 'Test message',
            ]);
            expect($original)->not->toBe($modified);
        });
    });

    describe('audio()', function () {
        it('adds audio configuration for transcription', function () {
            $config = [
                'file' => '/path/to/audio.mp3',
                'action' => 'transcribe',
                'language' => 'en',
            ];

            $builder = InputBuilder::make()->audio($config);

            expect($builder->toArray())->toBe([
                'audio' => $config,
            ]);
        });

        it('adds audio configuration for translation', function () {
            $config = [
                'file' => '/path/to/audio.mp3',
                'action' => 'translate',
                'model' => 'whisper-1',
            ];

            $builder = InputBuilder::make()->audio($config);

            expect($builder->toArray())->toBe([
                'audio' => $config,
            ]);
        });

        it('adds audio configuration for speech generation', function () {
            $config = [
                'text' => 'Hello, this is a test.',
                'action' => 'speech',
                'voice' => 'alloy',
                'model' => 'tts-1',
            ];

            $builder = InputBuilder::make()->audio($config);

            expect($builder->toArray())->toBe([
                'audio' => $config,
            ]);
        });

        it('validates transcribe action requires file parameter', function () {
            expect(fn () => InputBuilder::make()->audio([
                'action' => 'transcribe',
            ]))->toThrow(InvalidArgumentException::class, 'Audio transcribe action requires a "file" parameter.');
        });

        it('validates translate action requires file parameter', function () {
            expect(fn () => InputBuilder::make()->audio([
                'action' => 'translate',
            ]))->toThrow(InvalidArgumentException::class, 'Audio translate action requires a "file" parameter.');
        });

        it('validates speech action requires text parameter', function () {
            expect(fn () => InputBuilder::make()->audio([
                'action' => 'speech',
            ]))->toThrow(InvalidArgumentException::class, 'Audio speech action requires a "text" parameter.');
        });

        it('validates invalid audio action', function () {
            expect(fn () => InputBuilder::make()->audio([
                'action' => 'invalid_action',
                'file' => '/path/to/audio.mp3',
            ]))->toThrow(InvalidArgumentException::class, 'Invalid audio action. Must be one of: transcribe, translate, speech');
        });

        it('validates voice must be valid', function () {
            expect(fn () => InputBuilder::make()->audio([
                'text' => 'Hello',
                'action' => 'speech',
                'voice' => 'invalid_voice',
            ]))->toThrow(InvalidArgumentException::class, 'Invalid voice. Must be one of: alloy, echo, fable, onyx, nova, shimmer');
        });

        it('accepts valid voices', function () {
            $validVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];

            foreach ($validVoices as $voice) {
                $builder = InputBuilder::make()->audio([
                    'text' => 'Hello',
                    'action' => 'speech',
                    'voice' => $voice,
                ]);

                expect($builder->toArray()['audio']['voice'])->toBe($voice);
            }
        });

        it('validates speed must be between 0.25 and 4.0', function () {
            expect(fn () => InputBuilder::make()->audio([
                'text' => 'Hello',
                'action' => 'speech',
                'speed' => 0.1,
            ]))->toThrow(InvalidArgumentException::class, 'Speed must be between 0.25 and 4.0');

            expect(fn () => InputBuilder::make()->audio([
                'text' => 'Hello',
                'action' => 'speech',
                'speed' => 5.0,
            ]))->toThrow(InvalidArgumentException::class, 'Speed must be between 0.25 and 4.0');
        });

        it('accepts valid speed values', function () {
            $builder = InputBuilder::make()->audio([
                'text' => 'Hello',
                'action' => 'speech',
                'speed' => 1.5,
            ]);

            expect($builder->toArray()['audio']['speed'])->toBe(1.5);
        });

        it('validates temperature must be between 0 and 1', function () {
            expect(fn () => InputBuilder::make()->audio([
                'file' => '/path/to/audio.mp3',
                'action' => 'transcribe',
                'temperature' => -0.1,
            ]))->toThrow(InvalidArgumentException::class, 'Temperature must be between 0 and 1');

            expect(fn () => InputBuilder::make()->audio([
                'file' => '/path/to/audio.mp3',
                'action' => 'transcribe',
                'temperature' => 1.1,
            ]))->toThrow(InvalidArgumentException::class, 'Temperature must be between 0 and 1');
        });

        it('is immutable and returns a new instance', function () {
            $original = InputBuilder::make();
            $modified = $original->audio([
                'file' => '/path/to/audio.mp3',
                'action' => 'transcribe',
            ]);

            expect($original->toArray())->toBe([]);
            expect($modified->toArray())->toHaveKey('audio');
            expect($original)->not->toBe($modified);
        });
    });

    describe('audioInput()', function () {
        it('adds audio input configuration for chat context', function () {
            $config = [
                'file' => '/path/to/audio.mp3',
                'format' => 'mp3',
            ];

            $builder = InputBuilder::make()->audioInput($config);

            expect($builder->toArray())->toBe([
                'audio_input' => $config,
            ]);
        });

        it('validates file parameter is required', function () {
            expect(fn () => InputBuilder::make()->audioInput([
                'format' => 'mp3',
            ]))->toThrow(InvalidArgumentException::class, 'Audio input requires a "file" parameter.');
        });

        it('is immutable and returns a new instance', function () {
            $original = InputBuilder::make();
            $modified = $original->audioInput(['file' => '/path/to/audio.mp3']);

            expect($original->toArray())->toBe([]);
            expect($modified->toArray())->toHaveKey('audio_input');
            expect($original)->not->toBe($modified);
        });
    });

    describe('image()', function () {
        it('adds image configuration for generation', function () {
            $config = [
                'prompt' => 'A beautiful sunset',
                'size' => '1024x1024',
                'quality' => 'hd',
            ];

            $builder = InputBuilder::make()->image($config);

            expect($builder->toArray())->toBe([
                'image' => $config,
            ]);
        });

        it('adds image configuration for editing', function () {
            $config = [
                'image' => '/path/to/image.png',
                'prompt' => 'Add a rainbow',
                'mask' => '/path/to/mask.png',
            ];

            $builder = InputBuilder::make()->image($config);

            expect($builder->toArray())->toBe([
                'image' => $config,
            ]);
        });

        it('adds image configuration for variation', function () {
            $config = [
                'image' => '/path/to/image.png',
                'n' => 2,
            ];

            $builder = InputBuilder::make()->image($config);

            expect($builder->toArray())->toBe([
                'image' => $config,
            ]);
        });

        it('validates at least prompt or image is required', function () {
            expect(fn () => InputBuilder::make()->image([
                'size' => '1024x1024',
            ]))->toThrow(InvalidArgumentException::class, 'Image configuration requires at least a "prompt" or "image" parameter.');
        });

        it('validates n must be integer between 1 and 10', function () {
            expect(fn () => InputBuilder::make()->image([
                'prompt' => 'Test',
                'n' => 0,
            ]))->toThrow(InvalidArgumentException::class, 'Number of images (n) must be an integer between 1 and 10');

            expect(fn () => InputBuilder::make()->image([
                'prompt' => 'Test',
                'n' => 11,
            ]))->toThrow(InvalidArgumentException::class, 'Number of images (n) must be an integer between 1 and 10');
        });

        it('validates quality must be standard or hd', function () {
            expect(fn () => InputBuilder::make()->image([
                'prompt' => 'Test',
                'quality' => 'invalid',
            ]))->toThrow(InvalidArgumentException::class, 'Quality must be either "standard" or "hd"');
        });

        it('accepts valid quality values', function () {
            $builder1 = InputBuilder::make()->image([
                'prompt' => 'Test',
                'quality' => 'standard',
            ]);
            expect($builder1->toArray()['image']['quality'])->toBe('standard');

            $builder2 = InputBuilder::make()->image([
                'prompt' => 'Test',
                'quality' => 'hd',
            ]);
            expect($builder2->toArray()['image']['quality'])->toBe('hd');
        });

        it('validates style must be vivid or natural', function () {
            expect(fn () => InputBuilder::make()->image([
                'prompt' => 'Test',
                'style' => 'invalid',
            ]))->toThrow(InvalidArgumentException::class, 'Style must be either "vivid" or "natural"');
        });

        it('accepts valid style values', function () {
            $builder1 = InputBuilder::make()->image([
                'prompt' => 'Test',
                'style' => 'vivid',
            ]);
            expect($builder1->toArray()['image']['style'])->toBe('vivid');

            $builder2 = InputBuilder::make()->image([
                'prompt' => 'Test',
                'style' => 'natural',
            ]);
            expect($builder2->toArray()['image']['style'])->toBe('natural');
        });

        it('validates size must be valid', function () {
            expect(fn () => InputBuilder::make()->image([
                'prompt' => 'Test',
                'size' => '999x999',
            ]))->toThrow(InvalidArgumentException::class, 'Invalid size. Must be one of: 256x256, 512x512, 1024x1024, 1792x1024, 1024x1792');
        });

        it('accepts valid size values', function () {
            $validSizes = ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'];

            foreach ($validSizes as $size) {
                $builder = InputBuilder::make()->image([
                    'prompt' => 'Test',
                    'size' => $size,
                ]);

                expect($builder->toArray()['image']['size'])->toBe($size);
            }
        });

        it('is immutable and returns a new instance', function () {
            $original = InputBuilder::make();
            $modified = $original->image([
                'prompt' => 'A beautiful sunset',
            ]);

            expect($original->toArray())->toBe([]);
            expect($modified->toArray())->toHaveKey('image');
            expect($original)->not->toBe($modified);
        });
    });

    describe('fluent API', function () {
        it('can chain multiple methods', function () {
            $builder = InputBuilder::make()
                ->message('Describe this image')
                ->image(['prompt' => 'A sunset'])
                ->audio([
                    'file' => '/path/to/audio.mp3',
                    'action' => 'transcribe',
                ]);

            $result = $builder->toArray();

            expect($result)->toHaveKeys(['message', 'image', 'audio']);
            expect($result['message'])->toBe('Describe this image');
            expect($result['image']['prompt'])->toBe('A sunset');
            expect($result['audio']['file'])->toBe('/path/to/audio.mp3');
        });

        it('each method returns a new instance maintaining immutability', function () {
            $step1 = InputBuilder::make();
            $step2 = $step1->message('Hello');
            $step3 = $step2->audio(['file' => 'audio.mp3', 'action' => 'transcribe']);
            $step4 = $step3->image(['prompt' => 'Test']);

            expect($step1->toArray())->toBe([]);
            expect($step2->toArray())->toHaveKey('message');
            expect($step2->toArray())->not->toHaveKey('audio');
            expect($step3->toArray())->toHaveKeys(['message', 'audio']);
            expect($step3->toArray())->not->toHaveKey('image');
            expect($step4->toArray())->toHaveKeys(['message', 'audio', 'image']);
        });
    });

    describe('toArray()', function () {
        it('returns all configured data', function () {
            $builder = InputBuilder::make()
                ->message('Test message')
                ->audio([
                    'file' => '/path/to/audio.mp3',
                    'action' => 'transcribe',
                    'language' => 'en',
                ])
                ->image([
                    'prompt' => 'A sunset',
                    'size' => '1024x1024',
                ]);

            $result = $builder->toArray();

            expect($result)->toBe([
                'message' => 'Test message',
                'audio' => [
                    'file' => '/path/to/audio.mp3',
                    'action' => 'transcribe',
                    'language' => 'en',
                ],
                'image' => [
                    'prompt' => 'A sunset',
                    'size' => '1024x1024',
                ],
            ]);
        });
    });
});
