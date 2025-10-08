<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\ConversationsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Exceptions\ApiResponseValidationException;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

afterEach(function () {
    Mockery::close();
});

it('createConversation returns created conversation', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/conversations');
            expect(($options['headers']['Accept'] ?? ''))->toBe('application/json');
            expect($options['json'] ?? [])->toBeArray();
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'conv_1'])));

    $repo = new ConversationsHttpRepository($client);
    $out = $repo->createConversation(['foo' => 'bar']);

    expect($out['id'] ?? null)->toBe('conv_1');
});

it('createConversation throws on server error', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'boom']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->createConversation())->toThrow(ApiResponseValidationException::class);
});

it('getConversation returns conversation', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/conversations/conv_2');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'conv_2'])));

    $repo = new ConversationsHttpRepository($client);
    $out = $repo->getConversation('conv_2');

    expect($out['id'])->toBe('conv_2');
});

it('getConversation throws on 404', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->andReturn(new Psr7Response(404, [], json_encode(['error' => ['message' => 'nope']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->getConversation('missing'))->toThrow(ApiResponseValidationException::class);
});

it('updateConversation returns updated', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/conversations/conv_3');
            expect(($options['json']['title'] ?? null))->toBe('New Title');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['id' => 'conv_3', 'title' => 'New Title'])));

    $repo = new ConversationsHttpRepository($client);
    $out = $repo->updateConversation('conv_3', ['title' => 'New Title']);

    expect($out['title'] ?? null)->toBe('New Title');
});

it('updateConversation throws on 500', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'fail']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->updateConversation('conv_4', ['x' => 'y']))->toThrow(ApiResponseValidationException::class);
});

it('deleteConversation returns true on 204', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/conversations/conv_5');
            return true;
        })
        ->andReturn(new Psr7Response(204));

    $repo = new ConversationsHttpRepository($client);
    $ok = $repo->deleteConversation('conv_5');

    expect($ok)->toBeTrue();
});

it('deleteConversation throws on 500', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'boom']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->deleteConversation('conv_6'))->toThrow(ApiResponseValidationException::class);
});

it('listItems returns items with query params', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/conversations/conv_7/items?limit=3');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['data' => [['id' => 'i1'], ['id' => 'i2'], ['id' => 'i3']]])));

    $repo = new ConversationsHttpRepository($client);
    $out = $repo->listItems('conv_7', ['limit' => 3]);

    expect($out['data'] ?? [])->toHaveCount(3);
});

it('listItems throws on 500', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('get')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'bad']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->listItems('conv_8'))->toThrow(ApiResponseValidationException::class);
});

it('createItems returns created items', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST');
            expect($uri)->toBe('/v1/conversations/conv_9/items');
            expect($options['json']['items'] ?? null)->toBeArray();
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['ok' => true])));

    $repo = new ConversationsHttpRepository($client);
    $out = $repo->createItems('conv_9', [['type' => 'message', 'content' => 'hi']]);

    expect($out['ok'] ?? false)->toBeTrue();
});

it('createItems throws on 500', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('request')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'bad']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->createItems('conv_10', []))->toThrow(ApiResponseValidationException::class);
});

it('deleteItem returns true on 200/204', function () {
    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->withArgs(function (string $uri, array $options) {
            expect($uri)->toBe('/v1/conversations/conv_11/items/item_1');
            return true;
        })
        ->andReturn(new Psr7Response(200, [], json_encode(['ok' => true])));

    $repo = new ConversationsHttpRepository($client);
    $ok = $repo->deleteItem('conv_11', 'item_1');

    expect($ok)->toBeTrue();
});

it('deleteItem throws on 500', function () {
    // Avoid multiple retry attempts so that we only assert one call
    config()->set('ai-assistant.responses.retry', [
        'enabled' => true,
        'max_attempts' => 1,
    ]);

    $client = Mockery::mock(GuzzleClient::class);

    $client->shouldReceive('delete')
        ->once()
        ->andReturn(new Psr7Response(500, [], json_encode(['error' => ['message' => 'no']])));

    $repo = new ConversationsHttpRepository($client);

    expect(fn () => $repo->deleteItem('conv_12', 'item_2'))->toThrow(ApiResponseValidationException::class);
});
