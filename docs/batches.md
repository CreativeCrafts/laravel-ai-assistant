# Batches

The Batch API runs large numbers of requests asynchronously, within 24 hours, at a lower price than
synchronous calls. Good for nightly classification, bulk embeddings, translations and evaluations.

## 1. Build the input file

Each line of a `.jsonl` file is one request with a unique `custom_id`:

```php
use App\Models\Review;

$path = storage_path('app/batches/reviews-' . now()->format('YmdHis') . '.jsonl');
$handle = fopen($path, 'w');

Review::whereNull('sentiment')->lazyById()->each(function (Review $review) use ($handle) {
    fwrite($handle, json_encode([
        'custom_id' => "review-{$review->id}",
        'method' => 'POST',
        'url' => '/v1/responses',
        'body' => [
            'model' => 'gpt-5-mini',
            'instructions' => 'Classify the sentiment as positive, neutral or negative. Reply with one word.',
            'input' => $review->body,
        ],
    ]) . "\n");
});

fclose($handle);
```

## 2. Upload it and create the batch

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$file = Ai::files()->upload($path, 'batch');

$batch = Ai::batches()->create([
    'input_file_id' => $file['id'],
    'endpoint' => '/v1/responses',     // or /v1/chat/completions, /v1/embeddings, /v1/moderations, ...
    'completion_window' => '24h',
    'metadata' => ['job' => 'review-sentiment'],
]);
```

For files larger than 512 MB use `Ai::uploads()->uploadFile($path, 'batch')` (see [Files & uploads](files-and-uploads.md)).

## 3. Wait for completion

Either poll:

```php
$batch = Ai::batches()->retrieve($batch['id']);
// status: validating → in_progress → finalizing → completed (or failed / expired / cancelled)
$batch['request_counts']; // ['total' => 1000, 'completed' => 998, 'failed' => 2]
```

or, better, listen for the `batch.completed` webhook (see [Webhooks](webhooks.md)):

```php
use CreativeCrafts\LaravelAiAssistant\Events\OpenAiWebhookReceived;

Event::listen(function (OpenAiWebhookReceived $event) {
    if ($event->type === 'batch.completed') {
        ImportBatchResults::dispatch($event->data['id']);
    }
});
```

## 4. Download and process the results

```php
class ImportBatchResults implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $batchId) {}

    public function handle(): void
    {
        $batch = Ai::batches()->retrieve($this->batchId);

        $output = Ai::files()->content($batch['output_file_id'])['content'];

        foreach (preg_split('/\R/', trim($output)) as $line) {
            $row = json_decode($line, true);
            $id = (int) str_replace('review-', '', $row['custom_id']);

            $text = collect($row['response']['body']['output'] ?? [])
                ->flatMap(fn ($item) => $item['content'] ?? [])
                ->firstWhere('type', 'output_text')['text'] ?? null;

            Review::whereKey($id)->update(['sentiment' => strtolower(trim((string) $text))]);
        }

        if ($batch['error_file_id'] ?? null) {
            Storage::put("batches/{$this->batchId}-errors.jsonl", Ai::files()->content($batch['error_file_id'])['content']);
        }
    }
}
```

## Manage batches

```php
Ai::batches()->list(['limit' => 20]);
Ai::batches()->cancel('batch_123');
```

| Method | Endpoint |
|---|---|
| `create(array $payload)` | `POST /v1/batches` |
| `retrieve(string $batchId)` | `GET /v1/batches/{id}` |
| `list(array $params = [])` | `GET /v1/batches` |
| `cancel(string $batchId)` | `POST /v1/batches/{id}/cancel` |
