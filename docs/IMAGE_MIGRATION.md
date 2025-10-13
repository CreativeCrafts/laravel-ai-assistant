# Image API Migration Guide

This guide helps you migrate from direct OpenAI image API calls or separate image methods to the unified Response API with Single Source of Truth (SSOT) architecture.

## Overview

The unified Response API provides a single interface for all image operations:
- **Image Generation**: Create images from text prompts
- **Image Edit**: Modify existing images with prompts
- **Image Variation**: Create variations of existing images

### Benefits of the Unified API

- ✅ **Single Source of Truth**: One interface for all AI operations
- ✅ **Automatic Routing**: API automatically routes to the correct OpenAI endpoint
- ✅ **Consistent Interface**: Same builder pattern across all features
- ✅ **Type Safety**: Full IDE autocomplete and type hints
- ✅ **Better Error Handling**: Unified exception hierarchy
- ✅ **Laravel Conventions**: Follows Laravel best practices

---

## Image Generation Migration

### Before: Direct OpenAI API Calls

If you were making direct HTTP calls to OpenAI's image generation endpoint:

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
    'Content-Type' => 'application/json',
])
->post('https://api.openai.com/v1/images/generations', [
    'model' => 'dall-e-3',
    'prompt' => 'A serene mountain landscape at sunset',
    'n' => 1,
    'size' => '1024x1024',
    'quality' => 'hd',
    'style' => 'vivid',
]);

$imageUrl = $response->json()['data'][0]['url'];
```

### After: Unified Response API

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('dall-e-3')
    ->input()
    ->image([
        'prompt' => 'A serene mountain landscape at sunset',
        'size' => '1024x1024',
        'quality' => 'hd',
        'style' => 'vivid',
    ])
    ->send();

$imageUrl = $response->imageUrls[0];
```

### Migration Benefits

- ✅ No need to manage HTTP headers and authentication
- ✅ Automatic endpoint routing based on parameters
- ✅ Consistent error handling
- ✅ Type-safe response objects
- ✅ Built-in retry logic and rate limiting

### Advanced Image Generation

**Multiple Images with DALL-E 2:**

```php
// Before: Manual API construction
$response = Http::withHeaders([...])
    ->post('https://api.openai.com/v1/images/generations', [
        'model' => 'dall-e-2',
        'prompt' => 'A cute robot mascot for a tech company',
        'n' => 3,
        'size' => '512x512',
    ]);

$imageUrls = array_map(fn($img) => $img['url'], $response->json()['data']);

// After: Clean fluent interface
$response = Ai::responses()
    ->model('dall-e-2')
    ->input()
    ->image([
        'prompt' => 'A cute robot mascot for a tech company',
        'n' => 3,
        'size' => '512x512',
    ])
    ->send();

$imageUrls = $response->imageUrls;
```

**Different Sizes and Styles:**

```php
// Before: Multiple manual API calls
$sizes = ['1024x1024', '1792x1024', '1024x1792'];
foreach ($sizes as $size) {
    $response = Http::withHeaders([...])
        ->post('https://api.openai.com/v1/images/generations', [
            'model' => 'dall-e-3',
            'prompt' => 'A minimalist geometric pattern',
            'size' => $size,
            'style' => 'natural',
        ]);
    
    $url = $response->json()['data'][0]['url'];
    $imageData = file_get_contents($url);
    file_put_contents("image-{$size}.png", $imageData);
}

// After: Clean loop with unified API
$sizes = ['1024x1024', '1792x1024', '1024x1792'];
foreach ($sizes as $size) {
    $response = Ai::responses()
        ->model('dall-e-3')
        ->input()
        ->image([
            'prompt' => 'A minimalist geometric pattern',
            'size' => $size,
            'style' => 'natural',
        ])
        ->send();
    
    $imageData = file_get_contents($response->imageUrls[0]);
    file_put_contents("image-{$size}.png", $imageData);
}
```

**Base64 Response Format:**

```php
// Before: Manual base64 handling
$response = Http::withHeaders([...])
    ->post('https://api.openai.com/v1/images/generations', [
        'prompt' => 'A simple star icon',
        'size' => '256x256',
        'response_format' => 'b64_json',
    ]);

$base64Data = $response->json()['data'][0]['b64_json'];
$imageData = base64_decode($base64Data);
file_put_contents('icon.png', $imageData);

// After: Structured base64 handling
$response = Ai::responses()
    ->input()
    ->image([
        'prompt' => 'A simple star icon',
        'size' => '256x256',
        'response_format' => 'b64_json',
    ])
    ->send();

$imageData = base64_decode($response->imageData[0]);
file_put_contents('icon.png', $imageData);
```

