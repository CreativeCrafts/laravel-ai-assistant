# Embeddings & vector stores

Two ways to search your own content by meaning:

- **Embeddings**: you store the vectors yourself (MySQL, pgvector, Meilisearch, …) and do the similarity
  search in your app.
- **Vector stores**: OpenAI stores, chunks and indexes your files and searches them for you; the model can use
  them directly through the `file_search` tool.

## Embeddings

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$result = Ai::embeddings()->create([
    'model' => 'text-embedding-3-small',
    'input' => ['How do I reset my password?', 'Where can I download invoices?'],
]);

$vectors = array_column($result['data'], 'embedding'); // list<list<float>>
$tokens = $result['usage']['total_tokens'];
```

Pass `'dimensions' => 512` with `text-embedding-3-*` models to get shorter vectors.

### Semantic search with MySQL

A simple approach that works well for a few thousand rows: store vectors as JSON and rank in PHP.

```php
// Migration
Schema::create('help_articles', function (Blueprint $table) {
    $table->id();
    $table->string('title');
    $table->text('body');
    $table->json('embedding')->nullable();
    $table->timestamps();
});
```

```php
namespace App\Services;

use App\Models\HelpArticle;
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;
use Illuminate\Support\Collection;

class HelpSearch
{
    public function index(HelpArticle $article): void
    {
        $article->update(['embedding' => $this->embed($article->title . "\n\n" . $article->body)]);
    }

    /** @return Collection<int, HelpArticle> */
    public function search(string $query, int $limit = 5): Collection
    {
        $q = $this->embed($query);

        return HelpArticle::whereNotNull('embedding')->get()
            ->map(fn (HelpArticle $a) => tap($a, fn () => $a->score = $this->cosine($q, $a->embedding)))
            ->sortByDesc('score')
            ->take($limit)
            ->values();
    }

    /** @return list<float> */
    private function embed(string $text): array
    {
        return Ai::embeddings()->create(['model' => 'text-embedding-3-small', 'input' => $text])['data'][0]['embedding'];
    }

    private function cosine(array $a, array $b): float
    {
        $dot = $na = $nb = 0.0;
        foreach ($a as $i => $v) {
            $dot += $v * $b[$i];
            $na += $v * $v;
            $nb += $b[$i] * $b[$i];
        }

        return $dot / (sqrt($na) * sqrt($nb) ?: 1);
    }
}
```

Cast `embedding` to `array` on the model. For large datasets use a dedicated vector index, or let OpenAI do
it with a vector store (below).

## Vector stores

### Create a store and add files

```php
// 1. Create the store
$store = Ai::vectorStores()->create([
    'name' => 'Help centre',
    'expires_after' => ['anchor' => 'last_active_at', 'days' => 30],   // optional
]);

// 2. Upload a file and attach it
$file = Ai::files()->upload(storage_path('app/docs/refund-policy.pdf'), 'assistants');

Ai::vectorStoreFiles()->create($store['id'], [
    'file_id' => $file['id'],
    'attributes' => ['department' => 'billing'],   // optional, filterable
]);
```

### Add many files at once

```php
$fileIds = collect(Storage::files('docs'))
    ->map(fn (string $path) => Ai::files()->upload(Storage::path($path), 'assistants')['id'])
    ->all();

$batch = Ai::vectorStoreFileBatches()->create($store['id'], ['file_ids' => $fileIds]);

// Poll until processing finishes
do {
    sleep(2);
    $batch = Ai::vectorStoreFileBatches()->retrieve($store['id'], $batch['id']);
} while (in_array($batch['status'], ['in_progress'], true));

$batch['file_counts']; // ['completed' => 12, 'failed' => 0, ...]
```

### Search a store directly

```php
$hits = Ai::vectorStores()->search($store['id'], [
    'query' => 'How long do refunds take?',
    'max_num_results' => 5,
    'filters' => ['type' => 'eq', 'key' => 'department', 'value' => 'billing'],
]);

foreach ($hits['data'] as $hit) {
    echo $hit['filename'], ' (', round($hit['score'], 2), "):\n";
    echo collect($hit['content'])->pluck('text')->implode("\n"), "\n\n";
}
```

### Let the model answer from the store (RAG)

```php
$answer = Ai::chat($request->input('question'))
    ->instructions('Answer only from the help centre documents. If the answer is not there, say so.')
    ->includeFileSearchTool([$store['id']])
    ->send()
    ->text;
```

Or with full control over the Responses API payload:

```php
use CreativeCrafts\LaravelAiAssistant\Contracts\ResponsesRepositoryContract;

$response = app(ResponsesRepositoryContract::class)->createResponse([
    'model' => 'gpt-5-mini',
    'input' => 'How long do refunds take?',
    'tools' => [[
        'type' => 'file_search',
        'vector_store_ids' => [$store['id']],
        'max_num_results' => 8,
    ]],
    'include' => ['file_search_call.results'],
]);
```

### Manage stores and files

```php
Ai::vectorStores()->list(['limit' => 20]);
Ai::vectorStores()->retrieve('vs_123');
Ai::vectorStores()->update('vs_123', ['name' => 'Help centre (EN)']);
Ai::vectorStores()->delete('vs_123');                       // bool

Ai::vectorStoreFiles()->list('vs_123', ['filter' => 'completed']);
Ai::vectorStoreFiles()->retrieve('vs_123', 'file_abc');
Ai::vectorStoreFiles()->update('vs_123', 'file_abc', ['attributes' => ['department' => 'sales']]);
Ai::vectorStoreFiles()->content('vs_123', 'file_abc');     // parsed text chunks
Ai::vectorStoreFiles()->delete('vs_123', 'file_abc');      // bool (the file itself is kept)

Ai::vectorStoreFileBatches()->listFiles('vs_123', 'vsfb_123');
Ai::vectorStoreFileBatches()->cancel('vs_123', 'vsfb_123');
```

## Keeping a store in sync with your models

```php
class HelpArticleObserver
{
    public function saved(HelpArticle $article): void
    {
        SyncArticleToVectorStore::dispatch($article);
    }
}

class SyncArticleToVectorStore implements ShouldQueue
{
    use Queueable;

    public function __construct(public HelpArticle $article) {}

    public function handle(): void
    {
        $storeId = config('services.openai.help_vector_store');

        if ($this->article->openai_file_id) {
            Ai::vectorStoreFiles()->delete($storeId, $this->article->openai_file_id);
            Ai::files()->delete($this->article->openai_file_id);
        }

        $path = tempnam(sys_get_temp_dir(), 'kb') . '.md';
        file_put_contents($path, "# {$this->article->title}\n\n{$this->article->body}");

        $file = Ai::files()->upload($path, 'assistants');
        Ai::vectorStoreFiles()->create($storeId, ['file_id' => $file['id']]);

        $this->article->updateQuietly(['openai_file_id' => $file['id']]);
        @unlink($path);
    }
}
```
