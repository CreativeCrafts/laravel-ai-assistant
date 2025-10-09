<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Enums;

enum Modality: string
{
    case Text = 'text';
    case Chat = 'chat';
    case Edit = 'edit';
    case AudioToText = 'audio_to_text';
    case Image = 'image';
}
