<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Conversation preset for starting conversations with default settings.
 *
 * @property string $id
 * @property string|null $name
 * @property string|null $default_model
 * @property string|null $default_instructions
 * @property array|null $tools
 * @property array|null $metadata
 */
class ConversationPreset extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public $incrementing = false;

    protected $table = 'ai_conversation_presets';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'default_model',
        'default_instructions',
        'tools',
        'metadata',
    ];

    protected $casts = [
        'tools' => 'array',
        'metadata' => 'array',
    ];
}
