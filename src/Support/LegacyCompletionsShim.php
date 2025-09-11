<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatMessageData;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CustomFunctionData;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileOperationException;
use CreativeCrafts\LaravelAiAssistant\Services\AssistantService;
use InvalidArgumentException;

/**
 * @deprecated Use Support\LegacyCompletions instead. This shim will be removed in the next minor release.

 * @internal
 * @deprecated Will be removed in the next major release. Use Responses API methods on AiAssistant instead.
 */
trait LegacyCompletionsShim
{
    /**
     * @deprecated since 1.8.0 Use AssistantService::textCompletion() instead.
     */
    public function draft(): string
    {
        $this->textGeneratorConfig['prompt'] = $this->prompt;
        return $this->processTextCompletion();
    }

    /**
     * @deprecated since 1.8.0 Use AssistantService::textCompletion() instead.
     */
    public function translateTo(string $language): string
    {
        $this->textGeneratorConfig['prompt'] = "Translate this text: \"{$this->prompt}\" to {$language}";
        return $this->processTextCompletion();
    }

    /**
     * @deprecated since 1.8.0 Use AiAssistant::reply()/sendChatMessage() instead.
     */
    public function andRespond(): array
    {
        $this->chatTextGeneratorConfig['messages'] = (new ChatMessageData($this->prompt))->messages();
        return $this->processChatTextCompletion();
    }

    /**
     * @deprecated since 1.8.0 Use typed tool registration + reply() instead.
     */
    public function withCustomFunction(CustomFunctionData $customFunctionData): array
    {
        $this->chatTextGeneratorConfig['messages'] = (new ChatMessageData($this->prompt))->messages();
        $this->chatTextGeneratorConfig['functions'] = $customFunctionData->toArray();
        return $this->processChatTextCompletion();
    }

    /**
     * @deprecated Use processTextEditCompletion() via Responses or external edit flow.
     */
    public function spellingAndGrammarCorrection(): string
    {
        $instructions = 'Fix the spelling and grammar errors in the following text.';
        return $this->processTextEditCompletion($instructions);
    }

    /**
     * @deprecated Use processTextEditCompletion() via Responses or external edit flow.
     */
    public function improveWriting(): string
    {
        $instructions = 'Edit the following text to make it more readable.';
        return $this->processTextEditCompletion($instructions);
    }

    /**
     * @deprecated Legacy audio transcription entry point. Prefer Responses API alternatives.
     */
    public function transcribeTo(string $language, ?string $optionalText = ''): string
    {
        // Validate and sanitize file path
        $this->validateAudioFilePath($this->prompt);

        // Open file with proper error handling
        $fileResource = $this->openAudioFile($this->prompt);

        try {
            $this->audioToTextGeneratorConfig['file'] = $fileResource;
            $this->audioToTextGeneratorConfig['language'] = $language;
            $this->audioToTextGeneratorConfig['prompt'] = $optionalText;
            $this->client = $this->client ?? resolve(AssistantService::class);
            return $this->client->transcribeTo($this->audioToTextGeneratorConfig);
        } finally {
            // Always close the file resource to prevent resource leaks
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
        }
    }

    /**
     * @deprecated Legacy translation entry point. Prefer Responses API alternatives.
     */
    public function translateAudioTo(): string
    {
        // Validate and sanitize file path
        $this->validateAudioFilePath($this->prompt);

        // Open file with proper error handling
        $fileResource = $this->openAudioFile($this->prompt);

        try {
            $this->audioToTextGeneratorConfig['file'] = $fileResource;
            $this->client = $this->client ?? resolve(AssistantService::class);
            return $this->client->translateTo($this->audioToTextGeneratorConfig);
        } finally {
            // Always close the file resource to prevent resource leaks
            if (is_resource($fileResource)) {
                fclose($fileResource);
            }
        }
    }

    /**
     * @deprecated since 1.8.0 Use AssistantService::textCompletion() instead.
     */
    protected function processTextCompletion(): string
    {
        trigger_error('The processTextCompletion() method is deprecated. Use AssistantService::textCompletion() instead.', E_USER_DEPRECATED);
        if (isset($this->textGeneratorConfig['stream']) && $this->textGeneratorConfig['stream']) {
            return $this->client->streamedCompletion($this->textGeneratorConfig);
        }
        return $this->client->textCompletion($this->textGeneratorConfig);
    }

    /**
     * @deprecated since 1.8.0 Use AssistantService::chatTextCompletion() or sendChatMessage() instead.
     */
    protected function processChatTextCompletion(): array
    {
        trigger_error('The processChatTextCompletion() method is deprecated. Use AssistantService::chatTextCompletion() or sendChatMessage() instead.', E_USER_DEPRECATED);
        if (isset($this->chatTextGeneratorConfig['stream']) && $this->chatTextGeneratorConfig['stream']) {
            $response = $this->client->streamedChat($this->chatTextGeneratorConfig);
        } else {
            $response = $this->client->chatTextCompletion($this->chatTextGeneratorConfig);
        }
        (new ChatMessageData($this->prompt))->cacheConversation($response);
        return $response;
    }

