<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Http\Controllers\WebhookController;
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;
use CreativeCrafts\LaravelAiAssistant\Tests\DataFactories\WebhooksFactory;
use Illuminate\Support\Facades\Event;
use CreativeCrafts\LaravelAiAssistant\Events\ResponseCompleted;
use CreativeCrafts\LaravelAiAssistant\Events\ResponseFailed;
use CreativeCrafts\LaravelAiAssistant\Events\ToolCallRequested;
use Illuminate\Http\Request;

beforeEach(function () {
    // Ensure webhooks enabled and secret configured
    config()->set('ai-assistant.webhooks.enabled', true);
    config()->set('ai-assistant.webhooks.signing_secret', 'secret');
    config()->set('ai-assistant.webhooks.signature_header', 'X-OpenAI-Signature');
});

it('processes response.completed webhook and stores status idempotently', function () {
    $controller = app(WebhookController::class);
    $store = app(ResponseStatusStore::class);
    Event::fake();

    $payload = WebhooksFactory::completed();
    $json = json_encode($payload, JSON_UNESCAPED_SLASHES);
    $sig = 'sha256=' . hash_hmac('sha256', (string)$json, (string)config('ai-assistant.webhooks.signing_secret'));

    $req = Request::create('/ai-assistant/webhook', 'POST', [], [], [], [
        'HTTP_X-OPENAI-SIGNATURE' => $sig,
    ], $json);

    $resp = $controller->handle($req);
    expect($resp->status())->toBe(200);

    $status = $store->getStatus($payload['response']['id']);
    expect($status['status'] ?? null)->toBe('completed');

    Event::assertDispatched(ResponseCompleted::class);

    // Call again to validate idempotent overwrite behavior (no errors)
    $resp2 = $controller->handle($req);
    expect($resp2->status())->toBe(200);
});

it('processes response.failed and tool_call requested events', function () {
    $controller = app(WebhookController::class);
    Event::fake();

    $failed = WebhooksFactory::failed(null, 'boom');
    $jsonF = json_encode($failed, JSON_UNESCAPED_SLASHES);
    $sigF = 'sha256=' . hash_hmac('sha256', (string)$jsonF, (string)config('ai-assistant.webhooks.signing_secret'));
    $reqF = Request::create('/ai-assistant/webhook', 'POST', [], [], [], ['HTTP_X-OPENAI-SIGNATURE' => $sigF], $jsonF);
    $controller->handle($reqF);
    Event::assertDispatched(ResponseFailed::class);

    $tool = WebhooksFactory::toolCallRequested();
    $jsonT = json_encode($tool, JSON_UNESCAPED_SLASHES);
    $sigT = 'sha256=' . hash_hmac('sha256', (string)$jsonT, (string)config('ai-assistant.webhooks.signing_secret'));
    $reqT = Request::create('/ai-assistant/webhook', 'POST', [], [], [], ['HTTP_X-OPENAI-SIGNATURE' => $sigT], $jsonT);
    $controller->handle($reqT);
    Event::assertDispatched(ToolCallRequested::class);
});
