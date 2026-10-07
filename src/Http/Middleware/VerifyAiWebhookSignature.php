<?php

declare(strict_types=1);

namespace CreativeCrafts\LaravelAiAssistant\Http\Middleware;

use Closure;
use CreativeCrafts\LaravelAiAssistant\Support\StandardWebhookSignature;
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

        // OpenAI webhooks: Standard Webhooks signature (webhook-id / webhook-timestamp / webhook-signature)
        $webhookId = (string)$request->headers->get(StandardWebhookSignature::ID_HEADER, '');
        $webhookTimestamp = (string)$request->headers->get(StandardWebhookSignature::TIMESTAMP_HEADER, '');
        $webhookSignature = (string)$request->headers->get(StandardWebhookSignature::SIGNATURE_HEADER, '');
        if ($webhookId !== '' && $webhookTimestamp !== '' && $webhookSignature !== '') {
            $skew = config('ai-assistant.webhooks.max_skew_seconds', 300);
            $tolerance = is_numeric($skew) && (int)$skew > 0 ? (int)$skew : 300;
            if (!StandardWebhookSignature::verify((string)$request->getContent(), $webhookId, $webhookTimestamp, $webhookSignature, $secret, $tolerance)) {
                return response('Invalid signature', 401);
            }

            return $next($request);
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
