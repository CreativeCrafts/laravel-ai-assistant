<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Http\Controllers\WebhookController;
use Illuminate\Http\Request;

beforeEach(function () {
    config()->set('ai-assistant.webhooks.enabled', true);
    config()->set('ai-assistant.webhooks.signing_secret', 'secret');
    config()->set('ai-assistant.webhooks.signature_header', 'X-OpenAI-Signature');
});

it('rejects invalid signature with 401', function () {
    $controller = app(WebhookController::class);

    $payload = ['type' => 'response.completed', 'response' => ['id' => 'resp_123']];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

    // wrong signature
    $sig = 'sha256=' . hash_hmac('sha256', (string)$json . 'tampered', (string)config('ai-assistant.webhooks.signing_secret'));

    $req = Request::create('/ai-assistant/webhook', 'POST', [], [], [], [
        'HTTP_X-OPENAI-SIGNATURE' => $sig,
    ], $json);

    $resp = $controller->handle($req);
    expect($resp->status())->toBe(401);
});

it('rejects when signature header is missing with 400', function () {
    $controller = app(WebhookController::class);

    $payload = ['type' => 'response.completed', 'response' => ['id' => 'resp_456']];
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);

    $req = Request::create('/ai-assistant/webhook', 'POST', [], [], [], [], $json);

    $resp = $controller->handle($req);
    expect($resp->status())->toBe(400);
});