    /**
     * @deprecated Legacy text edit flow. Prefer Responses API structured outputs.
     * @throws ApiResponseValidationException
     */
    protected function processTextEditCompletion(string $instructions): string
    {
        if ($this->editTextGeneratorConfig['model'] === 'gpt-3.5-turbo' || str_starts_with($this->editTextGeneratorConfig['model'], 'gpt-4')) {
            $this->chatTextGeneratorConfig['messages'] = (new ChatMessageData($this->prompt))
                ->setAssistantInstructions($instructions);
            $response = $this->processChatTextCompletion();

            // Validate response structure before accessing nested arrays
            if (!is_array($response) || !isset($response['choices']) || !is_array($response['choices'])) {
                throw new ApiResponseValidationException('Invalid API response structure: missing or invalid choices array.');
            }

            if (empty($response['choices'])) {
                throw new ApiResponseValidationException('Invalid API response: no choices returned.');
            }

            $firstChoice = $response['choices'][0] ?? null;
            if (!is_array($firstChoice) || !isset($firstChoice['message']) || !is_array($firstChoice['message'])) {
                throw new ApiResponseValidationException('Invalid choice structure: missing or invalid message array.');
            }

            if (!isset($firstChoice['message']['content']) || !is_string($firstChoice['message']['content'])) {
                throw new ApiResponseValidationException('Invalid message structure: missing or invalid content string.');
            }

            return $firstChoice['message']['content'];
        }
        // @pest-mutate-ignore
        $this->textGeneratorConfig['prompt'] = $instructions . "\n\nText to edit: " . $this->prompt;
        return $this->processTextCompletion();
    }

    /**
     * @deprecated Support function for legacy audio methods.
     * @throws InvalidArgumentException|FileOperationException
     */
    private function validateAudioFilePath(string $filePath): void
    {
        // Check if file path is empty
        if (trim($filePath) === '') {
            throw new InvalidArgumentException('Audio file path cannot be empty.');
        }

        // Handle virtual file system (vfs://) paths for testing
        $isVirtualPath = str_starts_with($filePath, 'vfs://');

        if ($isVirtualPath) {
            // For virtual paths, use direct file functions without realpath
            if (!file_exists($filePath)) {
                throw new FileOperationException("Audio file does not exist: {$filePath}");
            }

            if (!is_file($filePath)) {
                throw new FileOperationException("Path is not a file: {$filePath}");
            }

            if (!is_readable($filePath)) {
                throw new FileOperationException("Audio file is not readable: {$filePath}");
            }

            $pathForExtension = $filePath;
            $pathForSize = $filePath;
        } else {
            // For real paths, use realpath for security (prevent directory traversal)
            $realPath = realpath($filePath);
            if ($realPath === false) {
                throw new FileOperationException("Audio file does not exist: {$filePath}");
            }

            // Check if it's actually a file (not a directory)
            if (!is_file($realPath)) {
                throw new FileOperationException("Path is not a file: {$filePath}");
            }

            // Check file permissions
            if (!is_readable($realPath)) {
                throw new FileOperationException("Audio file is not readable: {$filePath}");
            }

            $pathForExtension = $realPath;
            $pathForSize = $realPath;
        }

        // Validate file extension against whitelist
        $allowedExtensions = [
            'mp3', 'mp4', 'mpeg', 'mpga', 'm4a', 'wav', 'webm'
        ];

        $fileExtension = strtolower(pathinfo($pathForExtension, PATHINFO_EXTENSION));
        if (!in_array($fileExtension, $allowedExtensions, true)) {
            throw new FileOperationException(
                "Invalid audio file type '{$fileExtension}'. Allowed types: " . implode(', ', $allowedExtensions)
            );
        }

        // Check file size (max 25MB as per OpenAI limits)
        $maxFileSize = 25 * 1024 * 1024; // 25MB in bytes
        $fileSize = filesize($pathForSize);
        if ($fileSize === false || $fileSize > $maxFileSize) {
            throw new FileOperationException("Audio file is too large. Maximum size is 25MB.");
        }

        if ($fileSize === 0) {
            throw new FileOperationException("Audio file is empty: {$filePath}");
        }
    }

    /**
     * @deprecated Support function for legacy audio methods.
     */
    private function openAudioFile(string $filePath): mixed
    {
        $file = @fopen($filePath, 'rb');
        if ($file === false) {
            throw new FileOperationException("Failed to open audio file: {$filePath}");
        }
        return $file;
    }
}
