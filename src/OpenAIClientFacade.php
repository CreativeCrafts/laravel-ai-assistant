<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

/**
 * High-level facade that unifies access to Responses, Conversations, Files,
 * and optionally legacy Chat/Completions/Audio operations for compatibility.
 */
final readonly class OpenAIClientFacade
{
    public function __construct(
        private ResponsesRepositoryContract $responses,
        private ConversationsRepositoryContract $conversations,
        private FilesRepositoryContract $files,
        private OpenAiRepositoryContract $legacyChat
    ) {
    }

    /**
     * Access the Responses repository.
     */
    public function responses(): ResponsesRepositoryContract
    {
        return $this->responses;
    }

    /**
     * Access the Conversations repository.
     */
    public function conversations(): ConversationsRepositoryContract
    {
        return $this->conversations;
    }

    /**
     * Access the Files repository.
     */
    public function files(): FilesRepositoryContract
    {
        return $this->files;
    }

    /**
     * Access legacy Chat/Completions/Audio operations.
     * Note: Threads/Runs/Assistants methods on this object are deprecated.
     */
    public function chat(): OpenAiRepositoryContract
    {
        return $this->legacyChat;
    }
}
