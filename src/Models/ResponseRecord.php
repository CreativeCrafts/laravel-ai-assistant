<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $conversation_id
 * @property string|null $status
 * @property string|null $output_summary
 * @property array|null $token_usage
 * @property array|null $timings
 * @property array|null $error
 */
class ResponseRecord extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public $incrementing = false;

    protected $table = 'ai_responses';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'conversation_id',
        'status',
        'output_summary',
        'token_usage',
        'timings',
        'error',
    ];

    protected $casts = [
        'token_usage' => 'array',
        'timings' => 'array',
        'error' => 'array',
    ];
}
