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

    public function __construct()
    {
        $this->client = AppConfig::openAiClient();
    }

    /**
     * @param array $payload
     * @return string
     */
    public function __invoke(array $payload): string
    {
        try {
            return trim($this->client->edits()->create($payload)->choices[0]->text);
        } catch (Throwable $e) {
            $errorCode = is_int($e->getCode()) ? $e->getCode() : Response::HTTP_INTERNAL_SERVER_ERROR;
            throw new InvalidApiKeyException($e->getMessage(), $errorCode);
        }
    }
}