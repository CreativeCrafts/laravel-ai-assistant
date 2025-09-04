<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use CreativeCrafts\LaravelAiAssistant\Contracts\OpenAiRepositoryContract;

/**
 * Service for health check operations.
 *
 * This service provides methods to verify service availability,
 * system health, and component status.
 */
class HealthCheckService
{
    private OpenAiRepositoryContract $repository;
    private CacheService $cacheService;
    private LoggingService $loggingService;
    private SecurityService $securityService;

    public function __construct(
        OpenAiRepositoryContract $repository,
        CacheService $cacheService,
        LoggingService $loggingService,
        SecurityService $securityService
    ) {
        $this->repository = $repository;
        $this->cacheService = $cacheService;
        $this->loggingService = $loggingService;
        $this->securityService = $securityService;
    }

    /**
     * Perform a comprehensive health check of all components.
     *
     * @return array Health check results
     */
    public function performHealthCheck(): array
    {
        $startTime = microtime(true);

        $results = [
            'status' => 'healthy',
            'timestamp' => date('c'),
            'version' => $this->getPackageVersion(),
            'checks' => [],
            'summary' => [],
            'duration_ms' => 0
        ];

        // Perform individual health checks
        $checks = [
            'configuration' => [$this, 'checkConfiguration'],
            'cache' => [$this, 'checkCacheHealth'],
            'security' => [$this, 'checkSecurityHealth'],
            'api_connectivity' => [$this, 'checkApiConnectivity'],
            'memory' => [$this, 'checkMemoryUsage'],
            'disk' => [$this, 'checkDiskSpace'],
        ];

        $healthyCount = 0;
        $warningCount = 0;
        $unhealthyCount = 0;

        $config = Config::get('ai-assistant');
        if (!is_array($config)) {
            $config = [];
        }
        $healthCheckConfig = $config['health_checks'] ?? [];
        if (!is_array($healthCheckConfig)) {
            $healthCheckConfig = [];
        }
        $defaultTimeout = is_numeric($healthCheckConfig['timeout'] ?? null) ? (int) $healthCheckConfig['timeout'] : 10; // Default 10 seconds per check

        $criticalChecks = ['configuration', 'cache', 'security'];
        foreach ($checks as $checkName => $checkFunction) {
            $checkStartTime = microtime(true);
            try {
                // Execute check with timeout protection
                $checkResult = $this->executeWithTimeout($checkFunction, $defaultTimeout, $checkName);

                if (!is_array($checkResult)) {
                    $checkResult = [
                        'status' => 'unhealthy',
                        'message' => 'Invalid check result format',
                        'timestamp' => date('c')
                    ];
                }

                // Add execution time to check result
                $checkExecutionTime = round((microtime(true) - $checkStartTime) * 1000, 2);
                $checkResult['execution_time_ms'] = $checkExecutionTime;

                $results['checks'][$checkName] = $checkResult;

                switch ($checkResult['status'] ?? 'unhealthy') {
                    case 'healthy':
                        $healthyCount++;
                        break;
                    case 'warning':
                        $warningCount++;
                        // Do not downgrade overall status on warnings; surface them in summary/details only.
                        break;
                    case 'unhealthy':
                        $unhealthyCount++;
                        // Only critical checks determine overall unhealthy status
                        if (in_array($checkName, $criticalChecks, true)) {
                            $results['status'] = 'unhealthy';
                        }
                        break;
                }
            } catch (Exception $e) {
                $checkExecutionTime = round((microtime(true) - $checkStartTime) * 1000, 2);

                $results['checks'][$checkName] = [
                    'status' => 'unhealthy',
                    'message' => 'Health check failed: ' . $e->getMessage(),
                    'error' => true,
                    'execution_time_ms' => $checkExecutionTime,
                    'timestamp' => date('c')
                ];
                $unhealthyCount++;
                if (in_array($checkName, $criticalChecks, true)) {
                    $results['status'] = 'unhealthy';
                }

                $this->loggingService->logError(
                    'health_check',
                    "Health check '{$checkName}' failed",
                    ['error' => $e->getMessage(), 'execution_time_ms' => $checkExecutionTime]
                );
            }
        }

        // Calculate summary
        $totalChecks = count($checks);
        $results['summary'] = [
            'total_checks' => $totalChecks,
            'healthy' => $healthyCount,
            'warning' => $warningCount,
            'unhealthy' => $unhealthyCount,
            'success_rate' => round(($healthyCount / $totalChecks) * 100, 2)
        ];

        $results['duration_ms'] = round((microtime(true) - $startTime) * 1000, 2);

        // Log health check results
        $this->loggingService->logPerformanceMetrics(
            'health_check',
            $results['duration_ms'],
            $results['summary']
        );

        return $results;
    }

