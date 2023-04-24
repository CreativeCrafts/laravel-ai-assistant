<?php

namespace CreativeCrafts\LaravelAiAssistant\Commands;

use Illuminate\Console\Command;

class LaravelAiAssistantCommand extends Command
{
    public $signature = 'laravel-ai-assistant';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
