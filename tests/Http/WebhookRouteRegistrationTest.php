<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\LaravelAiAssistantServiceProvider;
use Illuminate\Support\Facades\Route;

it('registers the webhook route with configured name and path', function () {
    // Configuration is set in the shared TestCase environment
    expect(Route::has('custom.webhook'))->toBeTrue();

    $route = Route::getRoutes()->getByName('custom.webhook');

    // Ensure method is POST
    expect($route->methods())->toContain('POST');

    // Group prefix 'hooks' + path '/custom/path' => 'hooks/custom/path'
    expect($route->uri())->toBe('hooks/custom/path');
});

it('applies configured middleware to the webhook route', function () {
    // Ensure route is registered as per previous test (in case of isolation)
    if (!Route::has('custom.webhook')) {
        (new LaravelAiAssistantServiceProvider(app()))->packageBooted();
    }

    $route = Route::getRoutes()->getByName('custom.webhook');

    $middleware = method_exists($route, 'gatherMiddleware')
        ? $route->gatherMiddleware()
        : (array) ($route->getAction('middleware') ?? []);

    expect($middleware)->toContain('api');
});
