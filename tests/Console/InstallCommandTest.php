<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;

it('registers the ai:install command', function (): void {
    expect(Artisan::all())->toHaveKey('ai:install');
});
