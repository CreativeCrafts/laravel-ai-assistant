# Models, moderation & safety

## Models

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$models = collect(Ai::models()->list()['data'])->pluck('id')->sort()->values();

Ai::models()->retrieve('gpt-5-mini');          // ['id' => 'gpt-5-mini', 'owned_by' => 'openai', ...]
Ai::models()->delete('ft:gpt-4.1-mini:acme::abc123');   // fine-tuned models you own
```

Cache the model list if you show it in a UI:

```php
$models = Cache::remember('openai.models', now()->addDay(), fn () => Ai::models()->list()['data']);
```

## Moderation

Check user-generated text and images against OpenAI's usage policies before you store or process them.

```php
$result = Ai::moderations()->create([
    'model' => 'omni-moderation-latest',
    'input' => $request->input('comment'),
]);

$verdict = $result['results'][0];

if ($verdict['flagged']) {
    $categories = array_keys(array_filter($verdict['categories']));   // ['harassment', ...]
    abort(422, 'Your comment violates our community guidelines.');
}
```

### Text and images together

```php
Ai::moderations()->create([
    'model' => 'omni-moderation-latest',
    'input' => [
        ['type' => 'text', 'text' => $post->caption],
        ['type' => 'image_url', 'image_url' => ['url' => $post->image_url]],
    ],
]);
```

### As a validation rule

```php
namespace App\Rules;

use Closure;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Contracts\Validation\ValidationRule;

class NotFlagged implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $result = Ai::moderations()->create(['model' => 'omni-moderation-latest', 'input' => (string) $value]);

        if ($result['results'][0]['flagged'] ?? false) {
            $fail('The :attribute contains content that is not allowed.');
        }
    }
}

// $request->validate(['body' => ['required', 'string', new NotFlagged]]);
```

## Decisions

Typed answers to classification and scoring questions over text and images, returned in question order:

```php
$decision = Ai::decisions()->create($payload);   // POST /v1/decisions
```

The payload (the shared input plus an ordered list of questions) is passed to the API unchanged; see the
OpenAI API reference for the current schema.

## Content provenance checks

Check whether an image or audio file contains known OpenAI provenance signals (for example to label
AI-generated media):

```php
$check = Ai::contentProvenanceChecks()->create([
    'file' => $request->file('upload'),   // path, UploadedFile or stream
]);
```

## Safety alerts and cases

Retrieve safety alerts and cases raised for your organisation (for example from a webhook notification):

```php
Ai::safety()->retrieveAlert('alert_123');
Ai::safety()->retrieveCase('case_123');
```

## Methods

| Method | Endpoint |
|---|---|
| `Ai::models()->list()` | `GET /v1/models` |
| `Ai::models()->retrieve(string $model)` | `GET /v1/models/{model}` |
| `Ai::models()->delete(string $model)` | `DELETE /v1/models/{model}` |
| `Ai::moderations()->create(array $payload)` | `POST /v1/moderations` |
| `Ai::decisions()->create(array $payload)` | `POST /v1/decisions` |
| `Ai::contentProvenanceChecks()->create(array $payload)` | `POST /v1/content_provenance_checks` (multipart `file`) |
| `Ai::safety()->retrieveAlert(string $alertId)` | `GET /v1/safety/alerts/{id}` |
| `Ai::safety()->retrieveCase(string $caseId)` | `GET /v1/safety/cases/{id}` |
