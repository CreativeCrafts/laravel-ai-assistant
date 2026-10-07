<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\AudioRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedSegment;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\DiarizedTranscription;
use CreativeCrafts\LaravelAiAssistant\Exceptions\AudioTranscriptionException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use CreativeCrafts\LaravelAiAssistant\Support\DiarizationBuilder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

/**
 * Records the payloads the builder sends and replays canned responses.
 */
final class RecordingAudioRepository implements AudioRepositoryContract
{
    /** @var array<int, array<string, mixed>> */
    public array $payloads = [];

    /** @var array<string, mixed> */
    public array $transcription = [];

    /** @var array<int, array<string, mixed>> */
    public array $events = [];

    public function createSpeech(array $payload): array
    {
        return [];
    }

    public function streamSpeech(array $payload): iterable
    {
        return [];
    }

    public function createTranscription(array $payload): array
    {
        $this->payloads[] = $payload;

        return $this->transcription;
    }

    public function streamTranscription(array $payload): iterable
    {
        $this->payloads[] = $payload;

        return $this->events;
    }

    public function createTranslation(array $payload): array
    {
        return [];
    }

    public function createVoice(array $payload): array
    {
        return [];
    }

    public function createVoiceConsent(array $payload): array
    {
        return [];
    }

    public function listVoiceConsents(array $params = []): array
    {
        return [];
    }

    public function retrieveVoiceConsent(string $consentId): array
    {
        return [];
    }

    public function updateVoiceConsent(string $consentId, array $payload): array
    {
        return [];
    }

    public function deleteVoiceConsent(string $consentId): array
    {
        return [];
    }
}

beforeEach(function () {
    $this->audio = new RecordingAudioRepository();
    $this->builder = new DiarizationBuilder($this->audio);
    $this->recording = __DIR__ . '/../fixtures/test-audio.mp3';
});

it('builds a diarized_json request with automatic chunking by default', function () {
    $payload = $this->builder->file($this->recording)->toPayload();

    expect($payload)->toBe([
        'file' => $this->recording,
        'model' => 'gpt-4o-transcribe-diarize',
        'response_format' => 'diarized_json',
        'chunking_strategy' => 'auto',
    ]);
});

it('includes known speakers as names and data URL references', function () {
    $payload = $this->builder
        ->file($this->recording)
        ->knownSpeaker('agent', $this->recording)
        ->knownSpeakers(['customer' => 'data:audio/wav;base64,QUFB'])
        ->language('en')
        ->temperature(0.1)
        ->toPayload();

    expect($payload['known_speaker_names'])->toBe(['agent', 'customer'])
        ->and($payload['known_speaker_references'][0])->toStartWith('data:audio/mpeg;base64,')
        ->and($payload['known_speaker_references'][1])->toBe('data:audio/wav;base64,QUFB')
        ->and($payload['language'])->toBe('en')
        ->and($payload['temperature'])->toBe(0.1);
});

it('supports server VAD chunking and extra options without overriding the diarization format', function () {
    $payload = $this->builder
        ->file($this->recording)
        ->serverVad(threshold: 0.6, silenceDurationMs: 500)
        ->withOptions(['response_format' => 'json', 'extra_flag' => 'x'])
        ->toPayload();

    expect($payload['chunking_strategy'])->toBe(['type' => 'server_vad', 'threshold' => 0.6, 'silence_duration_ms' => 500])
        ->and($payload['response_format'])->toBe('diarized_json')
        ->and($payload['extra_flag'])->toBe('x');
});

it('limits known speakers to four', function () {
    $this->builder->knownSpeakers([
        'a' => 'data:audio/wav;base64,QQ==',
        'b' => 'data:audio/wav;base64,Qg==',
        'c' => 'data:audio/wav;base64,Qw==',
        'd' => 'data:audio/wav;base64,RA==',
        'e' => 'data:audio/wav;base64,RQ==',
    ]);
})->throws(InvalidArgumentException::class, 'A maximum of 4 known speakers');

it('validates the recording', function (string $path, string $message) {
    $this->builder->file($path);
})->with([
    'missing file' => ['/does/not/exist.mp3', 'File not found'],
    'unsupported format' => [__FILE__, 'Unsupported file format: php'],
])->throws(FileValidationException::class);

it('rejects recordings above the configured size limit', function () {
    config()->set('ai-assistant.audio.file_size_limit_mb', 0.000001);

    $this->builder->file($this->recording);
})->throws(FileValidationException::class, 'exceeds maximum allowed size');

it('requires a recording before sending', function () {
    $this->builder->send();
})->throws(AudioTranscriptionException::class, 'Audio file is required');

