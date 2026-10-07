# Administration API

Manage your OpenAI organisation from Laravel: projects, members, API keys, roles, spend controls, usage
and costs. These endpoints need an **Admin API key** (create one under Organization settings → Admin keys):

```env
OPENAI_ADMIN_KEY=sk-admin-...
```

All repositories hang off `Ai::admin()`:

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$admin = Ai::admin();
```

## Usage and costs

Build a cost dashboard or a daily budget alert:

```php
$costs = Ai::admin()->usage()->costs([
    'start_time' => now()->subDays(30)->startOfDay()->timestamp,
    'bucket_width' => '1d',
    'group_by' => ['project_id'],
    'limit' => 31,
]);

$total = collect($costs['data'])
    ->flatMap(fn ($bucket) => $bucket['results'])
    ->sum(fn ($result) => $result['amount']['value']);
```

```php
$tokens = Ai::admin()->usage()->completions([
    'start_time' => now()->subDay()->timestamp,
    'group_by' => ['model'],
]);
```

Other usage reports: `embeddings()`, `moderations()`, `images()`, `audioSpeeches()`, `audioTranscriptions()`,
`vectorStores()`, `codeInterpreterSessions()`, `fileSearchCalls()`, `webSearchCalls()`.

### Daily budget alert (scheduled)

```php
// routes/console.php
Schedule::call(function () {
    $spent = collect(Ai::admin()->usage()->costs(['start_time' => now()->startOfDay()->timestamp])['data'])
        ->flatMap(fn ($b) => $b['results'])
        ->sum(fn ($r) => $r['amount']['value']);

    if ($spent > 50) {
        Notification::route('slack', config('services.slack.ops'))->notify(new OpenAiBudgetExceeded($spent));
    }
})->hourly();
```

## Projects

```php
$project = Ai::admin()->projects()->create(['name' => 'Customer support bot']);

Ai::admin()->projects()->list(['limit' => 20, 'include_archived' => false]);
Ai::admin()->projects()->retrieve($project['id']);
Ai::admin()->projects()->update($project['id'], ['name' => 'Support bot']);
Ai::admin()->projects()->archive($project['id']);
```

### Project members, groups and service accounts

```php
$projectId = 'proj_abc';

Ai::admin()->projectUsers()->create($projectId, ['user_id' => 'user_123', 'role' => 'member']);
Ai::admin()->projectUsers()->list($projectId);
Ai::admin()->projectUsers()->update($projectId, 'user_123', ['role' => 'owner']);
Ai::admin()->projectUsers()->delete($projectId, 'user_123');

Ai::admin()->projectGroups()->create($projectId, ['group_id' => 'group_123', 'role' => 'member']);

// Machine credentials for a deployment
$account = Ai::admin()->projectServiceAccounts()->create($projectId, ['name' => 'production-api']);
$account['api_key']['value']; // shown once
```

### Project API keys, rate limits and permissions

```php
Ai::admin()->projectApiKeys()->list($projectId);
Ai::admin()->projectApiKeys()->delete($projectId, 'key_123');

Ai::admin()->projectRateLimits()->list($projectId);
Ai::admin()->projectRateLimits()->update($projectId, 'rl-gpt-5', ['max_requests_per_1_minute' => 500]);

Ai::admin()->projectPermissions()->retrieveModelPermissions($projectId);
Ai::admin()->projectPermissions()->updateModelPermissions($projectId, [/* allowed models */]);
Ai::admin()->projectPermissions()->retrieveHostedToolPermissions($projectId);
```

## Users, invites and groups

```php
Ai::admin()->users()->list();
Ai::admin()->users()->update('user_123', ['role' => 'reader']);
Ai::admin()->users()->delete('user_123');

Ai::admin()->invites()->create(['email' => 'dev@example.com', 'role' => 'reader']);
Ai::admin()->invites()->list();

