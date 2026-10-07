# Files & uploads

Files are used by batches, fine-tuning, vector stores, vision and code interpreter. Use `Ai::files()` for
files up to 512 MB and `Ai::uploads()` for large files (up to 8 GB) uploaded in parts.

## Upload a file

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$file = Ai::files()->upload(storage_path('app/docs/handbook.pdf'), 'assistants');

$file['id'];        // 'file_abc123'
$file['bytes'];
$file['filename'];
```

The purpose tells OpenAI how the file will be used:

| Purpose | Use |
|---|---|
| `assistants` | Vector stores, file search, code interpreter |
| `batch` | Batch API input (`.jsonl`) |
| `fine-tune` | Fine-tuning training/validation data (`.jsonl`) |
| `vision` | Images used as model input |
| `user_data` | General-purpose files used as model input |
| `evals` | Eval datasets |

### Expiring files

```php
Ai::files()->upload($path, 'batch', [
    'expires_after' => ['anchor' => 'created_at', 'seconds' => 60 * 60 * 24 * 7],
]);
```

### Uploading a user's file from a request

```php
public function store(Request $request)
{
    $request->validate(['document' => ['required', 'file', 'mimes:pdf,docx,txt', 'max:20480']]);

    $file = Ai::files()->upload($request->file('document')->getRealPath(), 'user_data');

    return back()->with('file_id', $file['id']);
}
```

> `upload()` sends the file under its basename. For a temporary upload the basename is random (`phpA1B2.tmp`);
> if the extension matters, move the file first: `$request->file('document')->storeAs('tmp', $original)`.

`ChatSession::attachUploadedFile()` handles uploads for you and attaches the file to the next turn (see
[Chat sessions](chat-sessions-and-tools.md#file-search-over-your-documents)).

## List, inspect, download and delete

```php
Ai::files()->list(['purpose' => 'batch', 'limit' => 100, 'order' => 'desc']);
Ai::files()->retrieve('file_abc123');

$download = Ai::files()->content('file_abc123');
Storage::put('exports/output.jsonl', $download['content']);

Ai::files()->delete('file_abc123'); // true on success
```

## Large files with the Uploads API

`uploadFile()` creates the upload, streams the file in 64 MB parts (without loading it into memory),
completes it and returns the completed upload, which contains the resulting `file`:

```php
$upload = Ai::uploads()->uploadFile(
    storage_path('app/training/conversations.jsonl'),
    purpose: 'fine-tune',
);

$fileId = $upload['file']['id'];
```

If any part fails, the upload is cancelled automatically. Optional arguments: `mimeType` (detected from the
file by default) and `partSize` (bytes, up to 64 MB).

### Manual multi-part upload

```php
$upload = Ai::uploads()->create([
    'filename' => 'dataset.jsonl',
    'purpose' => 'batch',
    'bytes' => filesize($path),
    'mime_type' => 'application/jsonl',
]);

$part = Ai::uploads()->addPart($upload['id'], ['data' => $chunkPathOrStream]);

Ai::uploads()->complete($upload['id'], ['part_ids' => [$part['id']]]);
// or
Ai::uploads()->cancel($upload['id']);
```

## Container files

Files inside a code-interpreter/shell container are managed separately, see
[Agents, skills & containers](agents.md#containers).

## Methods

| Method | Endpoint |
|---|---|
| `Ai::files()->upload(string $path, string $purpose = 'assistants', array $params = [])` | `POST /v1/files` |
| `Ai::files()->list(array $params = [])` | `GET /v1/files` |
| `Ai::files()->retrieve(string $fileId)` | `GET /v1/files/{id}` |
| `Ai::files()->content(string $fileId)` | `GET /v1/files/{id}/content` |
| `Ai::files()->delete(string $fileId): bool` | `DELETE /v1/files/{id}` |
| `Ai::uploads()->create(array $payload)` | `POST /v1/uploads` |
| `Ai::uploads()->addPart(string $uploadId, array $payload)` | `POST /v1/uploads/{id}/parts` |
| `Ai::uploads()->complete(string $uploadId, array $payload)` | `POST /v1/uploads/{id}/complete` |
| `Ai::uploads()->cancel(string $uploadId)` | `POST /v1/uploads/{id}/cancel` |
| `Ai::uploads()->uploadFile(string $path, string $purpose, ?string $mimeType = null, int $partSize = 64 MB)` | All of the above |
