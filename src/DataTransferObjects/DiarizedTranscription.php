<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\DataTransferObjects;

/**
 * A transcription annotated with who spoke when (speaker diarization).
 *
 * Produced from the `diarized_json` response of the gpt-4o-transcribe-diarize model, either
 * directly or by collecting `transcript.text.segment` stream events.
 */
final readonly class DiarizedTranscription
{
    /**
     * @param list<DiarizedSegment> $segments Segments in spoken order
     * @param float|null $duration Duration of the input audio in seconds (not reported when streaming)
     * @param array<string, mixed> $usage Token or duration usage reported by the API
     * @param array<string, mixed> $raw The raw API response
     */
    public function __construct(
        public string $text,
        public array $segments,
        public ?float $duration = null,
        public array $usage = [],
        public array $raw = [],
    ) {
    }

    /**
     * @param array<string, mixed> $data A diarized_json transcription response
     */
    public static function fromArray(array $data): self
    {
        $segments = [];
        $rawSegments = $data['segments'] ?? [];
        if (is_array($rawSegments)) {
            foreach ($rawSegments as $segment) {
                if (is_array($segment)) {
                    /** @var array<string, mixed> $segment */
                    $segments[] = DiarizedSegment::fromArray($segment);
                }
            }
        }

        $text = $data['text'] ?? null;
        $duration = $data['duration'] ?? null;
        $usage = $data['usage'] ?? [];

        return new self(
            text: is_string($text) ? trim($text) : self::joinText($segments),
            segments: $segments,
            duration: is_numeric($duration) ? (float)$duration : null,
            usage: is_array($usage) ? $usage : [],
            raw: $data,
        );
    }

    /**
     * Whether the payload looks like a diarized transcription (segments carrying speaker labels).
     *
     * @param array<mixed> $data
     */
    public static function isDiarized(array $data): bool
    {
        $segments = $data['segments'] ?? null;
        if (!is_array($segments) || $segments === []) {
            return false;
        }

        $first = reset($segments);

        return is_array($first) && array_key_exists('speaker', $first);
    }

    /**
     * Speaker labels in order of first appearance.
     *
     * @return list<string>
     */
    public function speakers(): array
    {
        $speakers = [];
        foreach ($this->segments as $segment) {
            if (!in_array($segment->speaker, $speakers, true)) {
                $speakers[] = $segment->speaker;
            }
        }

        return $speakers;
    }

    public function speakerCount(): int
    {
        return count($this->speakers());
    }

    public function hasSpeaker(string $speaker): bool
    {
        return in_array($speaker, $this->speakers(), true);
    }

    /**
     * @return list<DiarizedSegment>
     */
    public function segmentsFor(string $speaker): array
    {
        return array_values(array_filter(
            $this->segments,
            static fn (DiarizedSegment $segment): bool => $segment->speaker === $speaker
        ));
    }

    /**
     * Everything a single speaker said, in order.
     */
    public function textFor(string $speaker): string
    {
        return self::joinText($this->segmentsFor($speaker));
    }

    /**
     * Total speaking time per speaker, in seconds.
     *
     * @return array<string, float>
     */
    public function speakingTime(): array
    {
        $totals = [];
        foreach ($this->segments as $segment) {
            $totals[$segment->speaker] = ($totals[$segment->speaker] ?? 0.0) + $segment->duration();
        }

        return array_map(static fn (float $seconds): float => round($seconds, 3), $totals);
    }

    public function speakingTimeFor(string $speaker): float
    {
        return $this->speakingTime()[$speaker] ?? 0.0;
    }

    /**
     * Share of the total speaking time per speaker, as a percentage (0-100).
     *
     * @return array<string, float>
     */
    public function speakingShare(): array
    {
        $times = $this->speakingTime();
        $total = array_sum($times);
        if ($total <= 0.0) {
            return array_map(static fn (): float => 0.0, $times);
        }

        return array_map(static fn (float $seconds): float => round($seconds / $total * 100, 2), $times);
    }

    /**
     * The speaker with the most speaking time, or null when nobody spoke.
     */
    public function dominantSpeaker(): ?string
    {
        $times = $this->speakingTime();
        if ($times === []) {
            return null;
        }
        arsort($times);

        return (string)array_key_first($times);
    }

    /**
     * Consecutive segments from the same speaker merged into conversational turns.
     *
     * @return list<DiarizedSegment>
     */
    public function turns(): array
    {
        $turns = [];
        $current = null;
        $texts = [];

        foreach ($this->segments as $segment) {
            if ($current !== null && $current->speaker === $segment->speaker) {
                $current = new DiarizedSegment($current->id, $current->speaker, $current->start, $segment->end, '');
                $texts[] = $segment->text;
                continue;
            }

            if ($current !== null) {
                $turns[] = new DiarizedSegment($current->id, $current->speaker, $current->start, $current->end, self::joinStrings($texts));
            }
            $current = $segment;
            $texts = [$segment->text];
        }

        if ($current !== null) {
            $turns[] = new DiarizedSegment($current->id, $current->speaker, $current->start, $current->end, self::joinStrings($texts));
        }

        return $turns;
    }

    /**
     * Relabel speakers, e.g. ['A' => 'Agent', 'B' => 'Customer']. Unmapped labels are kept.
     *
     * @param array<string, string> $names
     */
    public function renameSpeakers(array $names): self
    {
        $segments = array_map(
            static fn (DiarizedSegment $segment): DiarizedSegment => isset($names[$segment->speaker])
                ? $segment->withSpeaker($names[$segment->speaker])
                : $segment,
            $this->segments
        );

        return new self($this->text, $segments, $this->duration, $this->usage, $this->raw);
    }

    /**
     * Render the conversation as one line per speaker turn, e.g. "Agent: How can I help?".
     */
    public function toTranscript(bool $withTimestamps = false): string
    {
        $lines = [];
        foreach ($this->turns() as $turn) {
            $prefix = $withTimestamps
                ? '[' . self::formatTimestamp($turn->start) . ' - ' . self::formatTimestamp($turn->end) . '] '
                : '';
            $lines[] = $prefix . $turn->speaker . ': ' . $turn->text;
        }

        return implode(PHP_EOL, $lines);
    }

    /**
     * Render the segments as WebVTT captions with voice tags (<v Speaker>).
     */
    public function toWebVtt(): string
    {
        $cues = ['WEBVTT'];
        foreach ($this->segments as $segment) {
            $speaker = str_replace(['<', '>'], '', $segment->speaker);
            $text = str_replace(['&', '<', '>'], ['&amp;', '&lt;', '&gt;'], $segment->text);
            $cues[] = self::formatTimestamp($segment->start) . ' --> ' . self::formatTimestamp($segment->end)
                . "\n<v {$speaker}>{$text}";
        }

        return implode("\n\n", $cues) . "\n";
    }

    /**
     * @return array{text: string, duration: float|null, speakers: list<string>, speaking_time: array<string, float>, segments: list<array{id: string, speaker: string, start: float, end: float, text: string}>, usage: array<string, mixed>}
     */
    public function toArray(): array
    {
        return [
            'text' => $this->text,
            'duration' => $this->duration,
            'speakers' => $this->speakers(),
            'speaking_time' => $this->speakingTime(),
            'segments' => array_map(static fn (DiarizedSegment $segment): array => $segment->toArray(), $this->segments),
            'usage' => $this->usage,
        ];
    }

    /**
     * @param list<DiarizedSegment> $segments
     */
    private static function joinText(array $segments): string
    {
        return self::joinStrings(array_map(static fn (DiarizedSegment $segment): string => $segment->text, $segments));
    }

    /**
     * @param array<int, string> $texts
     */
    private static function joinStrings(array $texts): string
    {
        return implode(' ', array_filter($texts, static fn (string $text): bool => $text !== ''));
    }

    private static function formatTimestamp(float $seconds): string
    {
        $milliseconds = (int)round(max(0.0, $seconds) * 1000);

        return sprintf(
            '%02d:%02d:%02d.%03d',
            intdiv($milliseconds, 3_600_000),
            intdiv($milliseconds, 60_000) % 60,
            intdiv($milliseconds, 1000) % 60,
            $milliseconds % 1000
        );
    }
}
