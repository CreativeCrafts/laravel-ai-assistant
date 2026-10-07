<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Repositories\Http\Admin;

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AbstractHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

/**
 * Base for endpoints that require an OpenAI Admin API key (Administration API, fine-tuning
 * checkpoint permissions). Requests are signed with ai-assistant.admin_api_key when configured,
 * otherwise with the regular API key.
 *
 * @internal
 */
abstract readonly class AbstractAdminHttpRepository extends AbstractHttpRepository
{
    public function __construct(
        OpenAITransport $transport,
        protected ?string $adminApiKey = null,
        string $basePath = '/v1'
    ) {
        parent::__construct($transport, $basePath);
    }

    protected function headers(): array
    {
        if ($this->adminApiKey === null || $this->adminApiKey === '') {
            return [];
        }

        return ['Authorization' => 'Bearer ' . $this->adminApiKey];
    }
}