---

## Image Edit Migration

Image editing allows you to modify existing images using a prompt and optional mask.

### Before: Direct API Calls

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
])
->attach('image', file_get_contents($imagePath), basename($imagePath))
->attach('mask', file_get_contents($maskPath), basename($maskPath))
->post('https://api.openai.com/v1/images/edits', [
    'prompt' => 'Add a beautiful sunset sky with vibrant colors',
    'n' => 1,
    'size' => '1024x1024',
]);

$editedImageUrl = $response->json()['data'][0]['url'];
```

### After: Unified Response API

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'image' => $imagePath,
        'mask' => $maskPath,
        'prompt' => 'Add a beautiful sunset sky with vibrant colors',
        'size' => '1024x1024',
    ])
    ->send();

$editedImageUrl = $response->imageUrls[0];
```

### Image Edit Without Mask

```php
// Before: Manual file attachment
$response = Http::withHeaders([...])
    ->attach('image', file_get_contents($imagePath), basename($imagePath))
    ->post('https://api.openai.com/v1/images/edits', [
        'prompt' => 'Make the colors more vibrant',
        'n' => 2,
        'size' => '512x512',
    ]);

// After: Simple image edit
$response = Ai::responses()
    ->input()
    ->image([
        'image' => $imagePath,
        'prompt' => 'Make the colors more vibrant',
        'n' => 2,
        'size' => '512x512',
    ])
    ->send();

foreach ($response->imageUrls as $index => $url) {
    $imageData = file_get_contents($url);
    file_put_contents("edited-{$index}.png", $imageData);
}
```

---

## Image Variation Migration

Image variations create different versions of an existing image without a text prompt.

### Before: Direct API Calls

```php
use Illuminate\Support\Facades\Http;

$response = Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
])
->attach('image', file_get_contents($imagePath), basename($imagePath))
->post('https://api.openai.com/v1/images/variations', [
    'n' => 3,
    'size' => '512x512',
    'response_format' => 'url',
]);

$variationUrls = array_map(fn($img) => $img['url'], $response->json()['data']);
```

### After: Unified Response API

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->input()
    ->image([
        'image' => $imagePath,
        'n' => 3,
        'size' => '512x512',
    ])
    ->send();

$variationUrls = $response->imageUrls;
```

### Multiple Variations Loop

```php
// Before: Multiple manual API calls
foreach (range(1, 5) as $batch) {
    $response = Http::withHeaders([...])
        ->attach('image', file_get_contents($imagePath), basename($imagePath))
        ->post('https://api.openai.com/v1/images/variations', [
            'n' => 2,
            'size' => '1024x1024',
        ]);
    
    foreach ($response->json()['data'] as $index => $imgData) {
        $imageData = file_get_contents($imgData['url']);
        file_put_contents("variation-batch{$batch}-{$index}.png", $imageData);
    }
}

// After: Clean batch processing
foreach (range(1, 5) as $batch) {
    $response = Ai::responses()
        ->input()
        ->image([
            'image' => $imagePath,
            'n' => 2,
            'size' => '1024x1024',
        ])
        ->send();
    
    foreach ($response->imageUrls as $index => $url) {
        $imageData = file_get_contents($url);
        file_put_contents("variation-batch{$batch}-{$index}.png", $imageData);
    }
}
```

---

## Search and Replace Patterns

Use these patterns to quickly migrate your code:

### Image Generation

```php
// Find:
Http::withHeaders([
    'Authorization' => 'Bearer ' . config('services.openai.api_key'),
    'Content-Type' => 'application/json',
])
->post('https://api.openai.com/v1/images/generations', [
    'model' => 'dall-e-3',
    'prompt' => $prompt,
    // ...options
]);

// Replace with:
Ai::responses()
    ->model('dall-e-3')
    ->input()
    ->image([
        'prompt' => $prompt,
        // ...options
    ])
    ->send();
```

### Image Edit

```php
// Find:
Http::withHeaders([...])
->attach('image', file_get_contents($imagePath), basename($imagePath))
->post('https://api.openai.com/v1/images/edits', [
    'prompt' => $prompt,
    // ...options
]);

// Replace with:
Ai::responses()
    ->input()
    ->image([
        'image' => $imagePath,
        'prompt' => $prompt,
        // ...options
    ])
    ->send();
```

### Image Variation

```php
// Find:
Http::withHeaders([...])
->attach('image', file_get_contents($imagePath), basename($imagePath))
->post('https://api.openai.com/v1/images/variations', [
    'n' => $count,
    // ...options
]);

