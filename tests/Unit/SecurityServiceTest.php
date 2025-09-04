<?php

declare(strict_types=1);

use CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException;
use CreativeCrafts\LaravelAiAssistant\Services\CacheService;
use CreativeCrafts\LaravelAiAssistant\Services\LoggingService;
use CreativeCrafts\LaravelAiAssistant\Services\SecurityService;

/**
 * Comprehensive unit tests for SecurityService.
 * Tests all public methods including API key validation, rate limiting,
 * request signing, and data sanitization functionality.
 */

beforeEach(function () {
    $this->cacheServiceMock = Mockery::mock(CacheService::class);
    $this->loggingServiceMock = Mockery::mock(LoggingService::class);

    $this->securityService = new SecurityService(
        $this->cacheServiceMock,
        $this->loggingServiceMock
    );
});

afterEach(function () {
    Mockery::close();
});

test('validate api key with valid key', function () {
    $validApiKey = 'sk-proj-sRum7W1AChLYOkji4VOzoI4P0OPhXkRfp7mn1fH6lOCxaS_II3Tt6lvbSdF3NcvJUde795bsfffffkskkdskdlkslkskldklskdkdfkldfkfdkfdklfklflkfkldklfdkfklfdkldkfklfklfkfkkfklflkf';

    $result = $this->securityService->validateApiKey($validApiKey);

    expect($result)->toBeTrue();
});

test('validate api key throws exception for empty key', function () {
    expect(fn () => $this->securityService->validateApiKey(''))
        ->toThrow(CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException::class, 'API key cannot be empty.');
});

test('validate api key throws exception for invalid format', function () {
    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('invalid_api_key_format', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->validateApiKey('invalid-key-format'))
        ->toThrow(CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException::class, 'Invalid API key format');
});

test('validate api key throws exception for wrong length', function () {
    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('invalid_api_key_format', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->validateApiKey('sk-tooshort'))
        ->toThrow(CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException::class, 'API key length is invalid.');
});

test('validate api key throws exception for test key', function () {
    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('suspicious_api_key', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->validateApiKey('sk-test1234567890abcdef1234567890abcdef12345678'))
        ->toThrow(CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException::class, 'API key appears to be invalid or a test key.');
});

test('validate organization id with valid id', function () {
    $validOrgId = 'org-1234567890abcdef12345678';

    $result = $this->securityService->validateOrganizationId($validOrgId);

    expect($result)->toBeTrue();
});

test('validate organization id returns true for empty', function () {
    $result = $this->securityService->validateOrganizationId('');

    expect($result)->toBeTrue();
});

test('validate organization id throws exception for invalid format', function () {
    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('invalid_organization_id', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->validateOrganizationId('invalid-org-id'))
        ->toThrow(InvalidArgumentException::class, 'Invalid organization ID format');
});

test('check rate limit allows within limit', function () {
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->with('rate_limit:user123')
        ->andReturn(5); // Current count

    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->with('rate_limit:user123', 6, Mockery::type('int'))
        ->andReturn(true);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $result = $this->securityService->checkRateLimit('user123', 10, 60);

    expect($result)->toBeTrue();
});

test('check rate limit denies when exceeded', function () {
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->with('rate_limit:user456')
        ->andReturn(10); // At limit

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('rate_limit_exceeded', Mockery::any(), Mockery::any());

    $result = $this->securityService->checkRateLimit('user456', 10, 60);

    expect($result)->toBeFalse();
});

test('check rate limit handles cache failure', function () {
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->with('rate_limit:user789')
        ->andThrow(new Exception('Cache failure'));

    $this->loggingServiceMock
        ->shouldReceive('logError')
        ->once();

    $result = $this->securityService->checkRateLimit('user789', 10, 60);

    expect($result)->toBeTrue(); // Should allow on cache failure
});

