<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Http\Responses\StreamedAiResponse;

class StreamingController extends Controller
{
    public function __invoke(Request $request)
    {
        $prompt = $request->string('q')->toString() ?: 'Say hello and count to 10.';
        $gen = Ai::stream($prompt);
        return StreamedAiResponse::fromGenerator($gen);
    }
}