// Replace with:
Ai::responses()
    ->input()
    ->image([
        'image' => $imagePath,
        'n' => $count,
        // ...options
    ])
    ->send();
```

### Response Handling

```php
// Find:
$imageUrl = $response->json()['data'][0]['url'];
$imageUrls = array_map(fn($img) => $img['url'], $response->json()['data']);

// Replace with:
$imageUrl = $response->imageUrls[0];
$imageUrls = $response->imageUrls;

// Find (base64):
$base64Data = $response->json()['data'][0]['b64_json'];

// Replace with:
$base64Data = $response->imageData[0];
```

---

## Parameter Mapping

### Image Generation Parameters

| Direct API | Unified API | Notes |
|------------|-------------|-------|
| `model` | `model` (method) or `'model'` in array | dall-e-2 or dall-e-3 |
| `prompt` | `'prompt'` | Required |
| `n` | `'n'` | Number of images (1-10 for DALL-E 2, 1 for DALL-E 3) |
| `size` | `'size'` | Image dimensions |
| `quality` | `'quality'` | standard or hd (DALL-E 3 only) |
| `style` | `'style'` | vivid or natural (DALL-E 3 only) |
| `response_format` | `'response_format'` | url or b64_json |
| `user` | `'user'` | Optional user identifier |

### Image Edit Parameters

| Direct API | Unified API | Notes |
|------------|-------------|-------|
| `image` (multipart) | `'image'` | Required - path to PNG image |
| `mask` (multipart) | `'mask'` | Optional - path to PNG mask |
| `prompt` | `'prompt'` | Required |
| `n` | `'n'` | Number of images (1-10) |
| `size` | `'size'` | 256x256, 512x512, or 1024x1024 |
| `response_format` | `'response_format'` | url or b64_json |
| `user` | `'user'` | Optional user identifier |

### Image Variation Parameters

| Direct API | Unified API | Notes |
|------------|-------------|-------|
| `image` (multipart) | `'image'` | Required - path to PNG image |
| `n` | `'n'` | Number of images (1-10) |
| `size` | `'size'` | 256x256, 512x512, or 1024x1024 |
| `response_format` | `'response_format'` | url or b64_json |
| `user` | `'user'` | Optional user identifier |

---

## Automatic Endpoint Detection

The unified API automatically detects which image endpoint to use:

| Input Parameters | Routes To | Reason |
|------------------|-----------|--------|
| `'prompt'` only | Image Generation | No existing image provided |
| `'image'` + `'prompt'` | Image Edit | Has both image and prompt |
| `'image'` only (no prompt) | Image Variation | Has image but no prompt |

```php
// Automatically routes to Image Generation
Ai::responses()->input()->image(['prompt' => 'A sunset'])->send();

// Automatically routes to Image Edit
Ai::responses()->input()->image(['image' => 'photo.png', 'prompt' => 'Add clouds'])->send();

// Automatically routes to Image Variation
Ai::responses()->input()->image(['image' => 'photo.png', 'n' => 3])->send();
```

---

## Common Migration Issues

### Issue 1: Response Structure Differences

**Problem:**
```php
// Old code expecting nested array structure
$data = $response->json()['data'];
foreach ($data as $image) {
    $url = $image['url'];
    $revisedPrompt = $image['revised_prompt'] ?? null;
}
```

**Solution:**
```php
// Use response properties
foreach ($response->imageUrls as $url) {
    // Process URL
}

// Revised prompts (DALL-E 3) are in metadata
$revisedPrompt = $response->metadata['revised_prompt'] ?? null;
```

### Issue 2: File Attachment Handling

**Problem:**
```php
// Old code manually reading and attaching files
->attach('image', file_get_contents($imagePath), basename($imagePath))
->attach('mask', file_get_contents($maskPath), basename($maskPath))
```

**Solution:**
```php
// Just pass file paths
->image([
    'image' => $imagePath,
    'mask' => $maskPath,
    // ...
])
```

The unified API handles file reading and multipart requests automatically.

### Issue 3: Base64 vs URL Response

**Problem:**
```php
// Old code with different handling for formats
if ($responseFormat === 'b64_json') {
    $data = $response->json()['data'][0]['b64_json'];
    $imageData = base64_decode($data);
} else {
    $url = $response->json()['data'][0]['url'];
    $imageData = file_get_contents($url);
}
```

**Solution:**
```php
// Unified response properties
if ($response->imageData) {
    $imageData = base64_decode($response->imageData[0]);
} else {
    $imageData = file_get_contents($response->imageUrls[0]);
}
```

### Issue 4: Error Handling

**Problem:**
```php
// Old code checking HTTP status codes
if ($response->failed()) {
    $error = $response->json()['error']['message'];
    throw new Exception($error);
}
```

**Solution:**
```php
// Use try-catch with specific exceptions
use CreativeCrafts\LaravelAiAssistant\Exceptions\ImageGenerationException;