test('apply rate limit executes operation when allowed', function () {
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->with('rate_limit:user123')
        ->andReturn(3);

    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->with('rate_limit:user123', 4, Mockery::type('int'))
        ->andReturn(true);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $executed = false;
    $operation = function () use (&$executed) {
        $executed = true;
        return 'operation_result';
    };

    $result = $this->securityService->applyRateLimit('user123', $operation, 5, 60);

    expect($executed)->toBeTrue()
        ->and($result)->toBe('operation_result');
});

test('apply rate limit throws exception when exceeded', function () {
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->with('rate_limit:user456')
        ->andReturn(5); // At limit

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('rate_limit_exceeded', Mockery::any(), Mockery::any());

    $operation = function () {
        return 'should_not_execute';
    };

    expect(fn () => $this->securityService->applyRateLimit('user456', $operation, 5, 60))
        ->toThrow(Exception::class, 'Rate limit exceeded');
});

test('generate request signature', function () {
    $data = ['key' => 'value', 'timestamp' => time()];
    $secret = 'test-secret-key';

    $signature = $this->securityService->generateRequestSignature($data, $secret);

    expect($signature)->toBeString()
        ->and($signature)->not->toBeEmpty();
});

test('generate request signature throws exception for empty secret', function () {
    $data = ['key' => 'value'];

    expect(fn () => $this->securityService->generateRequestSignature($data, ''))
        ->toThrow(InvalidArgumentException::class, 'Secret cannot be empty');
});

test('verify request signature with valid signature', function () {
    $data = ['key' => 'value', 'timestamp' => time()];
    $secret = 'test-secret-key';

    $signature = $this->securityService->generateRequestSignature($data, $secret);
    $result = $this->securityService->verifyRequestSignature($data, $signature, $secret);

    expect($result)->toBeTrue();
});

test('verify request signature throws exception for invalid timestamp', function () {
    $data = ['key' => 'value', 'timestamp' => time() - 3600]; // 1 hour ago
    $secret = 'test-secret-key';
    $signature = 'invalid-signature';

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('signature_timestamp_invalid', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->verifyRequestSignature($data, $signature, $secret))
        ->toThrow(InvalidArgumentException::class, 'Request timestamp is too old');
});

test('verify request signature throws exception for invalid signature', function () {
    $data = ['key' => 'value', 'timestamp' => time()];
    $secret = 'test-secret-key';
    $invalidSignature = 'invalid-signature';

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('signature_verification_failed', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->verifyRequestSignature($data, $invalidSignature, $secret))
        ->toThrow(InvalidArgumentException::class, 'Invalid request signature');
});

test('sanitize sensitive data', function () {
    $data = [
        'api_key' => 'sk-1234567890',
        'password' => 'secret123',
        'token' => 'abc123',
        'safe_data' => 'public_info'
    ];

    $result = $this->securityService->sanitizeSensitiveData($data);

    expect($result['api_key'])->toBe('[REDACTED]')
        ->and($result['password'])->toBe('[REDACTED]')
        ->and($result['token'])->toBe('[REDACTED]')
        ->and($result['safe_data'])->toBe('public_info');
});

test('sanitize sensitive data with custom keys', function () {
    $data = [
        'custom_secret' => 'secret_value',
        'normal_field' => 'normal_value'
    ];

    $customKeys = ['custom_secret'];
    $result = $this->securityService->sanitizeSensitiveData($data, $customKeys);

    expect($result['custom_secret'])->toBe('[REDACTED]')
        ->and($result['normal_field'])->toBe('normal_value');
});

test('validate request size within limit', function () {
    $data = str_repeat('a', 1000); // 1KB

    $result = $this->securityService->validateRequestSize($data, 2048); // 2KB limit

    expect($result)->toBeTrue();
});

test('validate request size throws exception when exceeded', function () {
    $data = str_repeat('a', 3000); // 3KB

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('request_size_exceeded', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->validateRequestSize($data, 2048))
        ->toThrow(InvalidArgumentException::class, 'Request size exceeds maximum allowed');
});

test('generate secure token', function () {
    $token = $this->securityService->generateSecureToken(32);

    expect($token)->toBeString()
        ->and(strlen($token))->toBe(64); // 32 bytes = 64 hex characters
});

