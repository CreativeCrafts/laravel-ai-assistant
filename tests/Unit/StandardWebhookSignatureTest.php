<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Support\StandardWebhookSignature;

beforeEach(function () {
    // Test vector from the Standard Webhooks specification
    $this->secret = 'whsec_MfKQ9r8GKYqrTwjUPD8ILPZIo2LaLaSw';
    $this->id = 'msg_p5jXN8AQM9LWM0D4loKWxJek';
    $this->timestamp = '1614265330';
    $this->payload = '{"test": 2432232314}';
    $this->signature = 'v1,g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=';
});

it('matches the Standard Webhooks reference signature', function () {
    expect(StandardWebhookSignature::sign($this->payload, $this->id, $this->timestamp, $this->secret))
        ->toBe('g0hM9SsE+OTPJTGt/tmIKtSyZlE3uFJELVlNIOLJ1OE=')
        ->and(StandardWebhookSignature::verify($this->payload, $this->id, $this->timestamp, $this->signature, $this->secret, now: 1614265330))
        ->toBeTrue();
});

it('accepts a matching signature among several space-separated signatures', function () {
    $header = 'v1,bm90LWl0 ' . $this->signature . ' v2,ignored';

    expect(StandardWebhookSignature::verify($this->payload, $this->id, $this->timestamp, $header, $this->secret, now: 1614265400))
        ->toBeTrue();
});

it('rejects tampered payloads, other ids and wrong secrets', function () {
    $verify = fn (string $payload, string $id, string $secret) => StandardWebhookSignature::verify(
        $payload,
        $id,
        $this->timestamp,
        $this->signature,
        $secret,
        now: 1614265330
    );

    expect($verify('{"test": 1}', $this->id, $this->secret))->toBeFalse()
        ->and($verify($this->payload, 'msg_other', $this->secret))->toBeFalse()
        ->and($verify($this->payload, $this->id, 'whsec_' . base64_encode('another-secret')))->toBeFalse()
        ->and($verify($this->payload, $this->id, 'whsec_***not-base64***'))->toBeFalse();
});

it('rejects timestamps outside the tolerance window', function (int $now) {
    expect(StandardWebhookSignature::verify($this->payload, $this->id, $this->timestamp, $this->signature, $this->secret, 300, $now))
        ->toBeFalse();
})->with([
    'too old' => [1614265330 + 301],
    'too new' => [1614265330 - 301],
]);

it('rejects malformed headers', function () {
    expect(StandardWebhookSignature::verify($this->payload, $this->id, 'not-a-number', $this->signature, $this->secret))->toBeFalse()
        ->and(StandardWebhookSignature::verify($this->payload, '', $this->timestamp, $this->signature, $this->secret, now: 1614265330))->toBeFalse()
        ->and(StandardWebhookSignature::verify($this->payload, $this->id, $this->timestamp, '', $this->secret, now: 1614265330))->toBeFalse();
});

it('signs with raw secrets that are not in the whsec_ format', function () {
    $signature = StandardWebhookSignature::sign('{}', 'evt_1', '1700000000', 'plain-secret');

    expect($signature)->toBe(base64_encode(hash_hmac('sha256', 'evt_1.1700000000.{}', 'plain-secret', true)));
});
