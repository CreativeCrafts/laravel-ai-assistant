# Laravel AI Assistant Documentation

Everything the package offers, with copy-paste examples. Start with **Getting started**, then jump to the
guide for the feature you are building. Every guide is self-contained and can be read on its own.

## Start here

| Guide | What you'll learn |
|---|---|
| [Getting started](getting-started.md) | Install, configure your API key, send your first request |
| [Core concepts](core-concepts.md) | The two layers of the package (builders and repositories), return values, pagination, binary content |
| [Configuration](configuration.md) | Every config section and environment variable, presets and environment overlays |

## Generating content

| Guide | What you'll learn |
|---|---|
| [Responses API (`Ai::responses()`)](responses.md) | The unified builder for text, audio and images, structured output, stored responses, token counting |
| [Chat sessions & tool calling](chat-sessions-and-tools.md) | `Ai::chat()`, `Ai::quick()`, function calling, file search, code interpreter |
| [Streaming](streaming.md) | Streaming tokens to the CLI, to a browser (SSE / Inertia + React) and over Laravel Reverb |
| [Conversations](conversations.md) | Multi-turn state stored by OpenAI, conversation items |
| [Chat Completions & legacy Completions](chat-completions.md) | `/v1/chat/completions` and `/v1/completions` |
| [Audio](audio.md) | Transcription, translation, text-to-speech, streaming audio, custom voices |
| [Speaker diarization](speaker-diarization.md) | Who spoke when, known speakers, transcripts and captions |
| [Images](images.md) | Generation, editing, variations, partial image streaming |
| [Videos (Sora)](videos.md) | Create, remix, edit, extend and download videos, characters |
| [Embeddings & vector stores](embeddings-and-vector-stores.md) | Embeddings, vector stores, semantic search, a full RAG example |

## Data, jobs and models

| Guide | What you'll learn |
|---|---|
| [Files & uploads](files-and-uploads.md) | Upload, list, download and delete files, multi-part uploads up to 8 GB |
| [Batches](batches.md) | Run thousands of requests asynchronously at a lower cost |
| [Fine-tuning, graders & evals](fine-tuning-and-evals.md) | Fine-tuning jobs, checkpoints, graders, evals and eval runs |
| [Models & moderation](models-and-moderation.md) | Listing models, moderation, safety, decisions, content provenance |

## Realtime, agents and platform

| Guide | What you'll learn |
|---|---|
| [Realtime & Live](realtime.md) | Ephemeral client secrets, WebRTC calls, SIP call control, transcription sessions |
| [Agents, skills, containers & ChatKit](agents.md) | Agents (beta), agent sessions, environments, vaults, skills, containers, ChatKit |
| [Webhooks](webhooks.md) | Receiving signed OpenAI webhooks, managing webhook endpoints |
| [Administration API](administration.md) | Projects, users, API keys, roles, usage and costs |

## Running in production

| Guide | What you'll learn |
|---|---|
| [Error handling & retries](error-handling.md) | Exceptions, retry/backoff, idempotency and timeouts |
| [Testing your application](testing.md) | Faking the package in Pest/PHPUnit tests without hitting the API |
| [Operations](operations.md) | Artisan commands, health checks, observability, caching, persistence, queues |
| [API reference](api-reference.md) | Every accessor and method signature on one page |

## Other resources

- [`examples/`](../examples) – runnable scripts
- [`MIGRATION.md`](../MIGRATION.md) – moving from the legacy `AiAssistant` facade to `Ai::responses()`
- [`UPGRADE.md`](../UPGRADE.md) – breaking changes between releases
- [`CHANGELOG.md`](../CHANGELOG.md) – release notes
