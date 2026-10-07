# Fine-tuning, graders & evals

## Fine-tuning

### 1. Prepare and upload training data

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$path = storage_path('app/training/support.jsonl');
$out = fopen($path, 'w');

foreach ($examples as $example) {
    fwrite($out, json_encode(['messages' => [
        ['role' => 'system', 'content' => 'You are Acme\'s support assistant.'],
        ['role' => 'user', 'content' => $example->question],
        ['role' => 'assistant', 'content' => $example->answer],
    ]]) . "\n");
}
fclose($out);

$training = Ai::files()->upload($path, 'fine-tune');
// For files over 512 MB: Ai::uploads()->uploadFile($path, 'fine-tune')['file']
```

### 2. Start a job

```php
$job = Ai::fineTuningJobs()->create([
    'model' => 'gpt-4.1-mini',
    'training_file' => $training['id'],
    'suffix' => 'acme-support',
    'method' => [
        'type' => 'supervised',
        'supervised' => ['hyperparameters' => ['n_epochs' => 3]],
    ],
    'metadata' => ['team' => 'support'],
]);
```

### 3. Follow progress

```php
$job = Ai::fineTuningJobs()->retrieve($job['id']);
$job['status'];            // validating_files, queued, running, succeeded, failed, cancelled
$job['fine_tuned_model'];  // 'ft:gpt-4.1-mini:acme:acme-support:abc123' once succeeded

foreach (Ai::fineTuningJobs()->listEvents($job['id'], ['limit' => 50])['data'] as $event) {
    logger()->info($event['message']);
}

Ai::fineTuningJobs()->listCheckpoints($job['id']);
```

Use the `fine_tuning.job.succeeded` webhook to get notified instead of polling.

### 4. Use the model

```php
$answer = Ai::responses()
    ->model($job['fine_tuned_model'])
    ->input()
    ->message('How do I reset my password?')
    ->send()
    ->text;
```

### Manage jobs and models

```php
Ai::fineTuningJobs()->list(['limit' => 20, 'metadata' => ['team' => 'support']]);
Ai::fineTuningJobs()->pause('ftjob_123');
Ai::fineTuningJobs()->resume('ftjob_123');
Ai::fineTuningJobs()->cancel('ftjob_123');

Ai::models()->delete('ft:gpt-4.1-mini:acme:acme-support:abc123');
```

### Checkpoint permissions (Admin API key required)

Share a checkpoint with other projects. These calls use `OPENAI_ADMIN_KEY`:

```php
$checkpoint = 'ft:gpt-4.1-mini:acme:acme-support:abc123:ckpt-step-200';

Ai::fineTuningCheckpointPermissions()->create($checkpoint, ['project_ids' => ['proj_abc', 'proj_def']]);
Ai::fineTuningCheckpointPermissions()->list($checkpoint);
Ai::fineTuningCheckpointPermissions()->delete($checkpoint, 'cp_123');
```

## Graders

Graders score model output. Validate a grader definition, or run it on a sample:

```php
$grader = [
    'type' => 'string_check',
    'name' => 'exact_match',
    'input' => '{{sample.output_text}}',
    'reference' => '{{item.expected}}',
    'operation' => 'eq',
];

Ai::graders()->validate(['grader' => $grader]);

$result = Ai::graders()->run([
    'grader' => $grader,
    'model_sample' => 'Paris',
    'item' => ['expected' => 'Paris'],
]);

$result['reward']; // 1.0
```

Graders are also used for reinforcement fine-tuning (`'method' => ['type' => 'reinforcement', ...]`).

## Evals

Evals measure model quality on a dataset so you can compare prompts and models before shipping.

```php
$eval = Ai::evals()->create([
    'name' => 'Support answer quality',
    'data_source_config' => [
        'type' => 'custom',
        'item_schema' => [
            'type' => 'object',
            'properties' => ['question' => ['type' => 'string'], 'expected' => ['type' => 'string']],
            'required' => ['question', 'expected'],
        ],
        'include_sample_schema' => true,
    ],
    'testing_criteria' => [[
        'type' => 'string_check',
        'name' => 'matches expected',
        'input' => '{{sample.output_text}}',
        'reference' => '{{item.expected}}',
        'operation' => 'ilike',
    ]],
]);

$run = Ai::evalRuns()->create($eval['id'], [
    'name' => 'gpt-5-mini baseline',
    'data_source' => [
        'type' => 'responses',
        'model' => 'gpt-5-mini',
        'input_messages' => [
            'type' => 'template',
            'template' => [['role' => 'user', 'content' => '{{item.question}}']],
        ],
        'source' => ['type' => 'file_id', 'id' => $datasetFileId],   // uploaded with purpose "evals"
    ],
]);

$run = Ai::evalRuns()->retrieve($eval['id'], $run['id']);
$run['result_counts']; // ['passed' => 42, 'failed' => 8, ...]

foreach (Ai::evalRuns()->listOutputItems($eval['id'], $run['id'], ['status' => 'fail'])['data'] as $item) {
    // inspect failures
}
```

### Manage evals and runs

```php
Ai::evals()->list(['limit' => 20]);
Ai::evals()->retrieve('eval_123');
Ai::evals()->update('eval_123', ['name' => 'Support quality v2']);
Ai::evals()->delete('eval_123');

Ai::evalRuns()->list('eval_123');
Ai::evalRuns()->cancel('eval_123', 'evalrun_123');
Ai::evalRuns()->delete('eval_123', 'evalrun_123');
Ai::evalRuns()->retrieveOutputItem('eval_123', 'evalrun_123', 'outputitem_123');
```

## Methods

| Accessor | Methods |
|---|---|
| `Ai::fineTuningJobs()` | `create`, `retrieve`, `list`, `cancel`, `pause`, `resume`, `listEvents`, `listCheckpoints` |
| `Ai::fineTuningCheckpointPermissions()` | `create($checkpoint, $payload)`, `list($checkpoint, $params)`, `delete($checkpoint, $permissionId)` |
| `Ai::graders()` | `run($payload)`, `validate($payload)` |
| `Ai::evals()` | `create`, `retrieve`, `update`, `list`, `delete` |
| `Ai::evalRuns()` | `create($evalId, ...)`, `retrieve`, `list`, `delete`, `cancel`, `listOutputItems`, `retrieveOutputItem` |
