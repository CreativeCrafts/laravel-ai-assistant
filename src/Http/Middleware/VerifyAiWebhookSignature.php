<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyAiWebhookSignature
{
    public function handle(Request $request, Closure $next): Response
    {
        $secretVal = config('ai-assistant.webhooks.signing_secret');
        $secret = is_string($secretVal) ? $secretVal : '';

        $headerVal = config('ai-assistant.webhooks.signature_header', 'X-AI-Signature');
        $header = is_string($headerVal) ? $headerVal : 'X-AI-Signature';

        if ($secret === '') {
            return response('Webhook signing not configured', 400);
        }

        // Use Symfony HeaderBag, which returns string|null (better for static analysis)
        $signatureVal = $request->headers->get($header);
        $signature = is_string($signatureVal) ? $signatureVal : '';

        if ($signature === '') {
            return response('Missing signature', 400);
        }

        $payload = (string)$request->getContent();
        $computed = hash_hmac('sha256', $payload, $secret);

        // Timing-safe comparison
        if (!hash_equals($computed, $signature)) {
            return response('Invalid signature', 401);
        }

        return $next($request);
    }
}
