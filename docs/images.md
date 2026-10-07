# Images

Generate, edit and vary images, either through the unified builder (simple, with `saveImages()`) or the
low-level Images API (every parameter, including `gpt-image-1` options and partial-image streaming).

## Generate

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A cosy reading nook with a cat, studio ghibli style',
        'model' => 'dall-e-3',          // default: dall-e-2
        'size' => '1792x1024',
        'quality' => 'hd',
        'style' => 'vivid',
        'n' => 1,
    ])
    ->send();

$paths = $response->saveImages(storage_path('app/public/generated'));
// ['/var/www/storage/app/public/generated/image_0_01J....png']
```

| Option | Description |
|---|---|
| `prompt` | What to draw (required for generation and edits) |
| `model` | `dall-e-2` (default) or `dall-e-3`. For `gpt-image-1` use [`Ai::images()`](#low-level-images-api-gpt-image-1): the builder always sends `response_format`, which gpt-image models reject |
| `n` | Number of images (1–10; `dall-e-3` supports 1) |
| `size` | `256x256`, `512x512`, `1024x1024`; `1792x1024` and `1024x1792` with `dall-e-3` (the builder validates sizes against these lists, so use `Ai::images()` for `gpt-image-1` sizes such as `1536x1024`) |
| `quality` | `standard`, `hd` (dall-e-3) |
| `style` | `vivid`, `natural` (dall-e-3) |
| `response_format` | `url` or `b64_json` |

`$response->images` holds the raw image entries (`url` or `b64_json`, plus `revised_prompt` for dall-e-3).

### Store on a disk instead of local storage

```php
foreach ($response->images as $i => $image) {
    $bytes = isset($image['b64_json'])
        ? base64_decode($image['b64_json'])
        : Http::get($image['url'])->body();

    Storage::disk('s3')->put("products/{$product->id}/hero-{$i}.png", $bytes);
}
```

Image URLs returned by OpenAI expire after about an hour, so download them promptly.

## Edit (image + prompt)

```php
$response = Ai::responses()
    ->input()
    ->image([
        'image' => storage_path('app/images/living-room.png'),
        'mask' => storage_path('app/images/living-room-mask.png'),  // transparent where to paint
        'prompt' => 'Replace the sofa with a green velvet sofa',
    ])
    ->send();

$response->saveImages(storage_path('app/images/edited'));
```

## Variations (image without a prompt)

```php
Ai::responses()
    ->input()
    ->image(['image' => storage_path('app/images/logo.png'), 'n' => 4, 'size' => '512x512'])
    ->send()
    ->saveImages(storage_path('app/images/logo-variations'));
```

## Low-level Images API (gpt-image-1)

The repository passes your payload through untouched, so every current option works: `background`,
`output_format`, `output_compression`, `moderation`, `input_fidelity`, multiple input images, and more.

```php
$result = Ai::images()->generate([
    'model' => 'gpt-image-1',
    'prompt' => 'Flat vector icon of a rocket, transparent background',
    'size' => '1024x1024',
    'background' => 'transparent',
    'output_format' => 'png',
    'quality' => 'high',
]);

Storage::put('icons/rocket.png', base64_decode($result['data'][0]['b64_json']));
```

### Edit with several reference images

```php
$result = Ai::images()->edit([
    'model' => 'gpt-image-1',
    'image' => [
        $request->file('product'),                    // UploadedFile
        storage_path('app/brand/logo.png'),           // path
    ],
    'prompt' => 'Product photo on a marble table with our logo on the box',
    'input_fidelity' => 'high',
]);
```

### Stream partial images

Show a preview while the final image renders:

```php
foreach (Ai::images()->streamGeneration([
    'model' => 'gpt-image-1',
    'prompt' => 'A detailed map of a fantasy city',
    'partial_images' => 2,
]) as $event) {
    if ($event['type'] === 'image_generation.partial_image') {
        broadcast(new ImagePreviewUpdated($jobId, $event['b64_json'], $event['partial_image_index']));
    }

    if ($event['type'] === 'image_generation.completed') {
        Storage::put("maps/{$jobId}.png", base64_decode($event['b64_json']));
    }
}
```

`Ai::images()->streamEdit($payload)` works the same way for edits.

### Methods

| Method | Endpoint |
|---|---|
| `generate(array $payload): array` | `POST /v1/images/generations` |
| `streamGeneration(array $payload): iterable` | same, with `stream: true` |
| `edit(array $payload): array` | `POST /v1/images/edits` (multipart: `image`, `mask`) |
| `streamEdit(array $payload): iterable` | same, with `stream: true` |
| `createVariation(array $payload): array` | `POST /v1/images/variations` (multipart: `image`) |

## Validation

The builder checks images before uploading: supported formats are `png`, `jpg`, `jpeg`, `webp`, and the
maximum size is `OPENAI_IMAGE_FILE_SIZE_LIMIT_MB` (default 4 MB). Invalid input throws
`InvalidArgumentException` from `send()`.
