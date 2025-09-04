<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Tests\DataFactories;

/**
 * Factory for creating test ConversationItem instances and data
 */
final class ConversationItemFactory
{
    /**
     * Create a conversation item data array
     */
    public static function create(array $overrides = []): array
    {
        return array_merge([
            'id' => self::generateId(),
            'conversation_id' => self::generateId(),
            'role' => self::randomRole(),
            'content' => self::sampleContent(),
            'attachments' => self::sampleAttachments(),
            'created_at' => now()->toDateTimeString(),
            'updated_at' => now()->toDateTimeString(),
        ], $overrides);
    }

    /**
     * Create multiple conversation items
     */
    public static function createMultiple(int $count, array $overrides = []): array
    {
        $items = [];
        for ($i = 0; $i < $count; $i++) {
            $items[] = self::create($overrides);
        }
        return $items;
    }

    /**
     * Create conversation item for specific conversation
     */
    public static function forConversation(string $conversationId, array $overrides = []): array
    {
        return self::create(array_merge([
            'conversation_id' => $conversationId,
        ], $overrides));
    }

    /**
     * Create user message
     */
    public static function userMessage(string $message, array $overrides = []): array
    {
        return self::create(array_merge([
            'role' => 'user',
            'content' => [
                'type' => 'text',
                'text' => $message,
            ],
            'attachments' => null,
        ], $overrides));
    }

    /**
     * Create assistant message
     */
    public static function assistantMessage(string $message, array $overrides = []): array
    {
        return self::create(array_merge([
            'role' => 'assistant',
            'content' => [
                'type' => 'text',
                'text' => $message,
            ],
            'attachments' => null,
        ], $overrides));
    }

    /**
     * Create system message
     */
    public static function systemMessage(string $message, array $overrides = []): array
    {
        return self::create(array_merge([
            'role' => 'system',
            'content' => [
                'type' => 'text',
                'text' => $message,
            ],
            'attachments' => null,
        ], $overrides));
    }

    /**
     * Create message with attachments
     */
    public static function withAttachments(array $attachments, array $overrides = []): array
    {
        return self::create(array_merge([
            'attachments' => $attachments,
        ], $overrides));
    }

    /**
     * Generate a unique ID
     */
    private static function generateId(): string
    {
        return 'item_' . uniqid('', true) . '_' . random_int(1000, 9999);
    }

    /**
     * Get random role
     */
    private static function randomRole(): string
    {
        $roles = ['user', 'assistant', 'system'];
        return $roles[array_rand($roles)];
    }

    /**
     * Generate sample content
     */
    private static function sampleContent(): array
    {
        $contentTypes = [
            [
                'type' => 'text',
                'text' => 'This is a sample conversation message for testing purposes.',
            ],
            [
                'type' => 'text',
                'text' => 'Hello! How can I assist you today?',
            ],
            [
                'type' => 'text',
                'text' => 'I need help with understanding AI assistant functionality.',
            ],
        ];

        return $contentTypes[array_rand($contentTypes)];
    }

    /**
     * Generate sample attachments
     */
    private static function sampleAttachments(): ?array
    {
        $hasAttachments = random_int(0, 1);

        if (!$hasAttachments) {
            return null;
        }

        return [
            [
                'type' => 'file',
                'file_id' => 'file_' . uniqid('', true),
                'filename' => 'document.pdf',
                'size' => random_int(1000, 500000),
            ],
            [
                'type' => 'image',
                'file_id' => 'img_' . uniqid('', true),
                'filename' => 'screenshot.png',
                'size' => random_int(50000, 2000000),
            ],
        ];
    }
}
