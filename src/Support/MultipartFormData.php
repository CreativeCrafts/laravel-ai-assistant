<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Support;

use BackedEnum;
use CreativeCrafts\LaravelAiAssistant\Exceptions\FileValidationException;
use Psr\Http\Message\StreamInterface;
use SplFileInfo;
use Stringable;

/**
 * Encodes request payloads into multipart/form-data parts the way the OpenAI API expects them.
 *
 * File fields accept a file path, an SplFileInfo, an open stream resource, a PSR-7 stream, or an
 * explicit part (['contents' => ..., 'filename' => ..., 'content_type' => ...]). A list of files is
 * sent as repeated "name[]" parts. All other values follow OpenAI's form encoding: list arrays become
 * repeated "name[]" fields, associative arrays become "name[key]" fields, booleans become
 * "true"/"false" and null values are skipped.
 *
 * The result is a list of explicit parts accepted by OpenAITransport::postMultipart() and request().
 *
 * @internal Used by the HTTP repositories.
 */
final class MultipartFormData
{
    /**
     * @param array<string, mixed> $payload
     * @param array<int, string> $fileFields Payload keys that hold files.
     * @return list<array{name: string, contents: mixed, filename?: string, headers?: array<string, string>}>
     */
    public static function encode(array $payload, array $fileFields = []): array
    {
        $parts = [];
        foreach ($payload as $name => $value) {
            $name = (string)$name;
            if ($value === null) {
                continue;
            }

            if (in_array($name, $fileFields, true)) {
                if (is_array($value) && array_is_list($value)) {
                    foreach ($value as $file) {
                        $parts[] = self::filePart($name . '[]', $file);
                    }
                } else {
                    $parts[] = self::filePart($name, $value);
                }
                continue;
            }

            self::addField($parts, $name, $value);
        }

        return $parts;
    }

    /**
     * Build a single file part.
     *
     * @return array{name: string, contents: mixed, filename?: string, headers?: array<string, string>}
     * @throws FileValidationException When a file path cannot be read.
     */
    public static function filePart(string $name, mixed $file): array
    {
        $filename = null;
        $contentType = null;

        if (is_array($file) && array_key_exists('contents', $file)) {
            $filename = isset($file['filename']) && is_string($file['filename']) ? $file['filename'] : null;
            $contentType = isset($file['content_type']) && is_string($file['content_type']) ? $file['content_type'] : null;
            $file = $file['contents'];
            if ($file instanceof SplFileInfo) {
                $file = $file->getRealPath() ?: $file->getPathname();
            }
            if (!is_string($file) || !is_file($file)) {
                return self::part($name, $file, $filename, $contentType);
            }
        }

        if ($file instanceof SplFileInfo) {
            $file = $file->getRealPath() ?: $file->getPathname();
        }

        if (is_string($file)) {
            if (!is_file($file)) {
                throw FileValidationException::fileNotFound($file);
            }
            if (!is_readable($file)) {
                throw FileValidationException::fileNotReadable($file);
            }
            $handle = fopen($file, 'rb');
            if ($handle === false) {
                throw FileValidationException::fileNotReadable($file);
            }

            return self::part($name, $handle, $filename ?? basename($file), $contentType ?? self::mimeTypeFor($file));
        }

        if (is_resource($file)) {
            $uri = stream_get_meta_data($file)['uri'] ?? '';
            if ($uri === '' || str_starts_with($uri, 'php://')) {
                return self::part($name, $file, null, null);
            }

            return self::part($name, $file, basename($uri), is_file($uri) ? self::mimeTypeFor($uri) : null);
        }

        if ($file instanceof StreamInterface) {
            $uri = $file->getMetadata('uri');
            $filename = is_string($uri) && $uri !== '' && !str_starts_with($uri, 'php://') ? basename($uri) : null;

            return self::part($name, $file, $filename, null);
        }

        throw FileValidationException::invalidPathType($file);
    }

    /**
     * Read a local audio/image file into a base64 data URL (e.g. for known_speaker_references[]).
     *
     * @throws FileValidationException When the file cannot be read.
     */
    public static function toDataUrl(string|SplFileInfo $file, ?string $mimeType = null): string
    {
        $path = $file instanceof SplFileInfo ? ($file->getRealPath() ?: $file->getPathname()) : $file;
        if (!is_file($path)) {
            throw FileValidationException::fileNotFound($path);
        }
        if (!is_readable($path)) {
            throw FileValidationException::fileNotReadable($path);
        }

        $contents = file_get_contents($path);
        if ($contents === false) {
            throw FileValidationException::fileNotReadable($path);
        }

        $mimeType ??= self::mimeTypeFor($path) ?? 'application/octet-stream';

        return 'data:' . $mimeType . ';base64,' . base64_encode($contents);
    }

    /**
     * MIME type for a file: known audio/image/video extensions first (what the API uses to identify
     * the format), then content sniffing.
     */
    public static function mimeTypeFor(string $path): ?string
    {
        return self::mimeTypeFromExtension($path) ?? self::detectMimeType($path);
    }

    public static function mimeTypeFromExtension(string $filename): ?string
    {
        return match (strtolower(pathinfo($filename, PATHINFO_EXTENSION))) {
            'mp3', 'mpga', 'mpeg' => 'audio/mpeg',
            'm4a' => 'audio/mp4',
            'mp4' => 'video/mp4',
            'wav' => 'audio/wav',
            'webm' => 'audio/webm',
            'ogg', 'oga', 'opus' => 'audio/ogg',
            'flac' => 'audio/flac',
            'aac' => 'audio/aac',
            'pcm' => 'audio/pcm',
            'png' => 'image/png',
            'jpg', 'jpeg' => 'image/jpeg',
            'webp' => 'image/webp',
            'gif' => 'image/gif',
            'mov' => 'video/quicktime',
            default => null,
        };
    }

    public static function detectMimeType(string $path): ?string
    {
        if (!function_exists('finfo_open')) {
            return null;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo === false) {
            return null;
        }
        $detected = finfo_file($finfo, $path);
        finfo_close($finfo);

        return is_string($detected) && $detected !== '' ? $detected : null;
    }

    /**
     * @param list<array{name: string, contents: mixed, filename?: string, headers?: array<string, string>}> $parts
     */
    private static function addField(array &$parts, string $name, mixed $value): void
    {
        if ($value === null) {
            return;
        }

        if (is_array($value)) {
            $isList = array_is_list($value);
            foreach ($value as $key => $item) {
                self::addField($parts, $isList ? $name . '[]' : $name . '[' . $key . ']', $item);
            }

            return;
        }

        if (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        } elseif ($value instanceof BackedEnum) {
            $value = (string)$value->value;
        } elseif (is_scalar($value) || $value instanceof Stringable) {
            $value = (string)$value;
        } else {
            $value = (string)json_encode($value);
        }

        $parts[] = ['name' => $name, 'contents' => $value];
    }

    /**
     * @return array{name: string, contents: mixed, filename?: string, headers?: array<string, string>}
     */
    private static function part(string $name, mixed $contents, ?string $filename, ?string $contentType): array
    {
        $part = ['name' => $name, 'contents' => $contents];
        if ($filename !== null && $filename !== '') {
            $part['filename'] = $filename;
        }
        if ($contentType !== null && $contentType !== '') {
            $part['headers'] = ['Content-Type' => $contentType];
        }

        return $part;
    }
}
