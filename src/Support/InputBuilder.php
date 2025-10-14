<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use Illuminate\Support\Facades\Validator;
use InvalidArgumentException;
use JsonException;

/**
 * Builder for unified input requests that can be routed to different OpenAI endpoints.
 * Supports audio, image, and text inputs with validation.
 *
 * This builder follows the mutable fluent pattern:
 * - All methods modify internal state ($this->data) and return $this
 * - No cloning is performed; the same instance is modified throughout the chain
 * - This ensures parent-child builder communication works correctly
 *
 * @see ResponsesBuilder::input() Returns this builder for method chaining
 */
final class InputBuilder
{
    /**
     * @param array<string,mixed> $data
     * @param ResponsesBuilder|null $parent
     */
    private function __construct(
        private array $data = [],
        private ?ResponsesBuilder $parent = null
    ) {
    }

    public static function make(?ResponsesBuilder $parent = null): self
    {
        return new self(parent: $parent);
    }

    /**
     * Add a text message to the unified request.
     */
    public function message(string $text): self
    {
        $this->data['message'] = $text;
        return $this;
    }

    /**
     * Add audio configuration to the unified request.
     * Supports transcription, translation, and speech generation.
     *
     * @param array<string,mixed> $config Audio configuration with keys:
     *   - file: string (path to audio file for transcription/translation)
     *   - action: string (transcribe|translate|speech)
     *   - text: string (text for speech generation)
     *   - model: string (optional, e.g., whisper-1, tts-1)
     *   - voice: string (optional for speech: alloy|echo|fable|onyx|nova|shimmer)
     *   - language: string (optional for transcription)
     *   - prompt: string (optional for transcription)
     *   - response_format: string (optional: json|text|srt|verbose_json|vtt)
     *   - temperature: float (optional, 0-1)
     *   - speed: float (optional for speech, 0.25-4.0)
     *   - format: string (optional for speech: mp3|opus|aac|flac|wav|pcm)
     */
    public function audio(array $config): self
    {
        $this->validateAudioConfig($config);

        $this->data['audio'] = $config;
        return $this;
    }

    /**
     * Add audio input in chat message context.
     * This is used when audio is part of a chat conversation.
     *
     * @param array<string,mixed> $config Audio input configuration with keys:
     *   - file: string (path to audio file)
     *   - format: string (optional)
     */
    public function audioInput(array $config): self
    {
        if (!isset($config['file'])) {
            throw new InvalidArgumentException('Audio input requires a "file" parameter.');
        }

        $this->data['audio_input'] = $config;
        return $this;
    }

    /**
     * Add image configuration to the unified request.
     * Supports generation, editing, and variations.
     *
     * @param array<string,mixed> $config Image configuration with keys:
     *   - prompt: string (text description for generation/editing)
     *   - image: string (path to image file for editing/variation)
     *   - mask: string (optional, path to mask image for editing)
     *   - model: string (optional, e.g., dall-e-3, dall-e-2)
     *   - n: int (optional, number of images to generate, 1-10)
     *   - size: string (optional: 256x256|512x512|1024x1024|1792x1024|1024x1792)
     *   - quality: string (optional: standard|hd)
     *   - style: string (optional: vivid|natural)
     *   - response_format: string (optional: url|b64_json)
     */
    public function image(array $config): self
    {
        $this->validateImageConfig($config);

        $this->data['image'] = $config;
        return $this;
    }

    /**
     * Get the unified request data as an array.
     *
     * @return array<string,mixed>
     */
    public function toArray(): array
    {
        return $this->data;
    }

    /**
     * Send the request through the parent ResponsesBuilder.
     * This allows method chaining like: Ai::responses()->input()->audio([...])->send()
     *
     * @return ChatResponseDto|ResponseDto
     * @throws InvalidArgumentException if no parent ResponsesBuilder is set
     * @throws JsonException
     */
    public function send(): ChatResponseDto|ResponseDto
    {
        if ($this->parent === null) {
            throw new InvalidArgumentException(
                'Cannot call send() on InputBuilder without a parent ResponsesBuilder. ' .
                'Use Ai::responses()->input()->...->send() instead of InputBuilder::make()->...->send()'
            );
        }

        return $this->parent->send();
    }

    /**
     * Add image input in the chat message context.
     * This is used when an image is part of a chat conversation, typically for vision models.
     * The image input must contain exactly one text item and one image item.
     *
     * @param array<string,mixed> $imageInput Image input configuration with structure:
     *   - role: string (required, must be 'user')
     *   - content: array (required, must contain exactly 2 items)
     *     - [0]: array with keys:
     *       - type: string (required, 'input_text')
     *       - text: string (required, the text prompt/question)
     *     - [1]: array with keys:
     *       - type: string (required, 'input_image')
     *       - image_url: string (required, fully qualified URL or base64-encoded image)
     * @return self Returns the current instance for method chaining
     * @throws InvalidArgumentException if the image input configuration is invalid
     * @see https://platform.openai.com/docs/api-reference/responses/create
     */
    public function imageInput(array $imageInput): self
    {
        $this->validateImageInput($imageInput);
        $this->data['input'] = $imageInput;
        return $this;
    }

