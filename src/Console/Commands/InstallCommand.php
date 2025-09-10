<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Console\Commands;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'ai:install {--driver= : persistence driver [memory|eloquent]}';

    protected $description = 'Publish config & migrations and guide initial AI Assistant setup';

    public function handle(): int
    {
        $this->info('Installing Laravel AI Assistant...');

        // Publish config
        $this->callSilent('vendor:publish', [
            '--provider' => 'CreativeCrafts\\LaravelAiAssistant\\LaravelAiAssistantServiceProvider',
            '--tag' => 'ai-assistant-config',
            '--force' => true,
        ]);

        // Publish migrations
        $this->callSilent('vendor:publish', [
            '--provider' => 'CreativeCrafts\\LaravelAiAssistant\\LaravelAiAssistantServiceProvider',
            '--tag' => 'ai-assistant-migrations',
        ]);

        // Choose a driver (force string)
        $driverOpt = $this->option('driver'); // array|string|null
        $driver = self::stringFromOption($driverOpt);
        if ($driver === '') {
            /** @var string $chosen */
            $chosen = $this->choice('Choose persistence driver', ['memory', 'eloquent'], 0);
            $driver = $chosen;
        }

        $envPath = base_path('.env');

        if (is_writable($envPath)) {
            $env = file_get_contents($envPath);
            $env = is_string($env) ? $env : '';
            // preg_replace() may return null; default back to prior content
            $env = preg_replace('/^AI_ASSISTANT_PERSISTENCE_DRIVER=.*$/m', '', $env) ?? $env;
            $env .= PHP_EOL . "AI_ASSISTANT_PERSISTENCE_DRIVER={$driver}" . PHP_EOL;
            file_put_contents($envPath, $env);
            $this->line("Updated .env: AI_ASSISTANT_PERSISTENCE_DRIVER={$driver}");
        } else {
            $this->warn('Could not update .env automatically. Set AI_ASSISTANT_PERSISTENCE_DRIVER=' . $driver);
        }

        /** @var string $preset */
        $preset = $this->choice('Preset', ['simple', 'advanced', 'production'], 0);
        if (is_writable($envPath)) {
            $env = file_get_contents($envPath);
            $env = is_string($env) ? $env : '';
            $env = preg_replace('/^AI_ASSISTANT_PRESET=.*$/m', '', $env) ?? $env;
            $env .= PHP_EOL . "AI_ASSISTANT_PRESET={$preset}" . PHP_EOL;
            file_put_contents($envPath, $env);
            $this->line("Updated .env: AI_ASSISTANT_PRESET={$preset}");
        }

        $this->line('Run migrations if using eloquent: php artisan migrate');
        $this->info('Done! Try: Route::aiAssistant(); and set AI_ASSISTANT_WEBHOOK_SIGNING_SECRET.');

        return self::SUCCESS;
    }

    /**
     * Normalise an option value (array|string|null) to a string for PHPStan.
     *
     * @param mixed $value
     * @return string
     */
    private static function stringFromOption(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        if (is_array($value)) {
            $first = reset($value);
            return is_string($first) ? $first : '';
        }
        return '';
    }
}
