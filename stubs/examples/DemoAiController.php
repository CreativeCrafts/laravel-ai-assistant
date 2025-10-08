<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Http\JsonResponse;

final class DemoAiController
{
    public function responsesExample(): JsonResponse
    {
        $builder = Ai::responses()->model('gpt-4o-mini');
        $builder->input()->appendUserText('Say hello from Responses!');
        $dto = $builder->send();

        return response()->json([
            'text' => $dto->text ?? $dto->content ?? null,
            'raw' => $dto->raw,
        ]);
    }

    public function conversationsExample(): JsonResponse
    {
        $conv = Ai::conversations();
        $conv->start();
        $conv->input()->appendUserText('Start a conversation. What is the time in UTC?');
        $dto = $conv->send();

        return response()->json([
            'conversation_id' => $dto->conversationId,
            'text' => $dto->text ?? $dto->content ?? null,
        ]);
    }
}
