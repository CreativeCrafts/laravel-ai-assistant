<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * @property string $id
 * @property string $response_id
 * @property string|null $name
 * @property array|null $arguments
 * @property string|null $state
 * @property array|null $result_summary
 */
class ToolInvocation extends Model
{
    /** @use HasFactory<\Illuminate\Database\Eloquent\Factories\Factory<static>> */
    use HasFactory;

    public $incrementing = false;

    protected $table = 'ai_tool_invocations';

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'response_id',
        'name',
        'arguments',
        'state',
        'result_summary',
    ];

    protected $casts = [
        'arguments' => 'array',
        'result_summary' => 'array',
    ];
}
