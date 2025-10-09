# Contributing to Laravel AI Assistant

Thank you for your interest in contributing! This guide explains how to set up your environment, coding standards, test strategy, and the PR process.

## Table of Contents
- Getting Started
- Development Workflow
- Coding Standards
- Static Analysis & QA
- Testing
- Backward Compatibility
- Commit & PR Guidelines

## Getting Started
1. Fork the repo and clone your fork.
2. Install dependencies:
   - `composer install`
3. Ensure your PHP version matches composer.json (PHP 8.2 or 8.3).

## Development Workflow
- The package is auto-discovered via the Service Provider. You can also test within a Laravel app by requiring your local path.
- Configuration lives in `config/ai-assistant.php` plus environment overlays in `config/environments/*`.
- Persistence driver defaults to in-memory; switch to `eloquent` only if you have run the package migrations.

## Coding Standards
- Use Laravel Pint for formatting: `composer format`
- Keep public APIs stable; document changes in README or docs/ and update tests.
- Favor small, focused PRs with clear rationale.

### Public API Contract
- **AiManager is the sole public facade** for AI operations (`quick`, `chat`, `stream`, `complete`).
- **Do not add new public methods to AssistantService**. It is an internal implementation detail marked with `@internal`.
- All user-facing functionality should be exposed through `AiManager` to ensure a consistent, discoverable API.
- Legacy methods on `AssistantService` are deprecated. New code should use `AiManager::complete()`.
- When adding features, extend `AiManager` or its related builders, not `AssistantService`.

## Static Analysis & QA
- Run static analysis: `composer analyse`
- Validate composer metadata: `composer validate-composer`
- Optional dependency checks: `composer check-deps` and `composer audit` (may require Composer v2.7+)
- CI helper (validate, audit, analyse, coverage): `composer ci`

## Testing
- Run tests: `composer test`
- With coverage: `composer test-coverage` (HTML in build/coverage/)
- Tests use Pest + Orchestra Testbench. See `tests/README.md` for details.
- Do not perform real network calls in tests. Use fakes in `tests/Fakes/*` and set `config(['ai-assistant.mock_responses' => true])`.
- When switching persistence to eloquent in tests, run package migrations (use in-memory SQLite for speed).

## Backward Compatibility (BC)
- The package maintains BC with legacy Assistants/Threads areas via shims. New work should prefer Responses + Conversations.
- When changing deprecated pathways, ensure tests for BC shims continue to pass (see tests/Integration/*).

## Commit & PR Guidelines
- Write descriptive commit messages (imperative mood: "Add X", "Fix Y").
- Include tests for bug fixes and new features.
- Update relevant docs: `docs/ARCHITECTURE.md`, `docs/CODEMAP.md`, `tests/README.md`, and README when applicable.
- Ensure `composer ci` passes locally before opening a PR.

Thank you for contributing and helping improve maintainability and developer experience!