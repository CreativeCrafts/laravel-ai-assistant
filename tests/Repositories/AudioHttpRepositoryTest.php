<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AudioHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use CreativeCrafts\LaravelAiAssistant\Transport\GuzzleOpenAITransport;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Psr7\Response as Psr7Response;

beforeEach(function () {
    $this->audioPath = __DIR__ . '/../fixtures/test-audio.mp3';
});

afterEach(function () {
    Mockery::close();
});

if (!function_exists('audioPartPairs')) {
    /**
     * @param array<int, array<string, mixed>> $parts
     * @return array<int, array{0: string, 1: mixed}>
     */
    function audioPartPairs(array $parts): array
    {
        return array_map(
            fn (array $part): array => [$part['name'], is_resource($part['contents']) ? 'resource' : $part['contents']],
            $parts
        );
    }
}

it('sends diarization requests with repeated known speaker fields', function () {
    config()->set('ai-assistant.audio.timeouts.transcription', 300);

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            expect($method)->toBe('POST')
                ->and($uri)->toBe('/v1/audio/transcriptions')
                ->and($options['timeout'])->toBe(300.0)
                ->and(audioPartPairs($options['multipart']))->toBe([
                    ['file', 'resource'],
                    ['model', 'gpt-4o-transcribe-diarize'],
                    ['response_format', 'diarized_json'],
                    ['chunking_strategy', 'auto'],
                    ['known_speaker_names[]', 'agent'],
                    ['known_speaker_names[]', 'customer'],
                    ['known_speaker_references[]', 'data:audio/wav;base64,QUFB'],
                    ['known_speaker_references[]', 'data:audio/wav;base64,QkJC'],
                ])
                ->and($options['multipart'][0]['filename'])->toBe('test-audio.mp3');
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode([
            'task' => 'transcribe',
            'duration' => 4.2,
            'text' => 'Hello. Hi.',
            'segments' => [
                ['type' => 'transcript.text.segment', 'id' => 'seg_1', 'start' => 0.0, 'end' => 1.5, 'speaker' => 'agent', 'text' => 'Hello.'],
                ['type' => 'transcript.text.segment', 'id' => 'seg_2', 'start' => 1.6, 'end' => 4.2, 'speaker' => 'customer', 'text' => 'Hi.'],
            ],
        ])));

    $repository = new AudioHttpRepository(new GuzzleOpenAITransport($client));
    $out = $repository->createTranscription([
        'file' => $this->audioPath,
        'model' => 'gpt-4o-transcribe-diarize',
        'response_format' => 'diarized_json',
        'chunking_strategy' => 'auto',
        'known_speaker_names' => ['agent', 'customer'],
        'known_speaker_references' => ['data:audio/wav;base64,QUFB', 'data:audio/wav;base64,QkJC'],
        'stream' => true,
    ]);

    expect($out['segments'])->toHaveCount(2)
        ->and($out['segments'][1]['speaker'])->toBe('customer');
});

it('streams transcription events', function () {
    $sse = implode("\n\n", [
        'data: {"type":"transcript.text.delta","delta":"Hello","segment_id":"seg_1"}',
        'data: {"type":"transcript.text.segment","id":"seg_1","start":0,"end":1.2,"speaker":"A","text":"Hello"}',
        'data: {"type":"transcript.text.done","text":"Hello","usage":{"type":"tokens","total_tokens":12}}',
    ]) . "\n\n";

    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(function (string $method, string $uri, array $options) {
            $pairs = audioPartPairs($options['multipart']);
            expect($pairs)->toContain(['stream', 'true'])
                ->and($options['stream'])->toBeTrue();
            return true;
        })
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'text/event-stream'], $sse));

    $repository = new AudioHttpRepository(new GuzzleOpenAITransport($client));
    $events = iterator_to_array($repository->streamTranscription([
        'file' => $this->audioPath,
        'model' => 'gpt-4o-transcribe-diarize',
        'response_format' => 'diarized_json',
    ]), false);

    expect(array_column($events, 'type'))->toBe(['transcript.text.delta', 'transcript.text.segment', 'transcript.text.done'])
        ->and($events[1]['speaker'])->toBe('A');
});

it('creates speech and returns binary audio', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $uri === '/v1/audio/speech' && $options['json']['voice'] === 'marin' && $options['headers']['Accept'] === 'application/octet-stream')
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'audio/mpeg'], 'MP3DATA'));

    $out = (new AudioHttpRepository(new GuzzleOpenAITransport($client)))->createSpeech([
        'model' => 'gpt-4o-mini-tts',
        'input' => 'Hello',
        'voice' => 'marin',
    ]);

    expect($out)->toBe(['content' => 'MP3DATA', 'content_type' => 'audio/mpeg']);
});

