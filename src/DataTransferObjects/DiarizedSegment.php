<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

/**
 * A span of speech attributed to a single speaker in a diarized transcription.
 *
 * Speakers are labelled with the names passed as known speakers, or sequentially
 * (A, B, C, ...) when no known speaker references were supplied.
 */
final readonly class DiarizedSegment
{
    public function __construct(
        public string $id,
        public string $speaker,
        public float $start,
        public float $end,
        public string $text,
    ) {
    }

    /**
     * Build a segment from a diarized_json segment or a transcript.text.segment stream event.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $id = $data['id'] ?? '';
        $speaker = $data['speaker'] ?? '';
        $start = $data['start'] ?? 0;
        $end = $data['end'] ?? 0;
        $text = $data['text'] ?? '';

        return new self(
            id: is_scalar($id) ? (string)$id : '',
            speaker: is_scalar($speaker) && (string)$speaker !== '' ? (string)$speaker : 'unknown',
            start: is_numeric($start) ? (float)$start : 0.0,
            end: is_numeric($end) ? (float)$end : 0.0,
            text: is_string($text) ? trim($text) : '',
        );
    }

    /**
     * Length of the segment in seconds.
     */
    public function duration(): float
    {
        return max(0.0, $this->end - $this->start);
    }

    public function withSpeaker(string $speaker): self
    {
        return new self($this->id, $speaker, $this->start, $this->end, $this->text);
    }

    /**
     * @return array{id: string, speaker: string, start: float, end: float, text: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'speaker' => $this->speaker,
            'start' => $this->start,
            'end' => $this->end,
            'text' => $this->text,
        ];
    }
}
