<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Http\Controllers;

use CreativeCrafts\LaravelAiAssistant\Services\HealthCheckService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

/**
 * Health Check Controller for AI Assistant monitoring endpoints.
 *
 * Provides HTTP endpoints for system health monitoring that can be used
 * by load balancers, monitoring services, and orchestration systems.
 */
class HealthCheckController extends Controller
{
    public function __construct(
        private readonly HealthCheckService $healthCheckService
    ) {
    }

    /**
     * Basic health check endpoint for simple monitoring.
     *
     * Returns a minimal health status suitable for load balancers
     * and basic monitoring systems that just need to know if the
     * system is responsive.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function basic(Request $request): JsonResponse
    {
        try {
            $status = $this->healthCheckService->getHealthStatus();

            $isHealthy = $status['status'] === 'healthy';
            $httpCode = $isHealthy ? 200 : 503;

            return response()->json([
                'status' => $status['status'],
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant'
            ], $httpCode);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Health check failed',
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant'
            ], 503);
        }
    }

    /**
     * Detailed health check endpoint for comprehensive monitoring.
     *
     * Returns detailed health information including component status,
     * system metrics, and diagnostic information suitable for detailed
     * monitoring and alerting systems.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detailed(Request $request): JsonResponse
    {
        try {
            $healthCheck = $this->healthCheckService->performHealthCheck();

            $isHealthy = $healthCheck['status'] === 'healthy';
            $httpCode = $isHealthy ? 200 : 503;

            // Structure the response for monitoring systems
            $response = [
                'status' => $healthCheck['status'],
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant',
                'version' => $healthCheck['version'] ?? 'unknown',
                'uptime' => $healthCheck['uptime'] ?? null,
                'checks' => [],
                'summary' => $healthCheck['summary'] ?? []
            ];

            // Format individual check results
            foreach ($healthCheck['checks'] ?? [] as $checkName => $checkResult) {
                if (is_array($checkResult)) {
                    $response['checks'][$checkName] = [
                        'status' => $checkResult['status'] ?? 'unknown',
                        'message' => $checkResult['message'] ?? '',
                        'response_time_ms' => $checkResult['response_time'] ?? null,
                        'details' => $checkResult['details'] ?? []
                    ];
                }
            }

            return response()->json($response, $httpCode);

        } catch (Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Detailed health check failed: ' . $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant',
                'checks' => [],
                'summary' => []
            ], 503);
        }
    }

    /**
     * Readiness probe endpoint for Kubernetes-style orchestration.
     *
     * Checks if the service is ready to handle requests. This typically
     * includes checking dependencies like databases, caches, and external APIs.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function ready(Request $request): JsonResponse
    {
        try {
            $healthCheck = $this->healthCheckService->performHealthCheck();

            // For readiness, we're stricter about what constitutes "ready"
            $criticalChecks = ['configuration', 'api_connectivity', 'cache'];
            $isReady = true;
            $failedChecks = [];

            foreach ($criticalChecks as $checkName) {
                $checkResult = $healthCheck['checks'][$checkName] ?? null;
                if (!$checkResult || ($checkResult['status'] ?? '') !== 'healthy') {
                    $isReady = false;
                    $failedChecks[] = $checkName;
                }
            }

            $httpCode = $isReady ? 200 : 503;

            return response()->json([
                'ready' => $isReady,
                'status' => $isReady ? 'ready' : 'not_ready',
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant',
                'failed_checks' => $failedChecks,
                'critical_checks' => $criticalChecks
            ], $httpCode);

        } catch (Exception $e) {
            return response()->json([
                'ready' => false,
                'status' => 'error',
                'message' => 'Readiness check failed: ' . $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant',
                'failed_checks' => ['system'],
                'critical_checks' => $criticalChecks ?? []
            ], 503);
        }
    }

    /**
     * Liveness probe endpoint for Kubernetes-style orchestration.
     *
     * Simple check to verify the service is alive and responding.
     * This should only fail if the service needs to be restarted.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function live(Request $request): JsonResponse
    {
        // For liveness, we only check if the service can respond
        // and basic dependencies are accessible
        try {
            // Basic service availability check
            $startTime = microtime(true);

            // Test basic functionality
            $basicStatus = $this->healthCheckService->getHealthStatus();
            $isAlive = ($basicStatus['status'] ?? '') === 'healthy';

            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            $httpCode = $isAlive ? 200 : 503;

            return response()->json([
                'alive' => $isAlive,
                'status' => $isAlive ? 'alive' : 'not_alive',
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant',
                'response_time_ms' => $responseTime
            ], $httpCode);

        } catch (Exception $e) {
            return response()->json([
                'alive' => false,
                'status' => 'error',
                'message' => 'Liveness check failed: ' . $e->getMessage(),
                'timestamp' => now()->toISOString(),
                'service' => 'laravel-ai-assistant',
                'response_time_ms' => null
            ], 503);
        }
    }
}
