<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\ImageAction;
use CreativeCrafts\LaravelAiAssistant\Enums\OpenAiEndpoint;

it('has all expected image action cases', function () {
    $cases = ImageAction::cases();

    expect($cases)->toHaveCount(3)
        ->and(ImageAction::Generate->value)->toBe('generate')
        ->and(ImageAction::Edit->value)->toBe('edit')
        ->and(ImageAction::Variation->value)->toBe('variation');
});

it('identifies actions requiring prompt correctly', function () {
    expect(ImageAction::Generate->requiresPrompt())->toBeTrue()
        ->and(ImageAction::Edit->requiresPrompt())->toBeTrue()
        ->and(ImageAction::Variation->requiresPrompt())->toBeFalse();
});

it('identifies actions requiring source image correctly', function () {
    expect(ImageAction::Edit->requiresSourceImage())->toBeTrue()
        ->and(ImageAction::Variation->requiresSourceImage())->toBeTrue()
        ->and(ImageAction::Generate->requiresSourceImage())->toBeFalse();
});

it('identifies actions requiring mask correctly', function () {
    expect(ImageAction::Edit->requiresMask())->toBeTrue()
        ->and(ImageAction::Generate->requiresMask())->toBeFalse()
        ->and(ImageAction::Variation->requiresMask())->toBeFalse();
});

it('maps to correct endpoint', function () {
    expect(ImageAction::Generate->toEndpoint())->toBe(OpenAiEndpoint::ImageGeneration)
        ->and(ImageAction::Edit->toEndpoint())->toBe(OpenAiEndpoint::ImageEdit)
        ->and(ImageAction::Variation->toEndpoint())->toBe(OpenAiEndpoint::ImageVariation);
});

it('can be created from string value', function () {
    expect(ImageAction::from('generate'))->toBe(ImageAction::Generate)
        ->and(ImageAction::from('edit'))->toBe(ImageAction::Edit)
        ->and(ImageAction::from('variation'))->toBe(ImageAction::Variation);
});

it('can be used in match expressions', function () {
    $action = ImageAction::Generate;

    $result = match ($action) {
        ImageAction::Generate => 'create new image',
        ImageAction::Edit => 'edit existing image',
        ImageAction::Variation => 'create image variation',
    };

    expect($result)->toBe('create new image');
});
