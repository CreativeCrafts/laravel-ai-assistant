<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Repositories\Http\AssistantsHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\BatchesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\FilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoreFileBatchesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Repositories\Http\VectorStoreFilesHttpRepository;
use CreativeCrafts\LaravelAiAssistant\Tests\Fakes\RecordingHttpClient;

// Guzzle resolves dot segments, so an id like '../../files/file-1' would otherwise reach another endpoint
it('keeps ids inside their own path segment', function (string $repository, string $method, array $arguments, string $httpMethod, string $path) {
    $http = new RecordingHttpClient();

    (new $repository($http->transport()))->{$method}(...$arguments);

    expect($http->lastRequest()->getMethod())->toBe($httpMethod)
        ->and($http->lastUri())->toBe('https://api.openai.com' . $path);
})->with([
    'assistants retrieve' => [AssistantsHttpRepository::class, 'retrieve', ['../files'], 'GET', '/v1/assistants/..%2Ffiles'],
    'assistants update' => [AssistantsHttpRepository::class, 'update', ['../files', ['name' => 'x']], 'POST', '/v1/assistants/..%2Ffiles'],
    'assistants delete' => [AssistantsHttpRepository::class, 'delete', ['../files/file-1'], 'DELETE', '/v1/assistants/..%2Ffiles%2Ffile-1'],
    'batches retrieve' => [BatchesHttpRepository::class, 'retrieve', ['../files'], 'GET', '/v1/batches/..%2Ffiles'],
    'batches cancel' => [BatchesHttpRepository::class, 'cancel', ['../../files/file-1'], 'POST', '/v1/batches/..%2F..%2Ffiles%2Ffile-1/cancel'],
    'files retrieve' => [FilesHttpRepository::class, 'retrieve', ['../batches?limit=1'], 'GET', '/v1/files/..%2Fbatches%3Flimit=1'],
    'files delete' => [FilesHttpRepository::class, 'delete', ['../vector_stores/vs_1'], 'DELETE', '/v1/files/..%2Fvector_stores%2Fvs_1'],
    'files content' => [FilesHttpRepository::class, 'content', ['file-1#x'], 'GET', '/v1/files/file-1%23x/content'],
    'vector store file batches create' => [VectorStoreFileBatchesHttpRepository::class, 'create', ['../../files', ['file_ids' => ['file-1']]], 'POST', '/v1/vector_stores/..%2F..%2Ffiles/file_batches'],
    'vector store file batches retrieve' => [VectorStoreFileBatchesHttpRepository::class, 'retrieve', ['vs_1', '../../../files'], 'GET', '/v1/vector_stores/vs_1/file_batches/..%2F..%2F..%2Ffiles'],
    'vector store file batches cancel' => [VectorStoreFileBatchesHttpRepository::class, 'cancel', ['vs_1', '../vfb_1'], 'POST', '/v1/vector_stores/vs_1/file_batches/..%2Fvfb_1/cancel'],
    'vector store file batches list files' => [VectorStoreFileBatchesHttpRepository::class, 'listFiles', ['../vs_2', 'vfb_1'], 'GET', '/v1/vector_stores/..%2Fvs_2/file_batches/vfb_1/files'],
    'vector store files create' => [VectorStoreFilesHttpRepository::class, 'create', ['../../files', ['file_id' => 'file-1']], 'POST', '/v1/vector_stores/..%2F..%2Ffiles/files'],
    'vector store files retrieve' => [VectorStoreFilesHttpRepository::class, 'retrieve', ['vs_1', '../../../files/file-1'], 'GET', '/v1/vector_stores/vs_1/files/..%2F..%2F..%2Ffiles%2Ffile-1'],
    'vector store files update' => [VectorStoreFilesHttpRepository::class, 'update', ['vs_1', '../file-2', ['attributes' => ['a' => 'b']]], 'POST', '/v1/vector_stores/vs_1/files/..%2Ffile-2'],
    'vector store files delete' => [VectorStoreFilesHttpRepository::class, 'delete', ['vs_1', '../../../files/file-1'], 'DELETE', '/v1/vector_stores/vs_1/files/..%2F..%2F..%2Ffiles%2Ffile-1'],
    'vector store files list' => [VectorStoreFilesHttpRepository::class, 'list', ['../../files'], 'GET', '/v1/vector_stores/..%2F..%2Ffiles/files'],
    'vector store files content' => [VectorStoreFilesHttpRepository::class, 'content', ['vs_1', 'file-1?x'], 'GET', '/v1/vector_stores/vs_1/files/file-1%3Fx/content'],
]);

it('rejects empty and dot ids without sending a request', function (string $repository, string $method, array $arguments) {
    $http = new RecordingHttpClient();

    expect(fn () => (new $repository($http->transport()))->{$method}(...$arguments))
        ->toThrow(InvalidArgumentException::class, 'Invalid path parameter')
        ->and($http->history)->toBe([]);
})->with([
    'empty file id' => [FilesHttpRepository::class, 'delete', ['']],
    'dot batch id' => [BatchesHttpRepository::class, 'cancel', ['.']],
    'dot-dot vector store file id' => [VectorStoreFilesHttpRepository::class, 'delete', ['vs_1', '..']],
    'empty assistant id' => [AssistantsHttpRepository::class, 'retrieve', ['']],
    'empty vector store id' => [VectorStoreFileBatchesHttpRepository::class, 'retrieve', ['', 'vfb_1']],
]);