it('accepts stream resources with a filename hint', function () {
    $stream = fopen('php://memory', 'rb+');
    fwrite($stream, 'audio');
    rewind($stream);

    $payload = $this->builder->file($stream, 'call.ogg')->toPayload();

    expect($payload['file'])->toBe(['contents' => $stream, 'filename' => 'call.ogg', 'content_type' => 'audio/ogg']);
});

it('diarizes uploaded files under their original filename', function () {
    // PHP keeps uploads at extension-less temporary paths such as /tmp/phpAbC123
    $upload = UploadedFile::fake()->createWithContent('support-call.mp3', (string)file_get_contents($this->recording));

    $payload = $this->builder->file($upload)->toPayload();

    expect(pathinfo((string)$upload->getRealPath(), PATHINFO_EXTENSION))->toBe('')
        ->and($payload['file'])->toBe([
            'contents' => $upload->getRealPath(),
            'filename' => 'support-call.mp3',
            'content_type' => 'audio/mpeg',
        ]);
});

it('validates the format of the filename the API receives', function () {
    $path = (string)tempnam(sys_get_temp_dir(), 'recording');
    copy($this->recording, $path);

    try {
        expect($this->builder->file($path, 'call.wav')->toPayload()['file'])
            ->toBe(['contents' => $path, 'filename' => 'call.wav', 'content_type' => 'audio/wav']);
    } finally {
        unlink($path);
    }
});

it('rejects uploads whose original filename is not a supported audio format', function () {
    $this->builder->file(UploadedFile::fake()->createWithContent('notes.txt', 'not audio'));
})->throws(FileValidationException::class, 'Unsupported file format: txt');

it('reads recordings from a filesystem disk', function () {
    Storage::fake('recordings');
    Storage::disk('recordings')->put('calls/support.mp3', 'audio-bytes');

    $payload = $this->builder->fromDisk('recordings', 'calls/support.mp3')->toPayload();

    expect($payload['file']['filename'])->toBe('support.mp3')
        ->and(stream_get_contents($payload['file']['contents']))->toBe('audio-bytes');
});

it('sends the request and maps the speaker-annotated result', function () {
    $this->audio->transcription = [
        'task' => 'transcribe',
        'duration' => 3.0,
        'text' => 'Hi. Hello.',
        'segments' => [
            ['id' => 'seg_1', 'start' => 0, 'end' => 1, 'speaker' => 'agent', 'text' => 'Hi.'],
            ['id' => 'seg_2', 'start' => 1, 'end' => 3, 'speaker' => 'customer', 'text' => 'Hello.'],
        ],
    ];

    $result = $this->builder->file($this->recording)->send();

    expect($result)->toBeInstanceOf(DiarizedTranscription::class)
        ->and($result->speakers())->toBe(['agent', 'customer'])
        ->and($result->textFor('customer'))->toBe('Hello.')
        ->and($this->audio->payloads[0]['model'])->toBe('gpt-4o-transcribe-diarize');
});

it('streams speaker segments and returns the complete transcription', function () {
    $this->audio->events = [
        ['type' => 'transcript.text.delta', 'delta' => 'Hi', 'segment_id' => 'seg_1'],
        ['type' => 'transcript.text.segment', 'id' => 'seg_1', 'start' => 0, 'end' => 1, 'speaker' => 'A', 'text' => 'Hi.'],
        ['type' => 'transcript.text.segment', 'id' => 'seg_2', 'start' => 1, 'end' => 2.5, 'speaker' => 'B', 'text' => 'Hello.'],
        ['type' => 'transcript.text.done', 'text' => 'Hi. Hello.', 'usage' => ['type' => 'tokens', 'total_tokens' => 10]],
    ];

    $deltas = [];
    $stream = $this->builder->file($this->recording)->stream(function (string $delta, ?string $segmentId) use (&$deltas) {
        $deltas[] = [$delta, $segmentId];
    });

    $segments = iterator_to_array($stream, false);
    $result = $stream->getReturn();

    expect($segments)->toHaveCount(2)
        ->and($segments[0])->toBeInstanceOf(DiarizedSegment::class)
        ->and($segments[1]->speaker)->toBe('B')
        ->and($deltas)->toBe([['Hi', 'seg_1']])
        ->and($result->text)->toBe('Hi. Hello.')
        ->and($result->speakingTime())->toBe(['A' => 1.0, 'B' => 1.5])
        ->and($result->usage)->toBe(['type' => 'tokens', 'total_tokens' => 10]);
});

it('throws when the stream reports an error', function () {
    $this->audio->events = [
        ['type' => 'error', 'error' => ['message' => 'Audio file might be corrupted']],
    ];

    iterator_to_array($this->builder->file($this->recording)->stream());
})->throws(AudioTranscriptionException::class, 'Audio file might be corrupted');
