<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use CreativeCrafts\LaravelAiAssistant\Exceptions\ConfigurationValidationException;
use CreativeCrafts\LaravelAiAssistant\Exceptions\InvalidApiKeyException;
use Exception;
use InvalidArgumentException;
use JsonException;
use Random\RandomException;
use RuntimeException;

/**
 * Service for handling security-related operations.
 * This service provides methods for API key validation, rate limiting,
 * request signing, and other security features.
 */
class SecurityService
{
    private const OPENAI_API_KEY_PATTERN = '/^sk-[A-Za-z0-9_-]{161}$/';
    private const OPENAI_ORG_PATTERN = '/^org-[a-zA-Z0-9]{24}$/';
    private const MAX_REQUEST_SIZE = 10 * 1024 * 1024; // 10MB
    /** @phpstan-ignore-next-line constant is intentionally unused; reserved for future feature flags */
    private const RATE_LIMIT_CACHE_PREFIX = 'rate_limit:';

    private CacheService $cacheService;
    private LoggingService $loggingService;

    public function __construct(CacheService $cacheService, LoggingService $loggingService)
    {
        $this->cacheService = $cacheService;
        $this->loggingService = $loggingService;
    }

    /**
     * Apply rate limiting to an operation.
     *
     * @param string $identifier Unique identifier for rate limiting
     * @param callable $operation The operation to execute
     * @param int $maxRequests Maximum requests allowed
     * @param int $timeWindow Time window in seconds
     * @return mixed The result of the operation
     * @throws InvalidArgumentException When rate limit is exceeded
     */
    public function applyRateLimit(string $identifier, callable $operation, int $maxRequests = 100, int $timeWindow = 3600): mixed
    {
        if (!$this->checkRateLimit($identifier, $maxRequests, $timeWindow)) {
            throw new RuntimeException('Rate limit exceeded');
        }

        return $operation();
    }

    /**
     * Check if a request should be rate limited.
     *
     * @param string $identifier Unique identifier for rate limiting (e.g., IP, user ID)
     * @param int $maxRequests Maximum number of requests allowed
     * @param int $timeWindow Time window in seconds
     * @return bool True if the request should be allowed, false if rate limited
     */
    public function checkRateLimit(string $identifier, int $maxRequests = 100, int $timeWindow = 3600): bool
    {
        $cacheKey = 'rate_limit:' . $identifier;

        try {
            // Get current request count from cache
            $currentCount = $this->cacheService->getConfig($cacheKey);

            if ($currentCount === null) {
                $currentCount = 0;
            }

            // Check if we've exceeded the rate limit
            if ($currentCount >= $maxRequests) {
                $this->loggingService->logSecurityEvent(
                    'rate_limit_exceeded',
                    'Rate limit exceeded for identifier',
                    [
                        'identifier_hash' => hash('sha256', $identifier),
                        'request_count' => $currentCount,
                        'max_requests' => $maxRequests,
                        'time_window' => $timeWindow
                    ]
                );

                return false;
            }

            // Increment counter and store
            $newCount = $currentCount + 1;
            $this->cacheService->cacheConfig($cacheKey, $newCount, $timeWindow);

            // Log performance event
            $this->loggingService->logPerformanceEvent(
                'rate_limit_check',
                'Rate limit checked successfully',
                [
                    'identifier_hash' => hash('sha256', $identifier),
                    'current_count' => $newCount,
                    'max_requests' => $maxRequests
                ]
            );

            return true;
        } catch (Exception $e) {
            // If caching fails, allow the request but log the error
            $this->loggingService->logError(
                'rate_limit_check',
                'Failed to check rate limit: ' . $e->getMessage(),
                ['identifier_hash' => hash('sha256', $identifier)]
            );

            return true;
        }
    }

    /**
     * Verify a request signature.
     *
     * @param array $payload The request payload
     * @param string $signature The signature to verify
     * @param string $secret Secret key for verification
     * @return bool True if signature is valid
     * @throws InvalidArgumentException When signature or timestamp is invalid
     */
    public function verifyRequestSignature(
        array $payload,
        string $signature,
        string $secret
    ): bool {
        // Validate timestamp if present
        if (isset($payload['timestamp'])) {
            $currentTime = time();
            $requestTime = (int)$payload['timestamp'];
            $toleranceSeconds = 300;

            if (abs($currentTime - $requestTime) > $toleranceSeconds) {
                $this->loggingService->logSecurityEvent(
                    'signature_timestamp_invalid',
                    'Request timestamp is outside tolerance window',
                    [
                        'request_time' => $requestTime,
                        'current_time' => $currentTime,
                        'difference' => abs($currentTime - $requestTime),
                        'tolerance' => $toleranceSeconds
                    ]
                );

                throw new InvalidArgumentException('Request timestamp is too old');
            }
        }

        // Generate the expected signature
        $expectedSignature = $this->generateRequestSignature($payload, $secret);

        // Use hash_equals for timing-safe comparison
        if (!hash_equals($expectedSignature, $signature)) {
            $this->loggingService->logSecurityEvent(
                'signature_verification_failed',
                'Request signature verification failed',
                ['payload_size' => count($payload)]
            );

            throw new InvalidArgumentException('Invalid request signature');
        }

        return true;
    }

