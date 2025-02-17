<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

use InvalidArgumentException;

interface ChatAssistantMessageDataFactoryContract
{
    /**
     * Builds and returns a ChatAssistantMessageData object with the provided parameters.
     *
     * This method validates the input parameters and constructs a ChatAssistantMessageData
     * object, which implements the ChatAssistantMessageDataContract.
     *
     * @param string|array|null $content The content of the assistant's message.
     * @param string|null $refusal An optional refusal message.
     * @param string|null $name An optional name for the assistant.
     * @param array|null $audio An optional array containing audio data. Must include an 'id' key if provided.
     * @param array|null $toolCalls An optional array containing tool call data. Must include 'id', 'type', and 'function' keys if provided.
     *
     * @throws InvalidArgumentException If the audio array is provided without an 'id' key,
     *                                  or if the toolCalls array is missing required fields.
     *
     * @return ChatAssistantMessageDataContract A data transfer object implementing the ChatAssistantMessageDataContract.
     */
    public static function buildChatAssistantMessageData(string|array|null $content, ?string $refusal, ?string $name, ?array $audio, ?array $toolCalls): ChatAssistantMessageDataContract;
}
