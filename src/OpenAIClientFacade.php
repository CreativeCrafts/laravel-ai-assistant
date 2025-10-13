<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use CreativeCrafts\LaravelAiAssistant\Contracts\ConversationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesInputItemsRepositoryContract;

/**
 * High-level facade that unifies access to Responses, Conversations, and Files.
 *
 * @deprecated Since v3.0. Use Ai facade methods instead.
 *             - For responses: Use Ai::responses()
 *             - For conversations: Use Ai::conversations()
 *             This class will be removed in v4.0.
 *
 * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::responses()
 * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::conversations()
 */
final readonly class OpenAIClientFacade
{
    public function __construct(
        private ResponsesRepositoryContract $responses,
        private ConversationsRepositoryContract $conversations,
        private FilesRepositoryContract $files,
        private ?ResponsesInputItemsRepositoryContract $responsesInputItems = null,
    ) {
    }

    /**
     * Access the Responses repository.
     *
     * @deprecated Since v3.0. Use Ai::responses() instead. Will be removed in v4.0.
     * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::responses()
     */
    public function responses(): ResponsesRepositoryContract
    {
        trigger_deprecation(
            'creativecrafts/laravel-ai-assistant',
            '3.0',
            'The "%s" method is deprecated. Use Ai::responses() instead.',
            __METHOD__
        );

        return $this->responses;
    }

    /**
     * Access the Conversations repository.
     *
     * @deprecated Since v3.0. Use Ai::conversations() instead. Will be removed in v4.0.
     * @see \CreativeCrafts\LaravelAiAssistant\Facades\Ai::conversations()
     */
    public function conversations(): ConversationsRepositoryContract
    {
        trigger_deprecation(
            'creativecrafts/laravel-ai-assistant',
            '3.0',
            'The "%s" method is deprecated. Use Ai::conversations() instead.',
            __METHOD__
        );

        return $this->conversations;
    }

    /**
     * Access the Files repository.
     *
     * @deprecated Since v3.0. Use Ai facade methods instead. Will be removed in v4.0.
     * @see Facades\Ai
     */
    public function files(): FilesRepositoryContract
    {
        trigger_deprecation(
            'creativecrafts/laravel-ai-assistant',
            '3.0',
            'The "%s" method is deprecated. Use Ai facade methods instead.',
            __METHOD__
        );

        return $this->files;
    }

    /**
     * Access the Responses Input Items repository.
     *
     * @deprecated Since v3.0. Use Ai facade methods instead. Will be removed in v4.0.
     * @see Facades\Ai
     */
    public function responsesInputItems(): ResponsesInputItemsRepositoryContract
    {
        trigger_deprecation(
            'creativecrafts/laravel-ai-assistant',
            '3.0',
            'The "%s" method is deprecated. Use Ai facade methods instead.',
            __METHOD__
        );

        return $this->responsesInputItems ?? app(ResponsesInputItemsRepositoryContract::class);
    }
}
