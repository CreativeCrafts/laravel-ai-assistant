<?php

namespace CreativeCrafts\LaravelAiAssistant;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use CreativeCrafts\LaravelAiAssistant\Commands\LaravelAiAssistantCommand;

class LaravelAiAssistantServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ai-assistant')
            ->hasConfigFile()
            ->hasCommand(LaravelAiAssistantCommand::class);
    }
}