$group = Ai::admin()->groups()->create(['name' => 'Backend team']);
Ai::admin()->groups()->addUser($group['id'], ['user_id' => 'user_123']);
Ai::admin()->groups()->listUsers($group['id']);
```

## Roles

```php
Ai::admin()->roles()->list();
Ai::admin()->roles()->create([/* role definition */]);
Ai::admin()->roles()->listForProject($projectId);

Ai::admin()->users()->assignRole('user_123', ['role_id' => 'role_123']);
Ai::admin()->groups()->assignRole($group['id'], ['role_id' => 'role_123']);
Ai::admin()->projectUsers()->assignRole($projectId, 'user_123', ['role_id' => 'role_123']);
```

## Spend controls

```php
Ai::admin()->spendLimit()->retrieve();
Ai::admin()->spendLimit()->updateForProject($projectId, [/* limit */]);

Ai::admin()->spendAlerts()->create([/* threshold and recipients */]);
Ai::admin()->spendAlerts()->listForProject($projectId);
```

## Security and compliance

```php
// Audit logs
Ai::admin()->auditLogs()->list(['event_types' => ['api_key.created'], 'limit' => 50]);

// Admin API keys
Ai::admin()->apiKeys()->list();

// mTLS certificates
Ai::admin()->certificates()->list();
Ai::admin()->certificates()->activateForProject($projectId, ['certificate_ids' => ['cert_123']]);

// Data retention and external storage
Ai::admin()->dataRetention()->retrieve();
Ai::admin()->dataRetention()->updateForProject($projectId, [/* policy */]);
Ai::admin()->externalStorage()->list();
Ai::admin()->externalStorage()->validate('es_123');
```

## Available repositories

| `Ai::admin()->…` | Methods |
|---|---|
| `apiKeys()` | `create`, `retrieve`, `list`, `delete` |
| `auditLogs()` | `list` |
| `certificates()` | `create`, `retrieve`, `update`, `list`, `delete`, `activate`, `deactivate`, `listForProject`, `activateForProject`, `deactivateForProject` |
| `dataRetention()` | `retrieve`, `update`, `retrieveForProject`, `updateForProject` |
| `externalStorage()` | `create`, `retrieve`, `list`, `delete`, `validate` |
| `groups()` | `create`, `retrieve`, `update`, `list`, `delete`, `addUser`, `retrieveUser`, `listUsers`, `removeUser`, `assignRole`, `retrieveRole`, `listRoles`, `unassignRole` |
| `invites()` | `create`, `retrieve`, `list`, `delete` |
| `projects()` | `create`, `retrieve`, `update`, `list`, `archive` |
| `projectApiKeys()` | `retrieve`, `list`, `delete` |
| `projectGroups()` | `create`, `retrieve`, `list`, `delete`, `assignRole`, `retrieveRole`, `listRoles`, `unassignRole` |
| `projectPermissions()` | `retrieveModelPermissions`, `updateModelPermissions`, `deleteModelPermissions`, `retrieveHostedToolPermissions`, `updateHostedToolPermissions` |
| `projectRateLimits()` | `list`, `update` |
| `projectServiceAccounts()` | `create`, `retrieve`, `update`, `list`, `delete`, `createApiKey` |
| `projectUsers()` | `create`, `retrieve`, `update`, `list`, `delete`, `assignRole`, `retrieveRole`, `listRoles`, `unassignRole` |
| `roles()` | `create`, `retrieve`, `update`, `list`, `delete`, `createForProject`, `retrieveForProject`, `updateForProject`, `listForProject`, `deleteForProject` |
| `spendAlerts()` | `create`, `retrieve`, `update`, `list`, `delete`, and `…ForProject` variants |
| `spendLimit()` | `retrieve`, `update`, `delete`, and `…ForProject` variants |
| `usage()` | `completions`, `embeddings`, `moderations`, `images`, `audioSpeeches`, `audioTranscriptions`, `vectorStores`, `codeInterpreterSessions`, `fileSearchCalls`, `webSearchCalls`, `costs` |
| `users()` | `retrieve`, `update`, `list`, `delete`, `assignRole`, `retrieveRole`, `listRoles`, `unassignRole` |