test('generate secure token throws exception for short length', function () {
    expect(fn () => $this->securityService->generateSecureToken(7))
        ->toThrow(InvalidArgumentException::class, 'Token length must be at least 8 bytes');
});

test('validate configuration security with valid config', function () {
    $validApiKey = 'sk-proj-sRum7W1AChLYOkji4VOzoI4P0OPhXkRfp7mn1fH6lOCxaS_II3Tt6lvbSdF3NcvJUde795bsfffffkskkdskdlkslkskldklskdkdfkldfkfdkfdklfklflkfkldklfdkfklfdkldkfklfklfkfkkfklflkf';
    $config = [
        'api_key' => $validApiKey,
        'organization_id' => 'org-1234567890abcdef12345678'
    ];

    $result = $this->securityService->validateConfigurationSecurity($config);

    expect($result)->toBeArray()
        ->and($result['is_secure'])->toBeTrue()
        ->and($result['warnings'])->toBeEmpty();
});

test('validate configuration security throws exception for missing api key', function () {
    $config = [
        'organization_id' => 'org-1234567890abcdef12345678'
    ];

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('missing_api_key', Mockery::any(), Mockery::any());

    expect(fn () => $this->securityService->validateConfigurationSecurity($config))
        ->toThrow(ConfigurationValidationException::class, 'API key is required');
});

test('validate configuration security with warnings', function () {
    $validApiKey = 'sk-proj-sRum7W1AChLYOkji4VOzoI4P0OPhXkRfp7mn1fH6lOCxaS_II3Tt6lvbSdF3NcvJUde795bsfffffkskkdskdlkslkskldklskdkdfkldfkfdkfdklfklflkfkldklfdkfklfdkldkfklfklfkfkkfklflkf';
    $config = [
        'api_key' => $validApiKey,
        'organization_id' => '', // Empty org ID should trigger warning
        'debug_mode' => true // Debug mode should trigger warning
    ];

    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->times(2);

    $result = $this->securityService->validateConfigurationSecurity($config);

    expect($result)->toBeArray()
        ->and($result['is_secure'])->toBeFalse()
        ->and($result['warnings'])->not->toBeEmpty();
});

test('edge case empty arrays and null values', function () {
    // Test sanitizing empty data
    $emptyResult = $this->securityService->sanitizeSensitiveData([]);
    expect($emptyResult)->toBeArray()->and($emptyResult)->toBeEmpty();

    // Test rate limiting with zero limits
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->andReturn(0);

    $this->loggingServiceMock
        ->shouldReceive('logError')
        ->once();

    $zeroLimitResult = $this->securityService->checkRateLimit('user', 0, 60);
    expect($zeroLimitResult)->toBeTrue(); // Should return true on cache failure

    // Test signature generation with null/empty data
    expect(fn () => $this->securityService->generateRequestSignature([], 'secret'))
        ->not->toThrow(Exception::class);
});

test('boundary conditions', function () {
    // Test very long API key
    $this->loggingServiceMock
        ->shouldReceive('logSecurityEvent')
        ->once()
        ->with('invalid_api_key_format', Mockery::any(), Mockery::any());

    $longKey = 'sk-' . str_repeat('a', 100);
    expect(fn () => $this->securityService->validateApiKey($longKey))
        ->toThrow(InvalidArgumentException::class);

    // Test maximum request size
    $maxSizeData = str_repeat('a', 1048576); // 1MB
    $result = $this->securityService->validateRequestSize($maxSizeData, 1048576);
    expect($result)->toBeTrue();

    // Test minimum token length
    $minToken = $this->securityService->generateSecureToken(8);
    expect($minToken)->toBeString();

    // Test rate limit at exact boundary
    $this->cacheServiceMock
        ->shouldReceive('getConfig')
        ->andReturn(9);

    $this->cacheServiceMock
        ->shouldReceive('cacheConfig')
        ->andReturn(true);

    $this->loggingServiceMock
        ->shouldReceive('logPerformanceEvent')
        ->once();

    $boundaryResult = $this->securityService->checkRateLimit('boundary_user', 10, 60);
    expect($boundaryResult)->toBeTrue();
});