    /**
     * Generate a simple request signature for integrity verification.
     *
     * @param array $payload The request payload
     * @param string $secret Secret key for signing
     * @return string Request signature
     * @throws JsonException
     */
    public function generateRequestSignature(array $payload, string $secret): string
    {
        if (trim($secret) === '') {
            throw new InvalidArgumentException('Secret cannot be empty');
        }

        // Sort the payload keys for deterministic JSON encoding
        ksort($payload);
        $payloadJson = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $timestamp = $payload['timestamp'] ?? time();
        $stringToSign = $timestamp . '.' . $payloadJson;

        return hash_hmac('sha256', $stringToSign, $secret);
    }

    /**
     * Sanitize sensitive data from arrays for logging.
     *
     * @param array $data Data array to sanitize
     * @param array $sensitiveKeys Keys to redact
     * @return array Sanitized array
     */
    public function sanitizeSensitiveData(array $data, array $sensitiveKeys = []): array
    {
        $defaultSensitiveKeys = [
            'api_key',
            'token',
            'secret',
            'password',
            'authorization',
            'x-api-key',
            'bearer',
        ];

        $allSensitiveKeys = array_merge($defaultSensitiveKeys, $sensitiveKeys);
        $sanitized = $data;

        foreach ($sanitized as $key => $value) {
            if (is_string($key)) {
                $keyLower = strtolower($key);

                foreach ($allSensitiveKeys as $sensitiveKey) {
                    if (stripos($keyLower, strtolower($sensitiveKey)) !== false) {
                        $sanitized[$key] = '[REDACTED]';
                        break;
                    }
                }
            }

            if (is_array($value)) {
                $sanitized[$key] = $this->sanitizeSensitiveData($value, $sensitiveKeys);
            }
        }

        return $sanitized;
    }

    /**
     * Validate request size to prevent DoS attacks.
     *
     * @param mixed $data The data to check size for
     * @param int $maxSize Maximum allowed size in bytes
     * @return bool True if size is acceptable
     * @throws InvalidArgumentException When request size exceeds limit
     * @throws JsonException
     */
    public function validateRequestSize($data, int $maxSize = self::MAX_REQUEST_SIZE): bool
    {
        $serialized = is_string($data) ? $data : json_encode($data, JSON_THROW_ON_ERROR);
        if ($serialized === false) {
            $serialized = '';
        }
        $size = strlen($serialized);

        if ($size > $maxSize) {
            $this->loggingService->logSecurityEvent(
                'request_size_exceeded',
                'Request size exceeds maximum allowed size',
                [
                    'request_size' => $size,
                    'max_size' => $maxSize,
                    'size_mb' => round($size / (1024 * 1024), 2)
                ]
            );

            throw new InvalidArgumentException('Request size exceeds maximum allowed');
        }

        return true;
    }

    /**
     * Generate a secure random token.
     *
     * @param int $length Token length in bytes
     * @return string Hex encoded random token
     * @throws RandomException
     */
    public function generateSecureToken(int $length = 32): string
    {
        if ($length < 8) {
            throw new InvalidArgumentException('Token length must be at least 8 bytes');
        }

        return bin2hex(random_bytes($length));
    }

    /**
     * Validate configuration security settings.
     *
     * @param array $config Configuration array to validate
     * @return array Array with 'is_secure' and 'warnings' keys
     * @throws ConfigurationValidationException When configuration is insecure
     */
    public function validateConfigurationSecurity(array $config): array
    {
        $warnings = [];
        $issues = [];

        // Check for required security settings
        if (empty($config['api_key'])) {
            $this->loggingService->logSecurityEvent(
                'missing_api_key',
                'API key is required and must not be empty',
                []
            );
            throw new ConfigurationValidationException('API key is required');
        }

        try {
            $this->validateApiKey($config['api_key']);
        } catch (InvalidArgumentException $e) {
            $issues[] = 'API key validation failed: ' . $e->getMessage();
        }

        // Check organization ID if provided
        if (!empty($config['organization_id'])) {
            try {
                $this->validateOrganizationId($config['organization_id']);
            } catch (InvalidArgumentException $e) {
                $issues[] = 'Organization ID validation failed: ' . $e->getMessage();
            }
        }

        // Check for warnings
        if (isset($config['organization_id']) && empty($config['organization_id'])) {
            $this->loggingService->logSecurityEvent(
                'empty_organization_id',
                'Organization ID is empty',
                []
            );
            $warnings[] = 'Organization ID is empty';
        }

        if (isset($config['debug_mode']) && $config['debug_mode'] === true) {
            $this->loggingService->logSecurityEvent(
                'debug_mode_enabled',
                'Debug mode is enabled',
                []
            );
            $warnings[] = 'Debug mode is enabled';
        }

        // Check for secure parameter values
        if (isset($config['temperature']) && $config['temperature'] > 2.0) {
            $warnings[] = 'Temperature value is unusually high and may indicate configuration error';
        }

        if (isset($config['max_completion_tokens']) && $config['max_completion_tokens'] > 4096) {
            $warnings[] = 'Max completion tokens is very high and may lead to excessive API costs';
        }

        if (!empty($issues)) {
            throw new ConfigurationValidationException(
                'Configuration security validation failed: ' . implode(', ', $issues)
            );
        }

        return [
            'is_secure' => empty($warnings),
            'warnings' => $warnings
        ];
    }

