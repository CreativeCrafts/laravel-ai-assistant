<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Support\QueryString;

it('returns an empty string for empty params', function () {
    expect(QueryString::build([]))->toBe('')
        ->and(QueryString::append('/v1/files', []))->toBe('/v1/files');
});

it('encodes scalars and skips null values', function () {
    expect(QueryString::build(['limit' => 10, 'order' => 'desc', 'after' => null]))
        ->toBe('limit=10&order=desc');
});

it('encodes booleans as true and false', function () {
    expect(QueryString::build(['stream' => true, 'include_obfuscation' => false]))
        ->toBe('stream=true&include_obfuscation=false');
});

it('encodes lists with repeated bracket keys', function () {
    expect(QueryString::build(['include' => ['message.output_text.logprobs', 'reasoning.encrypted_content']]))
        ->toBe('include%5B%5D=message.output_text.logprobs&include%5B%5D=reasoning.encrypted_content');
});

it('encodes associative arrays as nested keys', function () {
    expect(QueryString::build(['metadata' => ['user' => 'u 1']]))
        ->toBe('metadata%5Buser%5D=u%201');
});

it('appends to paths that already carry a query string', function () {
    expect(QueryString::append('/v1/responses?beta=true', ['limit' => 1]))
        ->toBe('/v1/responses?beta=true&limit=1');
});