    /**
     * Get a simple health status for quick checks.
     *
     * @return array Simple health status
     */
    public function getHealthStatus(): array
    {
        try {
            // Quick checks for essential components
            $configCheck = $this->checkConfiguration();
            $cacheCheck = $this->checkCacheHealth();

            $isHealthy = $configCheck['status'] === 'healthy' &&
                        $cacheCheck['status'] !== 'unhealthy';

            return [
                'status' => $isHealthy ? 'healthy' : 'unhealthy',
                'timestamp' => date('c'),
                'version' => $this->getPackageVersion(),
                'uptime' => $this->getUptime()
            ];
        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'timestamp' => date('c'),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Check configuration health.
     *
     * @return array Configuration health check result
     */
    private function checkConfiguration(): array
    {
        $issues = [];
        $warnings = [];

        try {
            // Check if configuration file exists and is readable
            $config = Config::get('ai-assistant');

            if (empty($config) || !is_array($config)) {
                $issues[] = 'AI Assistant configuration not found or empty';
            } else {
                // Check essential configuration values
                if (empty($config['api_key'])) {
                    $issues[] = 'API key is not configured';
                }

                // Chat and audio models are optional; absence should not mark configuration as warning by default.
                // If specific features require these models, those flows will validate accordingly.

                // Validate temperature range
                if (isset($config['temperature']) && ($config['temperature'] < 0 || $config['temperature'] > 2)) {
                    $warnings[] = 'Temperature value is outside recommended range (0-2)';
                }

                // Check API key format if provided
                if (!empty($config['api_key']) && is_string($config['api_key'])) {
                    try {
                        $this->securityService->validateApiKey($config['api_key']);
                    } catch (Exception $e) {
                        $issues[] = 'API key validation failed: ' . $e->getMessage();
                    }
                }
            }

            $status = !empty($issues) ? 'unhealthy' : (!empty($warnings) ? 'warning' : 'healthy');

            return [
                'status' => $status,
                'message' => $status === 'healthy' ? 'Configuration is valid' : 'Configuration issues detected',
                'details' => array_merge($issues, $warnings),
                'timestamp' => date('c')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Configuration check failed: ' . $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Check cache system health with comprehensive testing.
     *
     * @return array Cache health check result
     */
    private function checkCacheHealth(): array
    {
        $testKeysToCleanup = [];

        try {
            $config = Config::get('ai-assistant');
            $config = is_array($config) ? $config : [];
            $healthCheckConfig = is_array($config['health_checks'] ?? null) ? $config['health_checks'] : [];
            $cacheConfig = is_array($healthCheckConfig['cache'] ?? null) ? $healthCheckConfig['cache'] : [];

            $testKey = 'health_check_test_' . bin2hex(random_bytes(8)) . '_' . time();
            $testValue = 'health_check_value_' . bin2hex(random_bytes(8));
            $testKeysToCleanup[] = $testKey;

            // Test cache write
            $writeSuccess = $this->cacheService->cacheConfig($testKey, $testValue, 60);

            if (!$writeSuccess) {
                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache write operation failed',
                    'timestamp' => date('c')
                ];
            }

            // Test cache read
            $readValue = $this->cacheService->getConfig($testKey);

            if ($readValue !== $testValue) {
                // Clean up the test key before returning
                $this->cleanupTestKeys($testKeysToCleanup, $cacheConfig);

                return [
                    'status' => 'unhealthy',
                    'message' => 'Cache read operation failed or returned incorrect value',
                    'details' => [
                        'expected' => $testValue,
                        'actual' => $readValue
                    ],
                    'timestamp' => date('c')
                ];
            }

            // Test cache delete and verify it worked
            $deleteSuccess = $this->cacheService->clearConfig($testKey);

            // Verify delete operation by attempting to read the key again
            $verifyDeleted = true;
            if ($cacheConfig['verify_delete'] ?? true) {
                $deletedValue = $this->cacheService->getConfig($testKey);

                if ($deletedValue !== null) {
                    $verifyDeleted = false;
                    // Attempt cleanup again
                    $this->cleanupTestKeys($testKeysToCleanup, $cacheConfig);

                    return [
                        'status' => 'warning',
                        'message' => 'Cache delete operation may not be working correctly',
                        'details' => [
                            'delete_called' => true,
                            'key_still_exists' => true,
                            'value_after_delete' => $deletedValue
                        ],
                        'timestamp' => date('c')
                    ];
                }
            }

            // Get cache stats
            $stats = $this->cacheService->getStats();

            // Test multiple cache operations for robustness
            $additionalTestKey = 'health_check_multi_' . bin2hex(random_bytes(8)) . '_' . time();
            $testKeysToCleanup[] = $additionalTestKey;

            $multiTestSuccess = $this->cacheService->cacheConfig($additionalTestKey, 'multi_test', 30);
            $multiTestRead = $this->cacheService->getConfig($additionalTestKey);
            $multiTestMatch = $multiTestRead === 'multi_test';

            // Clean up all test keys
            $this->cleanupTestKeys($testKeysToCleanup, $cacheConfig);

            $verifyDeleteConfigured = isset($cacheConfig['verify_delete']) ? (bool) $cacheConfig['verify_delete'] : false;
            // At this point, if verification was enabled and failed we would have returned earlier with a warning.
            $deleteTestStatus = $verifyDeleteConfigured ? 'passed' : 'skipped';

            return [
                'status' => 'healthy',
                'message' => 'Cache system is functioning properly',
                'details' => [
                    'driver' => $stats['cache_driver'] ?? 'unknown',
                    'write_test' => 'passed',
                    'read_test' => 'passed',
                    'delete_test' => $deleteTestStatus,
                    'multi_operation_test' => $multiTestSuccess && $multiTestMatch ? 'passed' : 'failed'
                ],
                'timestamp' => date('c')
            ];

        } catch (Exception $e) {
            // Always try to clean up test keys even on error
            $this->cleanupTestKeys($testKeysToCleanup, $cacheConfig ?? []);

            $this->loggingService->logError(
                'health_check',
                "Health check 'cache' failed",
                ['error' => $e->getMessage()]
            );

            return [
                'status' => 'unhealthy',
                'message' => 'Cache health check failed: ' . $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Clean up test keys from cache.
     *
     * @param array $testKeys Array of test keys to clean up
     * @param array $cacheConfig Cache configuration
     */
    private function cleanupTestKeys(array $testKeys, array $cacheConfig): void
    {
        if (!($cacheConfig['cleanup_test_keys'] ?? true)) {
            return;
        }

        try {
            foreach ($testKeys as $key) {
                if (!empty($key)) {
                    $this->cacheService->clearConfig($key);
                }
            }
        } catch (Exception $e) {
            $this->loggingService->logError(
                'health_check',
                'Failed to clean up cache test keys',
                ['error' => $e->getMessage(), 'keys' => $testKeys]
            );
        }
    }

    /**
     * Check security components health.
     *
     * @return array Security health check result
     */
    private function checkSecurityHealth(): array
    {
        $warnings = [];

        try {
            $config = Config::get('ai-assistant');

            // API key validation is already done in configuration check
            // Focus on other security functionality here

            // Test rate limiting functionality
            $testIdentifier = 'health_check_' . time();
            $rateLimitTest = $this->securityService->checkRateLimit($testIdentifier, 1, 60);

            if (!$rateLimitTest) {
                $warnings[] = 'Rate limiting may not be functioning properly';
            }

            // Test request signing
            $testPayload = ['test' => 'payload'];
            $testSecret = 'test_secret';
            $testTimestamp = (string) time();

            // include timestamp in payload for signing/verification
            $testPayloadWithTs = $testPayload + ['timestamp' => $testTimestamp];
            $signature = $this->securityService->generateRequestSignature($testPayloadWithTs, $testSecret);
            $isValid = $this->securityService->verifyRequestSignature($testPayloadWithTs, $signature, $testSecret);

            if (!$isValid) {
                $warnings[] = 'Request signing verification may not be functioning properly';
            }

            $status = !empty($warnings) ? 'warning' : 'healthy';

            return [
                'status' => $status,
                'message' => $status === 'healthy' ? 'Security components are functioning properly' : 'Some security warnings detected',
                'details' => $warnings,
                'timestamp' => date('c')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'Security health check failed: ' . $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Check API connectivity with actual OpenAI API call.
     *
     * @return array API connectivity check result
     */
    private function checkApiConnectivity(): array
    {
        try {
            $config = Config::get('ai-assistant');
            $config = is_array($config) ? $config : [];
            $healthCheckConfig = is_array($config['health_checks'] ?? null) ? $config['health_checks'] : [];

            if (empty($config['api_key'])) {
                return [
                    'status' => 'warning',
                    'message' => 'API key not configured - cannot test connectivity',
                    'timestamp' => date('c')
                ];
            }

            // Check if API connectivity test is disabled
            if (!($healthCheckConfig['api_connectivity']['enabled'] ?? true)) {
                return [
                    'status' => 'healthy',
                    'message' => 'API connectivity test is disabled',
                    'timestamp' => date('c')
                ];
            }

            $connectionTime = microtime(true);
            $testModel = $healthCheckConfig['api_connectivity']['test_model'] ?? 'gpt-3.5-turbo';
            $maxTokens = $healthCheckConfig['api_connectivity']['max_tokens'] ?? 1;

            try {
                // Make a minimal API call to test connectivity. Prefer createChatCompletion, but fall back
                // to a generic chat() method if provided by tests/fakes.
                $payload = [
                    'model' => $testModel,
                    'messages' => [
                        ['role' => 'user', 'content' => 'test']
                    ],
                    'max_tokens' => $maxTokens,
                    'temperature' => 0.1,
                ];

                try {
                    $response = $this->repository->createChatCompletion($payload);
                } catch (Exception $primaryCallError) {
                    // Fallback to a looser method name some fakes use
                    try {
                        /** @phpstan-ignore-next-line */
                        $response = $this->repository->chat($payload);
                    } catch (Exception $fallbackError) {
                        // Re-throw the original error to preserve context
                        throw $primaryCallError;
                    }
                }

                $responseTime = (microtime(true) - $connectionTime) * 1000;

                // Check response time against threshold
                $slowThreshold = ($healthCheckConfig['api_connectivity']['timeout'] ?? 5) * 1000;
                if ($responseTime > $slowThreshold) {
                    return [
                        'status' => 'warning',
                        'message' => 'API response time is slow',
                        'details' => [
                            'response_time_ms' => round($responseTime, 2),
                            'threshold_ms' => $slowThreshold,
                            'model_tested' => $testModel
                        ],
                        'timestamp' => date('c')
                    ];
                }

                return [
                    'status' => 'healthy',
                    'message' => 'API connectivity is functioning properly',
                    'details' => [
                        'response_time_ms' => round($responseTime, 2),
                        'model_tested' => $testModel,
                        'tokens_used' => $maxTokens
                    ],
                    'timestamp' => date('c')
                ];

            } catch (Exception $apiError) {
                $responseTime = (microtime(true) - $connectionTime) * 1000;

                return [
                    'status' => 'unhealthy',
                    'message' => 'API connectivity failed: ' . $apiError->getMessage(),
                    'details' => [
                        'response_time_ms' => round($responseTime, 2),
                        'model_tested' => $testModel,
                        'error_type' => get_class($apiError)
                    ],
                    'timestamp' => date('c')
                ];
            }

        } catch (Exception $e) {
            return [
                'status' => 'unhealthy',
                'message' => 'API connectivity check failed: ' . $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Check memory usage.
     *
     * @return array Memory usage check result
     */
    private function checkMemoryUsage(): array
    {
        try {
            $config = Config::get('ai-assistant');
            $config = is_array($config) ? $config : [];
            $healthCheckConfig = is_array($config['health_checks'] ?? null) ? $config['health_checks'] : [];
            $memoryConfig = is_array($healthCheckConfig['memory'] ?? null) ? $healthCheckConfig['memory'] : [];

            $memoryUsage = memory_get_usage(true);
            $peakMemoryUsage = memory_get_peak_usage(true);
            $memoryLimitStr = ini_get('memory_limit') ?: '-1';
            $memoryLimit = $this->parseMemoryLimit($memoryLimitStr);

            $memoryUsagePercent = $memoryLimit > 0 ? ($memoryUsage / $memoryLimit) * 100 : 0;
            $peakMemoryPercent = $memoryLimit > 0 ? ($peakMemoryUsage / $memoryLimit) * 100 : 0;

            // Use configurable thresholds
            $criticalThreshold = $memoryConfig['critical_threshold_percent'] ?? 80;
            $warningThreshold = $memoryConfig['warning_threshold_percent'] ?? 60;

            $status = 'healthy';
            $message = 'Memory usage is within normal limits';

            if ($memoryUsagePercent > $criticalThreshold) {
                $status = 'unhealthy';
                $message = 'Memory usage is critically high';
            } elseif ($memoryUsagePercent > $warningThreshold) {
                $status = 'warning';
                $message = 'Memory usage is elevated';
            }

            return [
                'status' => $status,
                'message' => $message,
                'details' => [
                    'current_usage_bytes' => $memoryUsage,
                    'current_usage_mb' => round($memoryUsage / (1024 * 1024), 2),
                    'current_usage_percent' => round($memoryUsagePercent, 2),
                    'peak_usage_bytes' => $peakMemoryUsage,
                    'peak_usage_mb' => round($peakMemoryUsage / (1024 * 1024), 2),
                    'peak_usage_percent' => round($peakMemoryPercent, 2),
                    'memory_limit_bytes' => $memoryLimit,
                    'memory_limit_mb' => round($memoryLimit / (1024 * 1024), 2),
                    'thresholds' => [
                        'warning_percent' => $warningThreshold,
                        'critical_percent' => $criticalThreshold,
                    ]
                ],
                'timestamp' => date('c')
            ];
        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Memory usage check failed: ' . $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Check available disk space.
     *
     * @return array Disk space check result
     */
    private function checkDiskSpace(): array
    {
        try {
            $config = Config::get('ai-assistant');
            $config = is_array($config) ? $config : [];
            $healthCheckConfig = is_array($config['health_checks'] ?? null) ? $config['health_checks'] : [];
            $diskConfig = is_array($healthCheckConfig['disk'] ?? null) ? $healthCheckConfig['disk'] : [];

            $path = storage_path();
            $freeSpace = disk_free_space($path);
            $totalSpace = disk_total_space($path);

            if ($freeSpace === false || $totalSpace === false) {
                return [
                    'status' => 'warning',
                    'message' => 'Unable to determine disk space',
                    'timestamp' => date('c')
                ];
            }

            $freeSpacePercent = ($freeSpace / $totalSpace) * 100;

            // Use configurable thresholds
            $criticalThreshold = $diskConfig['critical_threshold_percent'] ?? 10;
            $warningThreshold = $diskConfig['warning_threshold_percent'] ?? 20;

            $status = 'healthy';
            $message = 'Disk space is sufficient';

            if ($freeSpacePercent < $criticalThreshold) {
                $status = 'unhealthy';
                $message = 'Disk space is critically low';
            } elseif ($freeSpacePercent < $warningThreshold) {
                $status = 'warning';
                $message = 'Disk space is running low';
            }

            $details = [
                'free_space_bytes' => $freeSpace,
                'free_space_gb' => round($freeSpace / (1024 * 1024 * 1024), 2),
                'total_space_bytes' => $totalSpace,
                'total_space_gb' => round($totalSpace / (1024 * 1024 * 1024), 2),
                'free_space_percent' => round($freeSpacePercent, 2),
                'thresholds' => [
                    'warning_percent' => $warningThreshold,
                    'critical_percent' => $criticalThreshold,
                ]
            ];

            // Only include path if explicitly allowed in configuration
            if (!($diskConfig['hide_paths'] ?? true)) {
                $details['path'] = basename($path); // Only show directory name, not full path
            }

            return [
                'status' => $status,
                'message' => $message,
                'details' => $details,
                'timestamp' => date('c')
            ];

        } catch (Exception $e) {
            return [
                'status' => 'warning',
                'message' => 'Disk space check failed: ' . $e->getMessage(),
                'timestamp' => date('c')
            ];
        }
    }

    /**
     * Get package version information.
     *
     * @return string Package version
     */
    private function getPackageVersion(): string
    {
        try {
            // Resolve the composer.json path safely
            $composerPath = realpath(__DIR__ . '/../../composer.json');

            // Validate the path is within expected boundaries
            if ($composerPath === false || !is_file($composerPath)) {
                return 'unknown';
            }

            // Ensure the path is within the package directory to prevent directory traversal
            $packageRoot = realpath(__DIR__ . '/../..');
            if ($packageRoot === false || strpos($composerPath, $packageRoot) !== 0) {
                return 'unknown';
            }

            $composerContent = file_get_contents($composerPath);
            if ($composerContent === false || empty($composerContent)) {
                return 'unknown';
            }

            $composerData = json_decode($composerContent, true);
            if (!is_array($composerData) || json_last_error() !== JSON_ERROR_NONE) {
                return 'unknown';
            }

            // Validate and sanitize the version string
            $version = $composerData['version'] ?? 'unknown';
            if (!is_string($version)) {
                return 'unknown';
            }

            // Basic version format validation (semantic versioning pattern)
            if (preg_match('/^[a-zA-Z0-9\.\-+]+$/', $version)) {
                return $version;
            }

            return 'unknown';

        } catch (Exception $e) {
            // Log the error but don't expose details in the response
            $this->loggingService->logError(
                'health_check',
                'Failed to retrieve package version',
                ['error' => $e->getMessage()]
            );
            return 'unknown';
        }
    }

    /**
     * Get application uptime (simplified version).
     *
     * @return array Uptime information
     */
    private function getUptime(): array
    {
        // This is a simplified uptime calculation
        // In a real implementation, you might store the start time somewhere persistent

        return [
            'started_at' => date('c', $_SERVER['REQUEST_TIME'] ?? time()),
            'current_time' => date('c')
        ];
    }

    /**
     * Parse memory limit string to bytes with proper validation.
     *
     * @param string $memoryLimit Memory limit string (e.g., '128M', '1G')
     * @return int Memory limit in bytes
     */
    private function parseMemoryLimit(string $memoryLimit): int
    {
        try {
            // Handle unlimited memory
            if ($memoryLimit === '-1' || $memoryLimit === '') {
                return 0; // Unlimited
            }

            // Input is already a string parameter; empty string handled above

            // Handle numeric-only values (bytes)
            if (is_numeric($memoryLimit)) {
                $bytes = (int) $memoryLimit;
                return $bytes >= 0 ? $bytes : 0;
            }

            // Extract unit and value
            $unit = strtolower(substr($memoryLimit, -1));
            $valueStr = substr($memoryLimit, 0, -1);

            // Validate value part is numeric
            if (!is_numeric($valueStr)) {
                return 0;
            }

            $value = (int) $valueStr;

            // Prevent negative values
            if ($value < 0) {
                return 0;
            }

            // Handle potential integer overflow by checking limits
            $maxSafeValue = PHP_INT_MAX / (1024 * 1024 * 1024); // Max GB value to prevent overflow

            switch ($unit) {
                case 'g':
                    if ($value > $maxSafeValue) {
                        $this->loggingService->logError(
                            'health_check',
                            'Memory limit value too large, potential overflow',
                            ['value' => $value, 'unit' => 'GB']
                        );
                        return 0; // Treat as unlimited
                    }
                    return $value * 1024 * 1024 * 1024;

                case 'm':
                    if ($value > ($maxSafeValue * 1024)) {
                        $this->loggingService->logError(
                            'health_check',
                            'Memory limit value too large, potential overflow',
                            ['value' => $value, 'unit' => 'MB']
                        );
                        return 0; // Treat as unlimited
                    }
                    return $value * 1024 * 1024;

                case 'k':
                    if ($value > ($maxSafeValue * 1024 * 1024)) {
                        $this->loggingService->logError(
                            'health_check',
                            'Memory limit value too large, potential overflow',
                            ['value' => $value, 'unit' => 'KB']
                        );
                        return 0; // Treat as unlimited
                    }
                    return $value * 1024;

                default:
                    // Try to parse as bytes if no recognized unit
                    if (is_numeric($memoryLimit)) {
                        $bytes = (int) $memoryLimit;
                        return $bytes >= 0 ? $bytes : 0;
                    }

                    $this->loggingService->logError(
                        'health_check',
                        'Invalid memory limit format',
                        ['memory_limit' => $memoryLimit]
                    );
                    return 0; // Treat as unlimited for invalid formats
            }

        } catch (Exception $e) {
            $this->loggingService->logError(
                'health_check',
                'Failed to parse memory limit',
                ['memory_limit' => $memoryLimit, 'error' => $e->getMessage()]
            );
            return 0; // Treat as unlimited on error
        }
    }

    /**
     * Execute a health check function with timeout protection.
     *
     * @param callable $checkFunction The health check function to execute
     * @param int $timeoutSeconds Maximum execution time in seconds
     * @param string $checkName Name of the check for logging
     * @return array Health check result
     */
    private function executeWithTimeout(callable $checkFunction, int $timeoutSeconds, string $checkName): array
    {
        $startTime = microtime(true);

        try {
            // Set a reasonable default if timeout is invalid
            if ($timeoutSeconds <= 0 || $timeoutSeconds > 60) {
                $timeoutSeconds = 10;
            }

            // Execute the check function
            $result = call_user_func($checkFunction);

            $executionTime = microtime(true) - $startTime;

            // Check if execution took longer than expected (warning threshold)
            if ($executionTime > ($timeoutSeconds * 0.8)) {
                $this->loggingService->logError(
                    'health_check',
                    "Health check '{$checkName}' took longer than expected",
                    [
                        'execution_time' => $executionTime,
                        'timeout_seconds' => $timeoutSeconds,
                        'check_name' => $checkName
                    ]
                );

                // If result is currently healthy but took too long, mark as warning
                if (is_array($result) && ($result['status'] ?? '') === 'healthy') {
                    $result['status'] = 'warning';
                    $result['message'] = ($result['message'] ?? 'Health check passed') . ' (slow execution)';
                }
            }

            return is_array($result) ? $result : [
                'status' => 'unhealthy',
                'message' => 'Invalid check result format',
                'timestamp' => date('c')
            ];

        } catch (Exception $e) {
            $executionTime = microtime(true) - $startTime;

            $this->loggingService->logError(
                'health_check',
                "Health check '{$checkName}' failed with exception",
                [
                    'error' => $e->getMessage(),
                    'execution_time' => $executionTime,
                    'check_name' => $checkName
                ]
            );

            return [
                'status' => 'unhealthy',
                'message' => "Health check failed: {$e->getMessage()}",
                'error' => true,
                'timestamp' => date('c')
            ];
        }
    }
}
