<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelAiAssistantServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laravel-ai-assistant')
            ->hasConfigFile();
    }
}
