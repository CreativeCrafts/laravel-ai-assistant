<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\ProjectsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Admin\UsageRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Agents\AgentSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ChatCompletionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\EmbeddingsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FineTuningCheckpointPermissionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VideosRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use CreativeCrafts\LaravelAiAssistant\Services\AdminManager;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;
use CreativeCrafts\LaravelAiAssistant\Transport\OpenAITransport;

it('exposes every API resource on the Ai facade', function () {
    expect(Ai::embeddings())->toBeInstanceOf(EmbeddingsRepositoryContract::class)
        ->and(Ai::chatCompletions())->toBeInstanceOf(ChatCompletionsRepositoryContract::class)
        ->and(Ai::videos())->toBeInstanceOf(VideosRepositoryContract::class)
        ->and(Ai::agentSessions())->toBeInstanceOf(AgentSessionsRepositoryContract::class)
        ->and(Ai::fineTuningCheckpointPermissions())->toBeInstanceOf(FineTuningCheckpointPermissionsRepositoryContract::class)
        ->and(Ai::admin())->toBeInstanceOf(AdminManager::class)
        ->and(Ai::admin()->projects())->toBeInstanceOf(ProjectsRepositoryContract::class)
        ->and(Ai::admin()->usage())->toBeInstanceOf(UsageRepositoryContract::class);
});

it('documents every public AiManager accessor on the facade', function () {
    $docblock = (string)(new ReflectionClass(Ai::class))->getDocComment();
    $methods = array_filter(
        (new ReflectionClass(AiManager::class))->getMethods(ReflectionMethod::IS_PUBLIC),
        fn (ReflectionMethod $method) => !$method->isConstructor()
    );

    foreach ($methods as $method) {
        expect($docblock)->toContain(' ' . $method->getName() . '(');
    }
});

it('signs admin requests with the configured admin API key', function () {
    config()->set('ai-assistant.admin_api_key', 'sk-admin-from-config');
    $http = RecordingHttpClient::json(['object' => 'list', 'data' => []]);
    app()->instance(OpenAITransport::class, $http->transport());

    Ai::admin()->projects()->list(['limit' => 5]);

    expect($http->lastUri())->toBe('https://api.openai.com/v1/organization/projects?limit=5')
        ->and($http->lastRequest()->getHeaderLine('Authorization'))->toBe('Bearer sk-admin-from-config');
});

it('falls back to the regular API key when no admin key is configured', function () {
    config()->set('ai-assistant.admin_api_key', null);
    $http = RecordingHttpClient::json(['object' => 'list', 'data' => []]);
    app()->instance(OpenAITransport::class, $http->transport());

    Ai::admin()->auditLogs()->list();

    expect($http->lastRequest()->hasHeader('Authorization'))->toBeFalse();
});

it('streams chat completions through the facade', function () {
    $http = RecordingHttpClient::sse('{"id":"chatcmpl_1","choices":[{"delta":{"content":"Hi"}}]}');
    app()->instance(OpenAITransport::class, $http->transport());

    $chunks = iterator_to_array(Ai::chatCompletions()->stream([
        'model' => 'gpt-5',
        'messages' => [['role' => 'user', 'content' => 'Hello']],
    ]), false);

    expect($chunks[0]['choices'][0]['delta']['content'])->toBe('Hi')
        ->and($http->lastJson()['stream'])->toBeTrue();
});
