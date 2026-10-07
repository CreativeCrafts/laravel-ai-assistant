<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedTranscription;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ResponseDto;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Support\DiarizationBuilder;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

beforeEach(function () {
    $this->recording = __DIR__ . '/../fixtures/test-audio.mp3';
    $this->diarizedResponse = [
        'task' => 'transcribe',
        'duration' => 6.0,
        'text' => 'Thanks for calling. My order is late.',
        'segments' => [
            ['type' => 'transcript.text.segment', 'id' => 'seg_1', 'start' => 0.0, 'end' => 2.5, 'speaker' => 'agent', 'text' => 'Thanks for calling.'],
            ['type' => 'transcript.text.segment', 'id' => 'seg_2', 'start' => 2.8, 'end' => 6.0, 'speaker' => 'customer', 'text' => 'My order is late.'],
        ],
        'usage' => ['type' => 'duration', 'seconds' => 6],
    ];
});

afterEach(function () {
    Mockery::close();
});

/**
 * Bind a Guzzle-backed transport whose client asserts on the outgoing multipart request.
 *
 * @param callable(array<int, array<string, mixed>>): void $assertParts
 */
function bindDiarizationTransport(array $response, callable $assertParts): void
{
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) use ($assertParts) {
            expect($method)->toBe('POST')->and($uri)->toBe('/v1/audio/transcriptions');
            $assertParts($options['multipart']);
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode($response)));

    app()->instance(OpenAITransport::class, new GuzzleOpenAITransport($client));
}

/**
 * @param array<int, array<string, mixed>> $parts
 * @return array<string, array<int, mixed>>
 */
function diarizationFields(array $parts): array
{
    $fields = [];
    foreach ($parts as $part) {
        $fields[$part['name']][] = is_resource($part['contents']) ? 'resource' : $part['contents'];
    }

    return $fields;
}

it('exposes the audio repository and the diarization builder on the facade', function () {
    expect(Ai::audio())->toBeInstanceOf(AudioRepositoryContract::class)
        ->and(Ai::diarize())->toBeInstanceOf(DiarizationBuilder::class)
        ->and(Ai::diarize($this->recording)->toPayload()['file'])->toBe($this->recording);
});

it('identifies known speakers through Ai::diarize()', function () {
    bindDiarizationTransport($this->diarizedResponse, function (array $parts) {
        $fields = diarizationFields($parts);
        expect($fields['file'])->toBe(['resource'])
            ->and($fields['model'])->toBe(['gpt-4o-transcribe-diarize'])
            ->and($fields['response_format'])->toBe(['diarized_json'])
            ->and($fields['chunking_strategy'])->toBe(['auto'])
            ->and($fields['known_speaker_names[]'])->toBe(['agent', 'customer'])
            ->and($fields['known_speaker_references[]'])->toHaveCount(2)
            ->and($fields['known_speaker_references[]'][0])->toStartWith('data:audio/mpeg;base64,');
    });

    $result = Ai::diarize($this->recording)
        ->knownSpeaker('agent', $this->recording)
        ->knownSpeaker('customer', $this->recording)
        ->send();

    expect($result)->toBeInstanceOf(DiarizedTranscription::class)
        ->and($result->speakers())->toBe(['agent', 'customer'])
        ->and($result->textFor('customer'))->toBe('My order is late.')
        ->and($result->toTranscript())->toBe("agent: Thanks for calling." . PHP_EOL . "customer: My order is late.");
});

it('diarizes through the unified responses builder', function () {
    bindDiarizationTransport($this->diarizedResponse, function (array $parts) {
        $fields = diarizationFields($parts);
        expect($fields['model'])->toBe(['gpt-4o-transcribe-diarize'])
            ->and($fields['response_format'])->toBe(['diarized_json'])
            ->and($fields['known_speaker_names[]'])->toBe(['agent'])
            ->and($fields)->not->toHaveKey('prompt')
            ->and($fields)->not->toHaveKey('known_speaker_names');
    });

    $response = Ai::responses()
        ->input()
        ->audio([
            'file' => $this->recording,
            'action' => 'diarize',
            'known_speakers' => ['agent' => $this->recording],
        ])
        ->send();

    expect($response)->toBeInstanceOf(ResponseDto::class)
        ->and($response->type)->toBe('audio_transcription')
        ->and($response->text)->toBe('Thanks for calling. My order is late.')
        ->and($response->metadata['speakers'])->toBe(['agent', 'customer'])
        ->and($response->metadata['speaking_time'])->toBe(['agent' => 2.5, 'customer' => 3.2])
        ->and($response->diarization()?->textFor('agent'))->toBe('Thanks for calling.');
});

it('keeps plain transcriptions unchanged', function () {
    bindDiarizationTransport(['text' => 'Just one voice.'], function (array $parts) {
        $fields = diarizationFields($parts);
        expect($fields['file'])->toBe(['resource'])
            ->and($fields['model'])->toBe(['gpt-4o-mini-transcribe'])
            ->and($fields['response_format'])->toBe(['json'])
            ->and($fields)->not->toHaveKey('chunking_strategy')
            ->and($fields)->not->toHaveKey('known_speaker_names[]');
    });

    $response = Ai::responses()
        ->input()
        ->audio(['file' => $this->recording, 'action' => 'transcribe'])
        ->send();

    expect($response->text)->toBe('Just one voice.')
        ->and($response->metadata)->toBe(['duration' => null, 'language' => null])
        ->and($response->diarization())->toBeNull();
});

it('routes diarize => true without an explicit action to transcription', function () {
    $data = Ai::responses()->input()->audio(['file' => $this->recording, 'diarize' => true])->toArray();

    expect($data['audio']['action'])->toBe('transcribe')
        ->and($data['audio']['diarize'])->toBeTrue();
});

it('rejects more than four known speakers in the unified builder', function () {
    Ai::responses()->input()->audio([
        'file' => $this->recording,
        'action' => 'diarize',
        'known_speakers' => array_fill_keys(['a', 'b', 'c', 'd', 'e'], $this->recording),
    ]);
})->throws(InvalidArgumentException::class, 'A maximum of 4 known speakers');
