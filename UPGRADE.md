# Upgrade Guide

This guide focuses on what you must change when upgrading between major releases.
For hands-on code examples, see `MIGRATION.md`.

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
