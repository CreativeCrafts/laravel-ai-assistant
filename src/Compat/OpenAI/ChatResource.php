<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI;

use CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Chat\CreateResponse as ChatResponse;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

readonly class ChatResource
{
    public function __construct(private OpenAITransport $transport)
    {
    }


    public function create(array $parameters): ChatResponse
    {
        $data = $this->transport->postJson('/v1/chat/completions', $parameters, idempotent: true);

        $response = new ChatResponse();
        $choices = [];
        foreach (($data['choices'] ?? []) as $c) {
            $msg = is_array($c['message'] ?? null) ? $c['message'] : [];

            $message = (object)[
                'role' => isset($msg['role']) ? (string)$msg['role'] : 'assistant',
            ];

            if (array_key_exists('content', $msg)) {
                // Preserve array-form content (multimodal) or cast to string when scalar
                $message->content = is_array($msg['content']) ? $msg['content'] : (string)$msg['content'];
            } else {
                $message->content = '';
            }

            // Include additional fields when present for richer compatibility
            if (isset($msg['tool_calls'])) {
                $message->tool_calls = $msg['tool_calls'];
            }
            if (isset($msg['function_call'])) {
                $message->function_call = $msg['function_call'];
            }
            if (isset($msg['refusal'])) {
                $message->refusal = (string)$msg['refusal'];
            }

            $choices[] = (object)[
                'message' => $message,
                'finish_reason' => $c['finish_reason'] ?? null,
            ];
        }
        $response->choices = $choices;
        return $response;
    }

    public function createStreamed(array $parameters): iterable
    {
        return $this->transport->streamSse('/v1/chat/completions', $parameters + ['stream' => true], idempotent: true);
    }
}
