<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Enums\ResponseStatus;

it('has all expected status cases', function () {
    $cases = ResponseStatus::cases();

    expect($cases)->toHaveCount(6)
        ->and(ResponseStatus::Pending->value)->toBe('pending')
        ->and(ResponseStatus::InProgress->value)->toBe('in_progress')
        ->and(ResponseStatus::Completed->value)->toBe('completed')
        ->and(ResponseStatus::Failed->value)->toBe('failed')
        ->and(ResponseStatus::RequiresAction->value)->toBe('requires_action')
        ->and(ResponseStatus::Unknown->value)->toBe('unknown');
});

it('identifies terminal statuses correctly', function () {
    expect(ResponseStatus::Completed->isTerminal())->toBeTrue()
        ->and(ResponseStatus::Failed->isTerminal())->toBeTrue()
        ->and(ResponseStatus::Pending->isTerminal())->toBeFalse()
        ->and(ResponseStatus::InProgress->isTerminal())->toBeFalse()
        ->and(ResponseStatus::RequiresAction->isTerminal())->toBeFalse()
        ->and(ResponseStatus::Unknown->isTerminal())->toBeFalse();
});

it('identifies active statuses correctly', function () {
    expect(ResponseStatus::Pending->isActive())->toBeTrue()
        ->and(ResponseStatus::InProgress->isActive())->toBeTrue()
        ->and(ResponseStatus::RequiresAction->isActive())->toBeTrue()
        ->and(ResponseStatus::Completed->isActive())->toBeFalse()
        ->and(ResponseStatus::Failed->isActive())->toBeFalse()
        ->and(ResponseStatus::Unknown->isActive())->toBeFalse();
});

it('identifies statuses requiring intervention correctly', function () {
    expect(ResponseStatus::RequiresAction->requiresIntervention())->toBeTrue()
        ->and(ResponseStatus::Pending->requiresIntervention())->toBeFalse()
        ->and(ResponseStatus::InProgress->requiresIntervention())->toBeFalse()
        ->and(ResponseStatus::Completed->requiresIntervention())->toBeFalse()
        ->and(ResponseStatus::Failed->requiresIntervention())->toBeFalse()
        ->and(ResponseStatus::Unknown->requiresIntervention())->toBeFalse();
});

it('can be created from string value', function () {
    expect(ResponseStatus::from('completed'))->toBe(ResponseStatus::Completed)
        ->and(ResponseStatus::from('failed'))->toBe(ResponseStatus::Failed)
        ->and(ResponseStatus::from('requires_action'))->toBe(ResponseStatus::RequiresAction)
        ->and(ResponseStatus::from('pending'))->toBe(ResponseStatus::Pending)
        ->and(ResponseStatus::from('in_progress'))->toBe(ResponseStatus::InProgress)
        ->and(ResponseStatus::from('unknown'))->toBe(ResponseStatus::Unknown);
});

it('can be used in match expressions', function () {
    $status = ResponseStatus::Completed;

    $result = match ($status) {
        ResponseStatus::Completed => 'done',
        ResponseStatus::Failed => 'error',
        ResponseStatus::RequiresAction => 'action_needed',
        default => 'processing',
    };

    expect($result)->toBe('done');
});
