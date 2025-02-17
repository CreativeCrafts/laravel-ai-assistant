<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataFactories;

use CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatAssistantMessageDataFactoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatAssistantMessageData;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

final class ChatAssistantMessageDataFactory implements ChatAssistantMessageDataFactoryContract
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
    public static function buildChatAssistantMessageData(string|array|null $content, ?string $refusal, ?string $name, ?array $audio, ?array $toolCalls): ChatAssistantMessageDataContract
    {
        if (is_array($audio) && ! isset($audio['id'])) {
            throw new InvalidArgumentException(
                message: 'Id for the previous audio response from the model is required',
                code: Response::HTTP_NOT_ACCEPTABLE
            );
        }

        if (is_array($toolCalls) && ! isset($toolCalls['id'], $toolCalls['type'], $toolCalls['function'])) {
            throw new InvalidArgumentException(
                message: 'Missing required fields for tool call',
                code: Response::HTTP_NOT_ACCEPTABLE
            );
        }

        if (is_array($toolCalls) && isset($toolCalls['function']) && ! isset($toolCalls['function']['arguments'], $toolCalls['function']['name'])) {
            throw new InvalidArgumentException(
                message: 'Missing required fields for tool call function',
                code: Response::HTTP_NOT_ACCEPTABLE
            );
        }

        return new ChatAssistantMessageData(
            role: 'assistant',
            content: $content,
            refusal: $refusal,
            name: $name,
            audio: $audio,
            toolCalls: $toolCalls,
        );
    }
}
