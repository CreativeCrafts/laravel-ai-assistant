<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Contracts;

/**
 * @internal Low-level abstraction for the Audio API (speech, transcriptions, translations, voices, voice consents).
 * Use Ai::audio() for raw access or Ai::diarize() for speaker identification instead.
 */
interface AudioRepositoryContract
{
    /**
     * Generate audio from text (POST /v1/audio/speech).
     *
     * @param array<string, mixed> $payload e.g. ['model' => 'gpt-4o-mini-tts', 'input' => 'Hello', 'voice' => 'alloy']
     * @return array Binary audio as ['content' => string, 'content_type' => string]
     */
    public function createSpeech(array $payload): array;

    /**
     * Stream generated audio as Server-Sent Events (POST /v1/audio/speech with stream_format=sse).
     *
     * @param array<string, mixed> $payload
     * @return iterable<array<string, mixed>> Decoded speech.audio.delta / speech.audio.done events
     */
    public function streamSpeech(array $payload): iterable;

    /**
     * Transcribe audio (POST /v1/audio/transcriptions).
     * Speaker diarization is available with model=gpt-4o-transcribe-diarize and response_format=diarized_json.
     *
     * @param array<string, mixed> $payload 'file' may be a path, SplFileInfo, stream resource or explicit part;
     *                                      array values are sent as OpenAI form fields (e.g. known_speaker_names[])
     * @return array Decoded transcription (['text' => ...] for text, srt and vtt formats)
     */
    public function createTranscription(array $payload): array;

    /**
     * Stream a transcription (POST /v1/audio/transcriptions with stream=true).
     *
     * @param array<string, mixed> $payload
     * @return iterable<array<string, mixed>> Decoded transcript.text.delta, transcript.text.segment and transcript.text.done events
     */
    public function streamTranscription(array $payload): iterable;

    /**
     * Translate audio into English (POST /v1/audio/translations).
     *
     * @param array<string, mixed> $payload
     */
    public function createTranslation(array $payload): array;

    /**
     * Create a custom voice from a consented audio sample or a prompt (POST /v1/audio/voices).
     *
     * @param array<string, mixed> $payload 'audio_sample' may be a path, SplFileInfo, stream resource or explicit part
     */
    public function createVoice(array $payload): array;

    /**
     * Upload a voice consent recording (POST /v1/audio/voice_consents).
     *
     * @param array<string, mixed> $payload e.g. ['name' => 'John Doe', 'language' => 'en-US', 'recording' => '/path/consent.wav'];
     *                                      'recording' may be a path, SplFileInfo, stream resource or explicit part
     */
    public function createVoiceConsent(array $payload): array;

    /**
     * List voice consent recordings (GET /v1/audio/voice_consents).
     *
     * @param array<string, mixed> $params Query parameters (after, limit)
     */
    public function listVoiceConsents(array $params = []): array;

    /**
     * Retrieve a voice consent recording (GET /v1/audio/voice_consents/{consentId}).
     */
    public function retrieveVoiceConsent(string $consentId): array;

    /**
     * Update a voice consent recording's metadata (POST /v1/audio/voice_consents/{consentId}).
     *
     * @param array<string, mixed> $payload e.g. ['name' => 'John Doe']
     */
    public function updateVoiceConsent(string $consentId, array $payload): array;

    /**
     * Delete a voice consent recording (DELETE /v1/audio/voice_consents/{consentId}).
     */
    public function deleteVoiceConsent(string $consentId): array;
}
