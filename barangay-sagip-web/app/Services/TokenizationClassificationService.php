<?php

namespace App\Services;

use App\Models\EmergencyRequest;
use App\Models\TokenizationClassificationLog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Thin HTTP client for the Barangay SAGIP tokenization microservice
 * (FastAPI + keyword-phrase matching — see
 * tokenization-service/tokenizer_classifier.py), which implements:
 *   - Feature 3: Request Classification (tokenization / keyword matching)
 *   - Feature 4: Urgency / Priority Classification (tokenization / keyword matching)
 *   - Feature 6: Response Assignment Classification (see ResponseAssignmentService)
 *
 * Every call is logged to tokenization_classification_logs for auditability,
 * and every call fails soft: if the tokenization service is unreachable,
 * the request is simply routed to human review (Feature 5) instead of the
 * request submission failing outright.
 */
class TokenizationClassificationService
{
    protected string $baseUrl;
    protected int $timeoutSeconds;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('services.tokenization_service.base_url'), '/');
        $this->timeoutSeconds = (int) config('services.tokenization_service.timeout', 5);
    }

    /**
     * Classifies request type + urgency in a single call and stores the
     * results directly onto the EmergencyRequest model (does not save it —
     * the caller decides when to persist).
     */
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

    /**
     * Calls the response-assignment endpoint with a request and a list of
     * candidate personnel arrays. Returns the decoded response or null on
     * failure (caller should fall back to manual assignment).
     */
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

    /**
     * Shared HTTP call + logging + error handling.
     */
    protected function call(string $endpoint, array $payload, ?int $emergencyRequestId): ?array
    {
        $start = microtime(true);
        $success = true;
        $responseBody = null;

        try {
            $response = Http::timeout($this->timeoutSeconds)
                ->acceptJson()
                ->post($this->baseUrl . $endpoint, $payload);

            if ($response->failed()) {
                $success = false;
                Log::warning("Tokenization service call failed [{$endpoint}]", [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
            } else {
                $responseBody = $response->json();
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
