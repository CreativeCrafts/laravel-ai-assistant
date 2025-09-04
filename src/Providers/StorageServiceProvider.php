<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Providers;

use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\AssistantsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ConversationItemsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ConversationsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ResponsesStoreContract;
use CreativeCrafts\LaravelAiAssistant\Contracts\Storage\ToolInvocationsStoreContract;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\EloquentAssistantsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\EloquentConversationItemsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\EloquentConversationsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\EloquentResponsesStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\EloquentToolInvocationsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\InMemoryAssistantsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\InMemoryConversationItemsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\InMemoryConversationsStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\InMemoryResponsesStore;
use CreativeCrafts\LaravelAiAssistant\Services\Storage\InMemoryToolInvocationsStore;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register storage singletons based on configured persistence driver
        $driver = config('ai-assistant.persistence.driver', 'memory');
        if (!is_string($driver)) {
            $driver = 'memory';
        }

        if ($driver === 'eloquent') {
            $this->registerEloquentStores();
        } else {
            $this->registerInMemoryStores();
        }
    }

    private function registerEloquentStores(): void
    {
        $this->app->singleton(AssistantsStoreContract::class, function ($app) {
            return new EloquentAssistantsStore();
        });

        $this->app->singleton(ConversationsStoreContract::class, function ($app) {
            return new EloquentConversationsStore();
        });

        $this->app->singleton(ConversationItemsStoreContract::class, function ($app) {
            return new EloquentConversationItemsStore();
        });

        $this->app->singleton(ResponsesStoreContract::class, function ($app) {
            return new EloquentResponsesStore();
        });

        $this->app->singleton(ToolInvocationsStoreContract::class, function ($app) {
            return new EloquentToolInvocationsStore();
        });
    }

    private function registerInMemoryStores(): void
    {
        $this->app->singleton(AssistantsStoreContract::class, function ($app) {
            return new InMemoryAssistantsStore();
        });

        $this->app->singleton(ConversationsStoreContract::class, function ($app) {
            return new InMemoryConversationsStore();
        });

        $this->app->singleton(ConversationItemsStoreContract::class, function ($app) {
            return new InMemoryConversationItemsStore();
        });

        $this->app->singleton(ResponsesStoreContract::class, function ($app) {
            return new InMemoryResponsesStore();
        });

        $this->app->singleton(ToolInvocationsStoreContract::class, function ($app) {
            return new InMemoryToolInvocationsStore();
        });
    }
}