it('streams speech audio deltas as SSE', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $options['json']['stream_format'] === 'sse')
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'text/event-stream'], "data: {\"type\":\"speech.audio.delta\",\"audio\":\"QUFB\"}\n\ndata: {\"type\":\"speech.audio.done\"}\n\n"));

    $events = iterator_to_array((new AudioHttpRepository(new GuzzleOpenAITransport($client)))->streamSpeech([
        'model' => 'gpt-4o-mini-tts',
        'input' => 'Hello',
        'voice' => 'alloy',
    ]), false);

    expect(array_column($events, 'type'))->toBe(['speech.audio.delta', 'speech.audio.done']);
});

it('translates audio', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $uri === '/v1/audio/translations'
            && audioPartPairs($options['multipart']) === [['file', 'resource'], ['model', 'whisper-1']])
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode(['text' => 'Hello'])));

    $out = (new AudioHttpRepository(new GuzzleOpenAITransport($client)))->createTranslation([
        'file' => $this->audioPath,
        'model' => 'whisper-1',
    ]);

    expect($out['text'])->toBe('Hello');
});

it('creates custom voices from consented samples', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $uri === '/v1/audio/voices'
            && audioPartPairs($options['multipart']) === [['name', 'Narrator'], ['consent', 'I consent'], ['audio_sample', 'resource']])
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 'voice_1'])));

    $out = (new AudioHttpRepository(new GuzzleOpenAITransport($client)))->createVoice([
        'name' => 'Narrator',
        'consent' => 'I consent',
        'audio_sample' => $this->audioPath,
    ]);

    expect($out['id'])->toBe('voice_1');
});

it('uploads voice consent recordings as multipart', function () {
    $client = Mockery::mock(GuzzleClient::class);
    $client->shouldReceive('request')
        ->once()
        ->withArgs(fn (string $method, string $uri, array $options) => $method === 'POST'
            && $uri === '/v1/audio/voice_consents'
            && audioPartPairs($options['multipart']) === [['name', 'John Doe'], ['language', 'en-US'], ['recording', 'resource']])
        ->andReturn(new Psr7Response(200, ['Content-Type' => 'application/json'], json_encode(['id' => 'cons_1'])));

    $out = (new AudioHttpRepository(new GuzzleOpenAITransport($client)))->createVoiceConsent([
        'name' => 'John Doe',
        'language' => 'en-US',
        'recording' => $this->audioPath,
    ]);

    expect($out['id'])->toBe('cons_1');
});

it('lists, retrieves, updates and deletes voice consents', function () {
    $http = RecordingHttpClient::json(['id' => 'cons_1']);
    $repository = new AudioHttpRepository($http->transport());

    expect($repository->listVoiceConsents(['limit' => 20]))->toBe(['id' => 'cons_1'])
        ->and($http->lastRequest()->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/audio/voice_consents?limit=20');

    $http = RecordingHttpClient::json(['id' => 'cons_1']);
    $repository = new AudioHttpRepository($http->transport());
    $repository->retrieveVoiceConsent('cons_1');
    expect($http->lastRequest()->getMethod())->toBe('GET')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/audio/voice_consents/cons_1');

    $http = RecordingHttpClient::json(['id' => 'cons_1']);
    $repository = new AudioHttpRepository($http->transport());
    $repository->updateVoiceConsent('cons_1', ['name' => 'Jane Doe']);
    expect($http->lastRequest()->getMethod())->toBe('POST')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/audio/voice_consents/cons_1')
        ->and($http->lastJson())->toBe(['name' => 'Jane Doe']);

    $http = RecordingHttpClient::json(['id' => 'cons_1', 'deleted' => true]);
    $repository = new AudioHttpRepository($http->transport());
    expect($repository->deleteVoiceConsent('cons_1'))->toBe(['id' => 'cons_1', 'deleted' => true])
        ->and($http->lastRequest()->getMethod())->toBe('DELETE')
        ->and($http->lastUri())->toBe('https://api.openai.com/v1/audio/voice_consents/cons_1');
});

it('percent-encodes voice consent ids', function () {
    $http = RecordingHttpClient::json(['id' => 'cons_1']);
    (new AudioHttpRepository($http->transport()))->retrieveVoiceConsent('../files?x=1');

    expect($http->lastUri())->toBe('https://api.openai.com/v1/audio/voice_consents/..%2Ffiles%3Fx=1');
});
