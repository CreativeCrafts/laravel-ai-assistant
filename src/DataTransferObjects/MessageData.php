<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

use CreativeCrafts\LaravelAiAssistant\Contracts\MessageDataContract;

final readonly class MessageData implements MessageDataContract
{
    public function __construct(
        protected string|array $message,
        protected string $role = 'user',
        protected string $toolCallId = '',
    ) {
    }

    /**
     * Convert the MessageData object to an array representation.
     *
     * This method creates an array containing the role and content of the message.
     * If a tool call ID is present, it's also included in the array.
     *
     * @return array An associative array containing the message data:
     *               - 'role': The role of the message sender (e.g., 'user', 'assistant')
     *               - 'content': The content of the message
     *               - 'tool_call_id': (Optional) The ID of the tool call, if applicable
     */
    public function toArray(): array
    {
        return array_merge(
            [
                'role' => $this->role,
                'content' => $this->message,
            ],
            $this->toolCallId !== '' ? [
                'tool_call_id' => $this->toolCallId,
            ] : []
        );
    }
}
