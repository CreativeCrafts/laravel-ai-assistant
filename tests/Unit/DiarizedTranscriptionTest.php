<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedSegment;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedTranscription;

beforeEach(function () {
    $this->response = [
        'task' => 'transcribe',
        'duration' => 12.0,
        'text' => 'Thanks for calling. How can I help? My order is late. It never arrived. Let me check that for you.',
        'segments' => [
            ['type' => 'transcript.text.segment', 'id' => 'seg_1', 'start' => 0.0, 'end' => 2.0, 'speaker' => 'A', 'text' => ' Thanks for calling.'],
            ['type' => 'transcript.text.segment', 'id' => 'seg_2', 'start' => 2.0, 'end' => 3.5, 'speaker' => 'A', 'text' => 'How can I help?'],
            ['type' => 'transcript.text.segment', 'id' => 'seg_3', 'start' => 4.0, 'end' => 6.0, 'speaker' => 'B', 'text' => 'My order is late.'],
            ['type' => 'transcript.text.segment', 'id' => 'seg_4', 'start' => 6.0, 'end' => 9.0, 'speaker' => 'B', 'text' => 'It never arrived.'],
            ['type' => 'transcript.text.segment', 'id' => 'seg_5', 'start' => 9.5, 'end' => 11.0, 'speaker' => 'A', 'text' => 'Let me check that for you.'],
        ],
        'usage' => ['type' => 'duration', 'seconds' => 12],
    ];
});

it('maps a diarized_json response', function () {
    $result = DiarizedTranscription::fromArray($this->response);

    expect($result->duration)->toBe(12.0)
        ->and($result->segments)->toHaveCount(5)
        ->and($result->segments[0])->toBeInstanceOf(DiarizedSegment::class)
        ->and($result->segments[0]->text)->toBe('Thanks for calling.')
        ->and($result->segments[0]->duration())->toBe(2.0)
        ->and($result->usage)->toBe(['type' => 'duration', 'seconds' => 12])
        ->and($result->raw)->toBe($this->response);
});

it('identifies the speakers in order of appearance', function () {
    $result = DiarizedTranscription::fromArray($this->response);

    expect($result->speakers())->toBe(['A', 'B'])
        ->and($result->speakerCount())->toBe(2)
        ->and($result->hasSpeaker('B'))->toBeTrue()
        ->and($result->hasSpeaker('C'))->toBeFalse();
});

it('returns what each speaker said', function () {
    $result = DiarizedTranscription::fromArray($this->response);

    expect($result->textFor('B'))->toBe('My order is late. It never arrived.')
        ->and(array_map(fn (DiarizedSegment $s) => $s->id, $result->segmentsFor('A')))->toBe(['seg_1', 'seg_2', 'seg_5'])
        ->and($result->textFor('Z'))->toBe('');
});

it('computes speaking time, share and the dominant speaker', function () {
    $result = DiarizedTranscription::fromArray($this->response);

    expect($result->speakingTime())->toBe(['A' => 5.0, 'B' => 5.0])
        ->and($result->speakingTimeFor('B'))->toBe(5.0)
        ->and($result->speakingShare())->toBe(['A' => 50.0, 'B' => 50.0])
        ->and($result->dominantSpeaker())->toBe('A');

    $skewed = DiarizedTranscription::fromArray([
        'segments' => [
            ['id' => '1', 'start' => 0, 'end' => 1, 'speaker' => 'A', 'text' => 'Hi'],
            ['id' => '2', 'start' => 1, 'end' => 4, 'speaker' => 'B', 'text' => 'Hello there'],
        ],
    ]);

    expect($skewed->dominantSpeaker())->toBe('B')
        ->and($skewed->speakingShare())->toBe(['A' => 25.0, 'B' => 75.0])
        ->and($skewed->text)->toBe('Hi Hello there');
});

it('merges consecutive segments into turns', function () {
    $turns = DiarizedTranscription::fromArray($this->response)->turns();

    expect($turns)->toHaveCount(3)
        ->and($turns[0]->toArray())->toBe([
            'id' => 'seg_1', 'speaker' => 'A', 'start' => 0.0, 'end' => 3.5, 'text' => 'Thanks for calling. How can I help?',
        ])
        ->and($turns[1]->speaker)->toBe('B')
        ->and($turns[1]->text)->toBe('My order is late. It never arrived.')
        ->and($turns[2]->text)->toBe('Let me check that for you.');
});

it('renames speakers without mutating the original', function () {
    $original = DiarizedTranscription::fromArray($this->response);
    $renamed = $original->renameSpeakers(['A' => 'Agent', 'B' => 'Customer']);

    expect($renamed->speakers())->toBe(['Agent', 'Customer'])
        ->and($original->speakers())->toBe(['A', 'B'])
        ->and($renamed->textFor('Customer'))->toBe('My order is late. It never arrived.');
});

it('renders a conversation transcript', function () {
    $result = DiarizedTranscription::fromArray($this->response)->renameSpeakers(['A' => 'Agent', 'B' => 'Customer']);

    expect($result->toTranscript())->toBe(implode(PHP_EOL, [
        'Agent: Thanks for calling. How can I help?',
        'Customer: My order is late. It never arrived.',
        'Agent: Let me check that for you.',
    ]))->and(explode(PHP_EOL, $result->toTranscript(true))[1])
        ->toBe('[00:00:04.000 - 00:00:09.000] Customer: My order is late. It never arrived.');
});

it('renders WebVTT captions with voice tags', function () {
    $vtt = DiarizedTranscription::fromArray([
        'segments' => [
            ['id' => '1', 'start' => 0.5, 'end' => 3725.25, 'speaker' => 'Agent', 'text' => 'Fish & <chips>'],
        ],
    ])->toWebVtt();

    expect($vtt)->toBe("WEBVTT\n\n00:00:00.500 --> 01:02:05.250\n<v Agent>Fish &amp; &lt;chips&gt;\n");
});

it('detects diarized payloads and tolerates malformed data', function () {
    expect(DiarizedTranscription::isDiarized($this->response))->toBeTrue()
        ->and(DiarizedTranscription::isDiarized(['text' => 'plain']))->toBeFalse()
        ->and(DiarizedTranscription::isDiarized(['segments' => [['id' => 0, 'text' => 'whisper segment']]]))->toBeFalse();

    $result = DiarizedTranscription::fromArray(['segments' => ['bad', ['speaker' => '', 'start' => 'x']]]);

    expect($result->segments)->toHaveCount(1)
        ->and($result->segments[0]->speaker)->toBe('unknown')
        ->and($result->segments[0]->start)->toBe(0.0)
        ->and($result->duration)->toBeNull()
        ->and($result->dominantSpeaker())->toBe('unknown')
        ->and(DiarizedTranscription::fromArray([])->dominantSpeaker())->toBeNull();
});

it('serializes to an array', function () {
    $array = DiarizedTranscription::fromArray($this->response)->toArray();

    expect($array['speakers'])->toBe(['A', 'B'])
        ->and($array['speaking_time'])->toBe(['A' => 5.0, 'B' => 5.0])
        ->and($array['segments'])->toHaveCount(5)
        ->and($array['duration'])->toBe(12.0);
});
