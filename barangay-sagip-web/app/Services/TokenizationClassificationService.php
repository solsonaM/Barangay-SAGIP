<?php

namespace App\Services;

use App\Models\EmergencyRequest;
use App\Models\TokenizationClassificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the Barangay SAGIP tokenization microservice.
 * Calls are authenticated with a shared service key and fail soft so that
 * unavailable classification never blocks resident request submission.
 */
class TokenizationClassificationService
{
    protected string $baseUrl;
    protected int $timeoutSeconds;
    protected ?string $serviceKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.tokenization_service.base_url'), '/');
        $this->timeoutSeconds = (int) config('services.tokenization_service.timeout', 5);
        $this->serviceKey = config('services.tokenization_service.service_key');
    }

    public function classifyAndApply(EmergencyRequest $request): EmergencyRequest
    {
        $result = $this->call('/classify/full', ['text' => $request->description], $request->id);

        if ($result === null) {
            $request->needs_review = true;
            $request->review_reason = 'Tokenization service unavailable — routed to manual review.';
            return $request;
        }

        $request->category = $result['category']['label'] ?? null;
        $request->category_confidence = $result['category']['confidence'] ?? null;
        $request->urgency = $result['urgency']['label'] ?? null;
        $request->urgency_confidence = $result['urgency']['confidence'] ?? null;
        $request->needs_review = $result['needs_review'] ?? false;
        $request->review_reason = $result['review_reason'] ?? null;

        return $request;
    }

    public function assignResponse(EmergencyRequest $request, array $candidates): ?array
    {
        return $this->call('/assign/response', [
            'request_category' => $request->category,
            'request_urgency' => $request->urgency?->value ?? $request->urgency,
            'request_latitude' => (float) $request->latitude,
            'request_longitude' => (float) $request->longitude,
            'candidates' => $candidates,
        ], $request->id);
    }

    protected function call(string $endpoint, array $payload, ?int $emergencyRequestId): ?array
    {
        $start = microtime(true);
        $success = true;
        $responseBody = null;

        try {
            if (blank($this->serviceKey)) {
                throw new \RuntimeException('TOKENIZATION_SERVICE_KEY is not configured.');
            }

            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->withHeaders(['X-Service-Key' => $this->serviceKey])
                ->post($this->baseUrl . $endpoint, $payload);

            if ($response->failed()) {
                $success = false;
                Log::warning("Tokenization service call failed [{$endpoint}]", [
                    'status' => $response->status(),
                ]);
            } else {
                $responseBody = $response->json();

                if (! is_array($responseBody)) {
                    $success = false;
                    $responseBody = null;
                    Log::warning("Tokenization service returned invalid JSON [{$endpoint}]");
                }
            }
        } catch (\Throwable $e) {
            $success = false;
            Log::error("Tokenization service call exception [{$endpoint}]: " . $e->getMessage());
        }

        $elapsedMs = (int) round((microtime(true) - $start) * 1000);

        if ($emergencyRequestId) {
            TokenizationClassificationLog::create([
                'emergency_request_id' => $emergencyRequestId,
                'endpoint' => $endpoint,
                'request_payload' => $payload,
                'response_payload' => $responseBody,
                'response_time_ms' => $elapsedMs,
                'was_successful' => $success,
            ]);
        }

        return $success ? $responseBody : null;
    }
}
