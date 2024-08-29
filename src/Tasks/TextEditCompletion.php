<?php

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Contract\TextEditCompletionContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use OpenAI\Client;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class TextEditCompletion implements TextEditCompletionContract
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? AppConfig::openAiClient();
    }

    public function __invoke(array $payload): string
    {
        try {
            $model = config('ai-assistant.chat_model');
            
            // If the model is GPT-4 compatible, use the chat completion endpoint
            if (str_starts_with($model, 'gpt-4') || $model === 'gpt-3.5-turbo') {
                $messages = [
                    ['role' => config('ai-assistant.ai_role', 'assistant'), 'content' => 'You are a helpful assistant that improves text.'],
                    ['role' => config('ai-assistant.user_role', 'user'), 'content' => "Please improve the following text: {$payload['input']}"],
                ];

                $response = $this->client->chat()->create([
                    'model' => $model,
                    'messages' => $messages,
                ]);

                return trim($response->choices[0]->message->content);
            } else {
                // Fall back to the original edit completion for other models
                return trim($this->client->edits()->create($payload)->choices[0]->text);
            }
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }
}
