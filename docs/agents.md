# Agents, skills, containers & ChatKit

These APIs let OpenAI run multi-step work for you in hosted environments. They are newer (several are in
beta), so the package passes payloads through unchanged: check the OpenAI API reference for the current
request fields. Beta headers (`OpenAI-Beta: agents=v1`, `OpenAI-Beta: chatkit_beta=v1`) are added for you.

## Agents (beta)

An agent is a reusable definition (model, instructions, tools). You run it in **sessions**.

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$agent = Ai::agents()->create([
    'name' => 'Report analyst',
    'model' => 'gpt-5',
    'instructions' => 'Analyse uploaded reports and produce a concise executive summary.',
]);

Ai::agents()->list(['limit' => 20]);
Ai::agents()->retrieve($agent['id']);
Ai::agents()->update($agent['id'], ['instructions' => 'Also list three risks.']);
Ai::agents()->delete($agent['id']);
```

### Sessions

```php
$session = Ai::agentSessions()->create([
    'agent_id' => $agent['id'],
    'environment' => ['type' => 'openai_hosted'],
    'input' => 'Summarise the attached Q3 report',
]);

// Send more input or control events to a running session
Ai::agentSessions()->createEvent($session['id'], [/* event */]);

// Follow progress live
foreach (Ai::agentSessions()->streamEvents($session['id']) as $event) {
    // broadcast to the UI, log, ...
}

// Or create and stream in one call
foreach (Ai::agentSessions()->stream(['agent_id' => $agent['id'], 'input' => 'Hello']) as $event) {
    // ...
}
```

Inspect what happened:

```php
$id = $session['id'];

Ai::agentSessions()->retrieve($id);
Ai::agentSessions()->listItems($id);
Ai::agentSessions()->listTurns($id);
Ai::agentSessions()->retrieveTurn($id, 'turn_123');
Ai::agentSessions()->listTurnItems($id, 'turn_123');
Ai::agentSessions()->listTraces($id);

// Subagents spawned by the session
Ai::agentSessions()->listSubagents($id);
Ai::agentSessions()->retrieveSubagent($id, 'sub_123');
Ai::agentSessions()->listSubagentItems($id, 'sub_123');
Ai::agentSessions()->listSubagentTurns($id, 'sub_123');
Ai::agentSessions()->retrieveSubagentTurn($id, 'sub_123', 'turn_123');
Ai::agentSessions()->listSubagentTurnItems($id, 'sub_123', 'turn_123');
```

### Artifacts produced by a session

```php
foreach (Ai::agentSessions()->listArtifacts($id)['data'] as $artifact) {
    $file = Ai::agentSessions()->artifactContent($id, $artifact['id']);
    Storage::put("agent-artifacts/{$id}/{$artifact['id']}", $file['content']);
}

Ai::agentSessions()->retrieveArtifact($id, 'art_123');
Ai::agentSessions()->deleteArtifact($id, 'art_123');
```

Session management: `update($id, $payload)`, `list($params)`, `delete($id)`.

### Environments and templates

```php
Ai::agentEnvironments()->retrieve('env_123');
Ai::agentEnvironments()->createFile('env_123', [/* file */]);
Ai::agentEnvironments()->listFiles('env_123');

$template = Ai::agentEnvironments()->createTemplate([/* template definition */]);
Ai::agentEnvironments()->listTemplates();
Ai::agentEnvironments()->retrieveTemplate($template['id']);
Ai::agentEnvironments()->updateTemplate($template['id'], [/* changes */]);
Ai::agentEnvironments()->deleteTemplate($template['id']);
```

### Vaults and credentials

Vaults keep secrets an agent may use (API tokens, passwords) out of prompts:

```php
$vault = Ai::vaults()->create(['name' => 'CRM access']);

$credential = Ai::vaults()->createCredential($vault['id'], [/* credential definition */]);
Ai::vaults()->listCredentials($vault['id']);
Ai::vaults()->retrieveCredential($vault['id'], $credential['id']);
Ai::vaults()->updateCredential($vault['id'], $credential['id'], [/* changes */]);
Ai::vaults()->deleteCredential($vault['id'], $credential['id']);

Ai::vaults()->list();
Ai::vaults()->retrieve($vault['id']);
Ai::vaults()->delete($vault['id']);
```

<!-- Vault update() is being added in a follow-up release; document it here once it ships. -->

## Skills

Skills are reusable bundles of instructions and files for the shell tool. Each update creates an immutable
version.

```php
$skill = Ai::skills()->create([
    'name' => 'invoice-parser',
    'files' => [storage_path('app/skills/invoice-parser/SKILL.md'), storage_path('app/skills/invoice-parser/parse.py')],
]);

$version = Ai::skills()->createVersion($skill['id'], ['files' => [/* updated files */]]);
Ai::skills()->update($skill['id'], ['default_version' => $version['version']]);

Ai::skills()->list();
Ai::skills()->retrieve($skill['id']);
Ai::skills()->listVersions($skill['id']);
Ai::skills()->retrieveVersion($skill['id'], '2');

$zip = Ai::skills()->content($skill['id']);                // ['content' => zip bytes, ...]
$zip = Ai::skills()->versionContent($skill['id'], '2');

Ai::skills()->deleteVersion($skill['id'], '1');
Ai::skills()->delete($skill['id']);
```

## Containers

Containers are sandboxed environments used by the code interpreter and shell tools.

```php
$container = Ai::containers()->create(['name' => 'analysis', 'expires_after' => ['anchor' => 'last_active_at', 'minutes' => 20]]);

$file = Ai::containerFiles()->create($container['id'], ['file' => storage_path('app/data/sales.csv')]);
// or reference an uploaded file: ['file_id' => 'file_abc123']

Ai::containerFiles()->list($container['id']);
Ai::containerFiles()->retrieve($container['id'], $file['id']);
$csv = Ai::containerFiles()->content($container['id'], $file['id']);   // ['content' => ..., 'content_type' => ...]
Ai::containerFiles()->delete($container['id'], $file['id']);

Ai::containers()->list();
Ai::containers()->retrieve($container['id']);
Ai::containers()->delete($container['id']);
```

Use the container with the code interpreter tool in a Responses API request:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5',
    'tools' => [['type' => 'code_interpreter', 'container' => $container['id']]],
    'input' => 'Chart monthly revenue from sales.csv and describe the trend.',
]);
```

## ChatKit (beta)

ChatKit embeds a ready-made chat UI backed by an OpenAI workflow. Your backend creates a session and hands
its client secret to the frontend widget:

```php
Route::post('/chatkit/session', function (Request $request) {
    $session = Ai::chatKit()->createSession([
        'workflow' => ['id' => config('services.openai.chatkit_workflow')],
        'user' => (string) $request->user()->id,
    ]);

    return ['client_secret' => $session['client_secret']];
})->middleware('auth');
```

Manage sessions and threads:

```php
Ai::chatKit()->cancelSession('cksess_123');

Ai::chatKit()->listThreads(['user' => (string) auth()->id(), 'limit' => 20]);
Ai::chatKit()->retrieveThread('cthr_123');
Ai::chatKit()->listThreadItems('cthr_123');
Ai::chatKit()->deleteThread('cthr_123');
```

## Assistants API (deprecated)

`Ai::assistants()` (create, retrieve, update, delete, list) remains for existing integrations. OpenAI has
announced the Assistants API shutdown for August 26, 2026; migrate to Responses + Conversations (see
[`MIGRATION.md`](../MIGRATION.md)).