try {
    $response = Ai::responses()
        ->input()
        ->image([...])
        ->send();
} catch (ImageGenerationException $e) {
    // Handle image generation errors
    logger()->error('Image generation failed', [
        'error' => $e->getMessage(),
        'prompt' => $prompt,
    ]);
}
```

### Issue 5: Image Format Requirements

**Problem:**
```php
// Trying to use JPEG or other formats
->attach('image', file_get_contents('photo.jpg'), 'photo.jpg')
```

**Solution:**
```php
// Convert to PNG first or ensure PNG format
// OpenAI requires PNG format for image uploads
if (pathinfo($imagePath, PATHINFO_EXTENSION) !== 'png') {
    // Convert to PNG or show error
    throw new InvalidArgumentException('Image must be in PNG format');
}

->image(['image' => $imagePath])
```

---

## Testing Your Migration

After migrating, verify your image operations:

### Test Image Generation

```php
use CreativeCrafts\LaravelAiAssistant\Facades\Ai;

$response = Ai::responses()
    ->model('dall-e-3')
    ->input()
    ->image([
        'prompt' => 'A test image of a modern Laravel application',
        'size' => '1024x1024',
    ])
    ->send();

dump([
    'type' => $response->type, // Should be 'image_generation'
    'has_urls' => !empty($response->imageUrls),
    'url_count' => count($response->imageUrls),
    'first_url' => $response->imageUrls[0] ?? null,
]);
```

### Test Image Edit

```php
$imagePath = storage_path('test-image.png');

if (file_exists($imagePath)) {
    $response = Ai::responses()
        ->input()
        ->image([
            'image' => $imagePath,
            'prompt' => 'Add a bright blue sky',
            'size' => '1024x1024',
        ])
        ->send();

    dump([
        'type' => $response->type, // Should be 'image_edit'
        'has_urls' => !empty($response->imageUrls),
        'edited_url' => $response->imageUrls[0] ?? null,
    ]);
}
```

### Test Image Variation

```php
$imagePath = storage_path('test-image.png');

if (file_exists($imagePath)) {
    $response = Ai::responses()
        ->input()
        ->image([
            'image' => $imagePath,
            'n' => 2,
            'size' => '512x512',
        ])
        ->send();

    dump([
        'type' => $response->type, // Should be 'image_variation'
        'variation_count' => count($response->imageUrls),
        'urls' => $response->imageUrls,
    ]);
}
```

---

## Migration Checklist

- [ ] Replace direct HTTP calls with `Ai::responses()`
- [ ] Remove manual HTTP header management
- [ ] Remove manual file attachment code (use file paths directly)
- [ ] Update response handling to use `$response->imageUrls` and `$response->imageData`
- [ ] Replace `$response->json()['data']` with response properties
- [ ] Update error handling to use try-catch with specific exceptions
- [ ] Ensure image files are in PNG format for edits and variations
- [ ] Verify automatic endpoint detection works correctly
- [ ] Test all image operations with real files
- [ ] Update tests to use the unified API
- [ ] Review and update documentation
- [ ] Check that image size limits are respected (4MB for uploads)

---

## Size and Format Reference

### DALL-E 3 Sizes
- `1024x1024` (square)
- `1792x1024` (landscape)
- `1024x1792` (portrait)

### DALL-E 2 Sizes
- `256x256` (small)
- `512x512` (medium)
- `1024x1024` (large)

### Image Edit/Variation Sizes
- `256x256`
- `512x512`
- `1024x1024`

### Upload Requirements
- Format: PNG only
- Max size: 4MB
- Transparency: Supported
- Square images recommended for best results

---

## Additional Resources

- [API Documentation](./API.md)
- [Image Generation Example](../examples/07-image-generation.php)
- [Unified API Example](../examples/08-unified-api.php)
- [Architecture Overview](./ARCHITECTURE.md)

---

## Getting Help

If you encounter issues during migration:

1. Check the [examples directory](../examples/) for working code
2. Review the [API documentation](./API.md)
3. Verify image format requirements (PNG, max 4MB)
4. Search for similar issues on [GitHub](https://github.com/creativecrafts/laravel-ai-assistant/issues)
5. Open a new issue with your specific migration problem

---

**Migration completed?** Excellent! The unified API will make your image operations more reliable, consistent, and easier to maintain. You now have a single interface for all AI operations including text, audio, and images.
