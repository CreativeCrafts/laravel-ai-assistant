<?php

namespace CreativeCrafts\LaravelAiAssistant\Tasks;

use CreativeCrafts\LaravelAiAssistant\AppConfig;
use CreativeCrafts\LaravelAiAssistant\Contract\TextCompletionContract;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use OpenAI\Client;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

final class TextCompletion implements TextCompletionContract
{
    protected Client $client;

    public function __construct(?Client $client = null)
    {
        $this->client = $client ?? AppConfig::openAiClient();
    }

    public function __invoke(array $payload): string
    {
        if ($payload['stream']) {
            return $this->streamedCompletion($payload);
        }

        return $this->textCompletion($payload);
    }

    public function textCompletion(array $payload): string
    {
        try {
            return trim($this->client->completions()->create($payload)->choices[0]->text);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }

    public function streamedCompletion(array $payload): string
    {
        try {
            $streamResponses = $this->client->completions()->createStreamed($payload);

            foreach ($streamResponses as $response) {
                /** @var Response $response */
                if (isset($response->choices[0]->text)) {
                    return $response->choices[0]->text;
                }
            }

            return '';
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }
}
