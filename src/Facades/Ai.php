<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Facades;

use CreativeCrafts\LaravelAiAssistant\Chat\ChatSession;
use CreativeCrafts\LaravelAiAssistant\Contracts\AssistantsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\BatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\FilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\ModerationsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\RealtimeSessionsRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFileBatchesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoreFilesRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\VectorStoresRepositoryContract;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\ChatResponseDto;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionRequest;
use CreativeCrafts\LaravelAiAssistant\DataTransferObjects\CompletionResult;
use CreativeCrafts\LaravelAiAssistant\Enums\Mode;
use CreativeCrafts\LaravelAiAssistant\Enums\Transport;
use CreativeCrafts\LaravelAiAssistant\Services\AiManager;
use CreativeCrafts\LaravelAiAssistant\Support\ConversationsBuilder;
use CreativeCrafts\LaravelAiAssistant\Support\ResponsesBuilder;
use Generator;
use Illuminate\Support\Facades\Facade;

/**
 * @method static ChatSession chat(?string $prompt = '')
 * @method static ChatResponseDto quick(string $prompt)
 * @method static Generator stream(string $prompt, ?callable $onEvent = null, ?callable $shouldStop = null)
 * @method static ResponsesBuilder responses()
 * @method static ConversationsBuilder conversations()
 * @method static ModerationsRepositoryContract moderations()
 * @method static BatchesRepositoryContract batches()
 * @method static RealtimeSessionsRepositoryContract realtimeSessions()
 * @method static VectorStoresRepositoryContract vectorStores()
 * @method static VectorStoreFilesRepositoryContract vectorStoreFiles()
 * @method static VectorStoreFileBatchesRepositoryContract vectorStoreFileBatches()
 * @method static AssistantsRepositoryContract assistants()
 * @method static FilesRepositoryContract files()
 * @method static CompletionResult complete(Mode $mode, Transport $transport, CompletionRequest $request)
 * @see AiManager
 */
class Ai extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return AiManager::class;
    }
}