    /**
     * Validate OpenAI API key format and basic validity.
     *
     * @param string $apiKey The API key to validate
     * @return bool True if the API key appears valid
     * @throws InvalidApiKeyException When API key is invalid
     */
    public function validateApiKey(string $apiKey): bool
    {
        if (trim($apiKey) === '') {
            throw new InvalidApiKeyException('API key cannot be empty.');
        }

        // Check a basic format first (must start with sk-)
        if (!str_starts_with($apiKey, 'sk-')) {
            $this->loggingService->logSecurityEvent(
                'invalid_api_key_format',
                'API key does not match expected OpenAI format',
                ['key_length' => strlen($apiKey), 'key_prefix' => substr($apiKey, 0, 3)]
            );

            throw new InvalidApiKeyException('Invalid API key format');
        }

        // Check for obvious test patterns first (before length validation)
        $testPatterns = ['sk-test', 'sk-fake', 'sk-demo', 'sk-your-api-key-here'];
        $keyLower = strtolower($apiKey);
        foreach ($testPatterns as $pattern) {
            if (str_starts_with($keyLower, strtolower($pattern))) {
                $this->loggingService->logSecurityEvent(
                    'suspicious_api_key',
                    'API key appears to be invalid or test key',
                    []
                );
                throw new InvalidApiKeyException('API key appears to be invalid or a test key.');
            }
        }

        // Check length first - this catches boundary conditions like very long keys
        if (strlen($apiKey) !== 164) { // sk- + 164 characters
            $this->loggingService->logSecurityEvent(
                'invalid_api_key_format',
                'API key does not match expected OpenAI format',
                ['key_length' => strlen($apiKey), 'key_prefix' => substr($apiKey, 0, 3)]
            );
            throw new InvalidApiKeyException('API key length is invalid.');
        }

        // Check if the API key matches the expected OpenAI format (only for proper length keys)
        if (!preg_match(self::OPENAI_API_KEY_PATTERN, $apiKey)) {
            $this->loggingService->logSecurityEvent(
                'invalid_api_key_format',
                'API key does not match expected OpenAI format',
                ['key_length' => strlen($apiKey), 'key_prefix' => substr($apiKey, 0, 3)]
            );

            throw new InvalidApiKeyException('Invalid API key format');
        }

        // Check for other obvious invalid patterns (like repeated chars) - only for proper length keys
        if ($this->isObviouslyInvalidApiKey($apiKey)) {
            $this->loggingService->logSecurityEvent(
                'suspicious_api_key',
                'API key appears to be invalid or test key',
                []
            );

            throw new InvalidApiKeyException('API key appears to be invalid or a test key.');
        }

        return true;
    }

    /**
     * Validate OpenAI organization ID format.
     *
     * @param string $organizationId The organization ID to validate
     * @return bool True if the organization ID appears valid
     * @throws InvalidArgumentException When organization ID is invalid
     */
    public function validateOrganizationId(string $organizationId): bool
    {
        if (trim($organizationId) === '') {
            return true; // Organization ID is optional
        }

        if (!preg_match(self::OPENAI_ORG_PATTERN, $organizationId)) {
            $this->loggingService->logSecurityEvent(
                'invalid_organization_id',
                'Organization ID does not match expected format',
                ['org_length' => strlen($organizationId)]
            );

            throw new InvalidArgumentException(
                'Invalid organization ID format. Organization IDs should start with "org-" and contain 24 characters after the prefix.'
            );
        }

        return true;
    }

    /**
     * Check if an API key appears to be obviously invalid.
     *
     * @param string $apiKey The API key to check
     * @return bool True if the key appears invalid
     */
    private function isObviouslyInvalidApiKey(string $apiKey): bool
    {
        $invalidPatterns = [
            'sk-000000000000000000000000000000000000000000000000',
            'sk-111111111111111111111111111111111111111111111111',
            'sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx',
            'sk-your-api-key-here',
            'sk-test',
            'sk-fake',
            'sk-demo',
        ];

        $keyLower = strtolower($apiKey);

        foreach ($invalidPatterns as $pattern) {
            if (str_starts_with($keyLower, strtolower($pattern))) {
                return true;
            }
        }

        // Check for repeated characters (likely test keys)
        $keyContent = substr($apiKey, 3); // Remove "sk-" prefix
        $uniqueChars = count(array_unique(str_split($keyContent)));

        // If less than 10 unique characters in a 48-character string, likely invalid
        return $uniqueChars < 10;
    }
}
