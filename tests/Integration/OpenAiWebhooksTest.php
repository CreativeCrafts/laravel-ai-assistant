<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;
use CreativeCrafts\LaravelAiAssistant\Events\ResponseCompleted;
use CreativeCrafts\LaravelAiAssistant\Http\Controllers\WebhookController;
use CreativeCrafts\LaravelAiAssistant\Http\Middleware\VerifyAiWebhookSignature;
use CreativeCrafts\LaravelAiAssistant\Services\ResponseStatusStore;
use CreativeCrafts\LaravelAiAssistant\Support\StandardWebhookSignature;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->secret = 'whsec_' . base64_encode('openai-webhook-signing-secret');
    config()->set('ai-assistant.webhooks.enabled', true);
    config()->set('ai-assistant.webhooks.signing_secret', $this->secret);
});

/**
 * Build a request signed the way OpenAI signs webhook deliveries.
 *
 * @param array<string, mixed> $event
 */
function openAiWebhookRequest(array $event, string $secret, ?int $timestamp = null, ?string $signature = null): Request
{
    $body = (string)json_encode($event, JSON_UNESCAPED_SLASHES);
    $id = 'wh_' . bin2hex(random_bytes(6));
    $timestamp = (string)($timestamp ?? time());
    $signature ??= 'v1,' . StandardWebhookSignature::sign($body, $id, $timestamp, $secret);

    return Request::create('/ai-assistant/webhook', 'POST', [], [], [], [
        'HTTP_WEBHOOK_ID' => $id,
        'HTTP_WEBHOOK_TIMESTAMP' => $timestamp,
        'HTTP_WEBHOOK_SIGNATURE' => $signature,
        'CONTENT_TYPE' => 'application/json',
    ], $body);
}

it('verifies OpenAI response.completed events and stores the response status', function () {
    Event::fake();

    $event = [
        'id' => 'evt_123',
        'object' => 'event',
        'created_at' => time(),
        'type' => 'response.completed',
        'data' => ['id' => 'resp_abc'],
    ];

    $response = app(WebhookController::class)->handle(openAiWebhookRequest($event, $this->secret));

    expect($response->status())->toBe(200)
        ->and(app(ResponseStatusStore::class)->getStatus('resp_abc')['status'] ?? null)->toBe('completed');
    Event::assertDispatched(ResponseCompleted::class, fn (ResponseCompleted $e) => $e->responseId === 'resp_abc');
    Event::assertDispatched(OpenAiWebhookReceived::class, fn (OpenAiWebhookReceived $e) => $e->type === 'response.completed'
        && $e->eventId === 'evt_123'
        && $e->data === ['id' => 'resp_abc']);
});

it('dispatches non-response events such as batch.completed', function () {
    Event::fake();

    $event = ['id' => 'evt_456', 'object' => 'event', 'created_at' => time(), 'type' => 'batch.completed', 'data' => ['id' => 'batch_1']];

    $response = app(WebhookController::class)->handle(openAiWebhookRequest($event, $this->secret));

    expect($response->status())->toBe(200);
    Event::assertDispatched(OpenAiWebhookReceived::class, fn (OpenAiWebhookReceived $e) => $e->type === 'batch.completed'
        && $e->data['id'] === 'batch_1');
    Event::assertNotDispatched(ResponseCompleted::class);
});

it('records cancelled responses', function () {
    $event = ['id' => 'evt_789', 'object' => 'event', 'created_at' => time(), 'type' => 'response.cancelled', 'data' => ['id' => 'resp_x']];

    app(WebhookController::class)->handle(openAiWebhookRequest($event, $this->secret));

    expect(app(ResponseStatusStore::class)->getStatus('resp_x')['status'] ?? null)->toBe('cancelled');
});

it('rejects OpenAI events with invalid or stale signatures', function (?int $timestamp, ?string $signature) {
    Event::fake();
    $event = ['id' => 'evt_1', 'object' => 'event', 'created_at' => time(), 'type' => 'response.completed', 'data' => ['id' => 'resp_1']];

    $response = app(WebhookController::class)->handle(openAiWebhookRequest($event, $this->secret, $timestamp, $signature));

    expect($response->status())->toBe(401);
    Event::assertNotDispatched(OpenAiWebhookReceived::class);
})->with([
    'wrong signature' => [null, 'v1,' . base64_encode(str_repeat('x', 32))],
    'stale timestamp' => [time() - 3600, null],
]);

it('honors max_skew_seconds given as an environment string', function (string $skew, int $age, int $status) {
    // env('AI_WEBHOOKS_MAX_SKEW_SECONDS') returns strings such as "600"
    config()->set('ai-assistant.webhooks.max_skew_seconds', $skew);
    $event = ['id' => 'evt_1', 'object' => 'event', 'created_at' => time(), 'type' => 'batch.completed', 'data' => ['id' => 'batch_1']];

    $response = app(WebhookController::class)->handle(openAiWebhookRequest($event, $this->secret, time() - $age));

    expect($response->status())->toBe($status);
})->with([
    'inside a 600 second window' => ['600', 500, 200],
    'outside a 60 second window' => ['60', 120, 401],
]);

it('accepts legacy body signatures when max_skew_seconds is an environment string', function () {
    config()->set('ai-assistant.webhooks.max_skew_seconds', '600');
    $body = '{"type":"response.completed","response":{"id":"resp_legacy"}}';
    $request = Request::create('/ai-assistant/webhook', 'POST', [], [], [], [
        'HTTP_X_OPENAI_SIGNATURE' => hash_hmac('sha256', $body, $this->secret),
        'CONTENT_TYPE' => 'application/json',
    ], $body);

    expect(app(WebhookController::class)->handle($request)->status())->toBe(200)
        ->and(app(ResponseStatusStore::class)->getStatus('resp_legacy')['status'] ?? null)->toBe('completed');
});

it('lets the verify.ai.webhook middleware accept OpenAI signatures', function () {
    $event = ['id' => 'evt_1', 'object' => 'event', 'created_at' => time(), 'type' => 'eval.run.succeeded', 'data' => ['id' => 'evalrun_1']];
    $middleware = new VerifyAiWebhookSignature();
    $next = fn () => response('passed');

    expect($middleware->handle(openAiWebhookRequest($event, $this->secret), $next)->getContent())->toBe('passed')
        ->and($middleware->handle(openAiWebhookRequest($event, $this->secret, signature: 'v1,bm9wZQ=='), $next)->getStatusCode())->toBe(401);
});
