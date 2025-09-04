<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Compat\OpenAI\Responses\Meta;

final class MetaInformation
{
    /** @var array<string,mixed> */
    private array $headers;

    private function __construct(array $headers)
    {
        $this->headers = $headers;
    }

    /**
    * Create a MetaInformation instance from headers/metadata.
    * @param array<string,mixed> $headers
    */
    public static function from(array $headers): self
    {
        return new self($headers);
    }

    /**
    * @return array<string,mixed>
    */
    public function headers(): array
    {
        return $this->headers;
    }
}
