# Upgrade Guide

This guide focuses on what you must change when upgrading between major releases.
For hands-on code examples, see `MIGRATION.md`.

---

## Unreleased

### Breaking changes

1) **Custom transports must implement `request()` and `streamRequest()`**

If you provide your own `OpenAITransport`, add:

```php
public function request(string $method, string $path, array $options = []): array;
public function streamRequest(string $method, string $path, array $options = []): iterable;
```

`$options` may contain `query`, `json`, `multipart`, `body`, `headers`, `timeout` and `idempotent`.
`request()` returns the decoded JSON body (`[]` for empty bodies, `['text' => ...]` for text bodies and
`['content' => ..., 'content_type' => ...]` for binary bodies); `streamRequest()` yields raw SSE lines.

2) **Custom repository implementations**

If you bind your own implementations of these contracts, add the new methods/parameters:

```php
// ResponsesRepositoryContract
public function createResponse(array $payload, array $headers = []): array;
public function streamResponse(array $payload, array $headers = []): iterable;
public function getResponse(string $responseId, array $params = []): array;
public function resumeStream(string $responseId, array $params = []): iterable;
public function compactResponse(array $payload): array;
public function countInputTokens(array $payload): array;

// ConversationsRepositoryContract
public function createItems(string $conversationId, array $items, array $params = []): array;
public function getItem(string $conversationId, string $itemId, array $params = []): array;

// FilesRepositoryContract
public function upload(string $filePath, string $purpose = 'assistants', array $params = []): array;
public function list(array $params = []): array;

// VectorStoresRepositoryContract
public function search(string $vectorStoreId, array $payload): array;
```

Conversation turns (`Ai::responses()->send()`/`stream()` and the chat helpers built on them) now pass the `$headers`
argument (`[]` when none are set), so mocks that pin the arguments of `createResponse()` or `streamResponse()`
(for example Mockery's `->with($payload)`) must expect it for those calls.

If you extend `StreamingService` and override `process()`, add the trailing `array $headers = []` parameter.

3) **OpenAI webhooks**

OpenAI signs webhook deliveries with the `webhook-id`, `webhook-timestamp` and `webhook-signature` headers.
Set `AI_WEBHOOKS_SIGNING_SECRET` to the endpoint's `whsec_...` secret to receive them; the previous
`X-OpenAI-Signature` scheme keeps working for other senders. Non-response events (e.g. `batch.completed`) now
return 200 and dispatch `OpenAiWebhookReceived` instead of being stored as response statuses.

### New capabilities

- Laravel 13 support (requires PHP 8.3+).
- Speaker diarization (voice analysis) via `Ai::diarize()` and `'action' => 'diarize'` in the unified builder.
- Low-level Audio API access via `Ai::audio()`.
- Every other OpenAI API resource via the `Ai` facade (see "OpenAI API Coverage" in the README), including the
  Administration API via `Ai::admin()` (set `OPENAI_ADMIN_KEY`).

---

## 3.1 (2026-02-04)

### Breaking changes

1) **Custom transports must implement `getContent()`**

If you provide your own `OpenAITransport`, you must add:

```php
public function getContent(string $path, array $headers = [], ?float $timeout = null): array;
```

Return an array with `content` (string) and `content_type` (string).

2) **Custom files repositories must implement `content()`**

If you implement `FilesRepositoryContract`, add:

```php
public function content(string $fileId): array;
```

3) **Queue tool execution (parallel mode)**

When `ai-assistant.tool_calling.parallel=true`, the queue executor now returns a queued placeholder instead of running tools inline.

### New capabilities

- New low-level repositories for Moderations, Batches, Realtime Sessions, Assistants, Vector Stores, Vector Store Files, and Vector Store File Batches.
- File content download support.
- Connection pool settings are now applied to the HTTP client.

---

## 3.0 (SSOT Architecture)

### What changed

- `Ai::responses()` is the unified API for text, audio, images, and tools.
- Legacy APIs are deprecated and will be removed in v4.0.
- Compat client and OpenAiRepository were removed.

### Action

- Start migrating legacy usage to `Ai::responses()`.
- Avoid using internal classes; use public facades (`Ai::responses()`, `Ai::conversations()`, `Ai::quick()`).

---

## Notes

- Always review `CHANGELOG.md` when upgrading.
- If you depend on internal contracts or repositories, expect them to evolve.
