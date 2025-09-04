<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string|null $user_id
 * @property string|null $title
 * @property string|null $status
 * @property array|null $metadata
 */
class Conversation extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public $incrementing = false;

    protected $table = 'ai_conversations';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'user_id',
        'title',
        'status',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];
}
