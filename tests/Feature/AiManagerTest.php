<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\Feature;

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use PHPUnit\Framework\TestCase;
use Generator;

final class AiManagerTest extends TestCase
{
    public function test_quick_returns_chat_response_dto(): void
    {
        $dto = Ai::quick('Hello world');
        $this->assertInstanceOf(ChatResponseDto::class, $dto);
        $this->assertNotSame('', $dto->text ?? '');
    }

    public function test_stream_returns_generator(): void
    {
        $gen = Ai::stream('Hello');
        $this->assertInstanceOf(Generator::class, $gen);
    }
}
