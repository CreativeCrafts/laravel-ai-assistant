<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string|null $role
 * @property array|null $content
 * @property array|null $attachments
 */
class ConversationItem extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public $incrementing = false;

    protected $table = 'ai_conversation_items';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'conversation_id',
        'role',
        'content',
        'attachments',
    ];

    protected $casts = [
        'content' => 'array',
        'attachments' => 'array',
    ];
}
