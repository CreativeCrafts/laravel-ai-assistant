# Tests Guide

This package uses Pest + Orchestra Testbench for fast, isolated testing of a Laravel package. This guide explains how to run tests, where things live, and how to write reliable tests that avoid real network calls.

## Quickstart
- Run all tests: `composer test`
- Run with coverage (HTML in build/coverage/): `composer test-coverage`
- Static analysis: `composer analyse`
- Code style (Laravel Pint): `composer format`
- CI helper (validate, audit, analyse, coverage): `composer ci`

## Test Bootstrap
- tests/Pest.php applies the shared TestCase to the entire tests/ tree.
- tests/TestCase.php extends Orchestra Testbench and configures:
  - `database.default = testing`
  - `ai-assistant.api_key = 'test_key_123'` (bypasses ServiceProvider OPENAI_API_KEY requirement)
- Environment overlays in config/environments/testing.php default to mock behavior and isolation.

## Structure
- tests/Config — config overlay and validation tests
- tests/Integration — end-to-end flows and BC shims (e.g., BcShimLegacyThreadsTest)
- tests/Unit — focused service/repository tests (e.g., LoggingServiceTest)
- tests/Fakes — HTTP/persistence fakes (e.g., FakeFilesRepository)
- tests/DataFactories — reusable DTO/data factories for deterministic inputs

## Writing Tests
- Prefer Pest style tests placed under tests/ with file names ending in `*Test.php`.
- All tests automatically use the shared TestCase via tests/Pest.php.
- For tests needing custom config, call `config([...])` inside the test.

Example:
```php
it('generates a response using a fake repository', function () {
    config(['ai-assistant.mock_responses' => true]);
    // Arrange fakes or inputs
    // Act & Assert
    expect(true)->toBeTrue();
});
```

## Avoiding Real Network Calls
- Use fakes under tests/Fakes/* and set `config(['ai-assistant.mock_responses' => true])`.
- Rebind contracts to fakes when needed using Laravel's container in tests:
```php
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\FakeFilesRepository;

app()->bind(FilesRepositoryContract::class, fn () => new FakeFilesRepository());
```

## Persistence Driver in Tests
- Default driver is in-memory (no DB writes).
- To validate Eloquent persistence flows:
```php
config(['ai-assistant.persistence.driver' => 'eloquent']);
// Ensure migrations are loaded. With Testbench, you can run:
$this->artisan('migrate', ['--database' => 'testing'])->run();
```
- Use in-memory SQLite through Testbench for speed and isolation. Publish models only if customizing.

## Running a Subset of Tests
- By file/folder: `vendor/bin/pest tests/Unit`
- By name: `vendor/bin/pest -d 'logging service'`

## Random Order & Reproducibility
- Tests run in random order (see phpunit.xml.dist). Pest prints a Random Order Seed when enabled; re-run with the same seed to reproduce flaky runs.

## Coverage & Reports
- Coverage HTML: build/coverage/index.html
- JUnit/XML and Clover reports configured via phpunit.xml.dist (consumed by CI)

## Common Pitfalls
- Forgetting to set `ai-assistant.api_key` for tests that boot the app outside the shared TestCase — set it via `config([...])` or env.
- Enabling `eloquent` driver without running the package migrations.
- Enabling `queue` executor for tool calls without a working queue driver — use `sync` for local tests.

## Useful References
- src/LaravelAiAssistantServiceProvider.php — bindings and config validation
- src/Services/* — orchestration and business logic
- src/Repositories/Http/* — HTTP calls to OpenAI endpoints
- config/ai-assistant.php — all runtime options