    /**
     * Validate audio configuration based on the action type.
     *
     * @param array<string,mixed> $config
     */
    private function validateAudioConfig(array $config): void
    {
        $action = $config['action'] ?? null;

        if ($action === 'transcribe' || $action === 'translate') {
            if (!isset($config['file'])) {
                throw new InvalidArgumentException(
                    "Audio {$action} action requires a \"file\" parameter."
                );
            }
        } elseif ($action === 'speech') {
            if (!isset($config['text'])) {
                throw new InvalidArgumentException(
                    'Audio speech action requires a "text" parameter.'
                );
            }

            if (isset($config['voice'])) {
                $validVoices = ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'];
                if (!in_array($config['voice'], $validVoices, true)) {
                    throw new InvalidArgumentException(
                        'Invalid voice. Must be one of: ' . implode(', ', $validVoices)
                    );
                }
            }

            if (isset($config['speed'])) {
                if ($config['speed'] < 0.25 || $config['speed'] > 4.0) {
                    throw new InvalidArgumentException(
                        'Speed must be between 0.25 and 4.0'
                    );
                }
            }
        } elseif ($action !== null) {
            throw new InvalidArgumentException(
                'Invalid audio action. Must be one of: transcribe, translate, speech'
            );
        }

        if (isset($config['temperature'])) {
            if ($config['temperature'] < 0 || $config['temperature'] > 1) {
                throw new InvalidArgumentException(
                    'Temperature must be between 0 and 1'
                );
            }
        }
    }

    /**
     * Validate image configuration.
     *
     * @param array<string,mixed> $config
     */
    private function validateImageConfig(array $config): void
    {
        $hasPrompt = isset($config['prompt']);
        $hasImage = isset($config['image']);

        if (!$hasPrompt && !$hasImage) {
            throw new InvalidArgumentException(
                'Image configuration requires at least a "prompt" or "image" parameter.'
            );
        }

        if (isset($config['n'])) {
            if (!is_int($config['n']) || $config['n'] < 1 || $config['n'] > 10) {
                throw new InvalidArgumentException(
                    'Number of images (n) must be an integer between 1 and 10'
                );
            }
        }

        if (isset($config['quality']) && !in_array($config['quality'], ['standard', 'hd'], true)) {
            throw new InvalidArgumentException(
                'Quality must be either "standard" or "hd"'
            );
        }

        if (isset($config['style']) && !in_array($config['style'], ['vivid', 'natural'], true)) {
            throw new InvalidArgumentException(
                'Style must be either "vivid" or "natural"'
            );
        }

        if (isset($config['size'])) {
            $validSizes = ['256x256', '512x512', '1024x1024', '1792x1024', '1024x1792'];
            if (!in_array($config['size'], $validSizes, true)) {
                throw new InvalidArgumentException(
                    'Invalid size. Must be one of: ' . implode(', ', $validSizes)
                );
            }
        }
    }

    /**
     * Validate the image input configuration for the chat message context.
     * Ensures the image input array conforms to the required structure for vision models:
     * - Must have a role set to 'user'
     * - Must contain exactly 2 content items
     * - Must have exactly one 'input_text' type with valid text
     * - Must have exactly one 'input_image' type with valid image URL or base64 data
     *
     * @param array<string,mixed> $imageInput The image input configuration array containing:
     *   - Role: string (must be 'user')
     *   - content: array (must contain exactly 2 items)
     *     - [0]: array with 'type' => 'input_text' and 'text' => string
     *     - [1]: array with 'type' => 'input_image' and 'image_url' => string (URL or base64)
     * @return void
     * @throws InvalidArgumentException if validation fails with details about the first validation error
     * @see https://platform.openai.com/docs/api-reference/responses/create
     */
    private function validateImageInput(array $imageInput): void
    {
        $validator = Validator::make($imageInput, [
            'role' => ['required', 'string', 'in:user'],
            'content' => ['required', 'array', 'size:2'],
            'content.*' => ['required', 'array'],
            'content.*.type' => ['required', 'string', 'in:input_text,input_image'],
        ], [
            'content.size' => 'Content must contain exactly two items.',
        ]);

        $validator->after(function ($v) use ($imageInput) {
            /** @var array $items */
            $items = $imageInput['content'] ?? [];

            $types = array_column($items, 'type');
            $textCount = count(array_keys($types, 'input_text', true));
            $imageCount = count(array_keys($types, 'input_image', true));

            if ($textCount !== 1 || $imageCount !== 1) {
                $v->errors()->add('content', 'Content must have exactly one item of type "input_text" and one item of type "input_image".');
            }

            foreach ($items as $i => $item) {
                $type = $item['type'] ?? null;

                if ($type === 'input_text') {
                    $text = $item['text'] ?? null;
                    if (!is_string($text) || trim($text) === '') {
                        $v->errors()->add("content.$i.text", 'For type "input_text", text is required and must be a non-empty string.');
                    }
                }

                if ($type === 'input_image') {
                    $imageUrl = $item['image_url'] ?? null;
                    if (!is_string($imageUrl) || !$this->isValidImageUrlOrBase64($imageUrl)) {
                        $v->errors()->add("content.$i.image_url", 'For type "input_image", image_url must be a fully qualified URL or a base64-encoded image.');
                    }
                }
            }
        });
        if ($validator->fails()) {
            throw new InvalidArgumentException($validator->errors()->first());
        }
    }

    private function isValidImageUrlOrBase64(string $imageUrl): bool
    {
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return true;
        }

        if (preg_match('#^data:image/(?:png|jpe?g|gif|webp|bmp|x-icon|svg\+xml);base64,[A-Za-z0-9+/=\s]+$#', $imageUrl)) {
            $base64 = substr($imageUrl, strpos($imageUrl, ',') + 1);
            return base64_decode($base64, true) !== false;
        }

        $decoded = base64_decode($imageUrl, true);
        return $decoded !== false && $decoded !== '';
    }

}
