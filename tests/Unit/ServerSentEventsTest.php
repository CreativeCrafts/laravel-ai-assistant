<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Support\ServerSentEvents;

it('decodes data-only frames using the payload type', function () {
    $events = iterator_to_array(ServerSentEvents::decode([
        'data: {"type":"transcript.text.delta","delta":"Hel"}',
        'data: {"type":"transcript.text.done","text":"Hello"}',
    ]), false);

    expect($events)->toBe([
        ['type' => 'transcript.text.delta', 'delta' => 'Hel'],
        ['type' => 'transcript.text.done', 'text' => 'Hello'],
    ]);
});

it('uses the event name when the payload has no type', function () {
    $events = iterator_to_array(ServerSentEvents::decode([
        'event: speech.audio.delta',
        'data: {"audio":"AAA="}',
    ]), false);

    expect($events)->toBe([['audio' => 'AAA=', 'type' => 'speech.audio.delta']]);
});

it('stops at the DONE sentinel and ignores comments and ids', function () {
    $events = iterator_to_array(ServerSentEvents::decode([
        ': keep-alive',
        'id: 1',
        'data: {"type":"a"}',
        'data: [DONE]',
        'data: {"type":"b"}',
    ]), false);

    expect($events)->toBe([['type' => 'a']]);
});

it('surfaces non-JSON data without losing the following events', function () {
    $events = iterator_to_array(ServerSentEvents::decode([
        'data: keep-alive',
        'data: {"type":"transcript.text.done","text":"Hi"}',
        'data: trailing',
    ]), false);

    expect($events)->toBe([
        ['data' => 'keep-alive'],
        ['type' => 'transcript.text.done', 'text' => 'Hi'],
        ['data' => 'trailing'],
    ]);
});

it('buffers JSON documents split across data lines', function () {
    $events = iterator_to_array(ServerSentEvents::decode([
        'data: {"type":"transcript.text.segment",',
        'data: "speaker":"A"}',
    ]), false);

    expect($events)->toBe([['type' => 'transcript.text.segment', 'speaker' => 'A']]);
});
