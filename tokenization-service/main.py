"""
Barangay SAGIP — Tokenization Classification Service

The service implements the project's final deterministic classification
architecture. It normalizes request text, matches hand-curated keywords and
phrases, calculates transparent match-share scores for request type and
urgency, and ranks response personnel with an explicit weighted scoring rule.

No statistical training pipeline, learned artifact, or model inference is
part of this service.
"""
import hmac
import math
import os
from typing import List, Optional

from fastapi import Depends, FastAPI, Header, HTTPException
from pydantic import BaseModel, Field

from tokenizer_classifier import classify_category, classify_urgency

app = FastAPI(
    title="Barangay SAGIP Tokenization Service",
    description="Deterministic keyword-tokenization request classification, urgency scoring, and response-assignment scoring.",
    version="2.2.0",
)


class ClassifyTextIn(BaseModel):
    text: str = Field(..., min_length=1, description="Free-text request description")


class ClassificationOut(BaseModel):
    label: str
    confidence: float
    all_scores: dict
    needs_review: bool = False
    review_reason: Optional[str] = None


class Personnel(BaseModel):
    id: int
    name: str
    specialization: str
    latitude: float
    longitude: float
    is_available: bool = True
    current_workload: int = 0


class AssignRequestIn(BaseModel):
    request_category: str
    request_urgency: str
    request_latitude: float
    request_longitude: float
    candidates: List[Personnel]


class RankedCandidate(BaseModel):
    personnel_id: int
    name: str
    score: float
    distance_km: float
    specialization_match: bool


class AssignResponseOut(BaseModel):
    recommended_personnel_id: Optional[int]
    ranking: List[RankedCandidate]
    needs_review: bool = False
    review_reason: Optional[str] = None


class FullClassifyIn(BaseModel):
    text: str = Field(..., min_length=1)


class FullClassifyOut(BaseModel):
    category: ClassificationOut
    urgency: ClassificationOut
    needs_review: bool
    review_reason: Optional[str] = None


def _read_unit_interval_env(name: str, default: float) -> float:
    raw = os.getenv(name, str(default))
    try:
        value = float(raw)
    except ValueError as exc:
        raise RuntimeError(f"{name} must be a number between 0 and 1.") from exc

    if not 0 <= value <= 1:
        raise RuntimeError(f"{name} must be a number between 0 and 1.")

    return value


# Shared label-confidence threshold for request type and urgency.
CLASSIFICATION_CONFIDENCE_THRESHOLD = _read_unit_interval_env(
    "TOKENIZATION_CONFIDENCE_THRESHOLD", 0.45
)

# Explicit review guardrails for response-assignment recommendations.
ASSIGNMENT_MIN_SCORE = _read_unit_interval_env(
    "TOKENIZATION_ASSIGNMENT_MIN_SCORE", 0.35
)
ASSIGNMENT_MIN_MARGIN = _read_unit_interval_env(
    "TOKENIZATION_ASSIGNMENT_MIN_MARGIN", 0.05
)

# Backwards-compatible name for any external test/import that used the old constant.
LOW_CONFIDENCE_THRESHOLD = CLASSIFICATION_CONFIDENCE_THRESHOLD

RULESET_VERSION = os.getenv("TOKENIZATION_RULESET_VERSION", "1.0.0")

URGENCY_WEIGHT = {"critical": 1.0, "high": 0.75, "average": 0.5, "low": 0.25}


def require_service_key(x_service_key: Optional[str] = Header(default=None)) -> None:
    """Authenticate Laravel-to-FastAPI service-to-service calls."""
    expected = os.getenv("TOKENIZATION_SERVICE_KEY")
    if not expected:
        raise HTTPException(status_code=503, detail="Service authentication is not configured")
    if not x_service_key or not hmac.compare_digest(x_service_key, expected):
        raise HTTPException(status_code=401, detail="Unauthorized")


def _haversine_km(lat1, lon1, lat2, lon2) -> float:
    radius_km = 6371.0
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    a = (
        math.sin(dphi / 2) ** 2
        + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2) ** 2
    )
    return radius_km * 2 * math.asin(math.sqrt(a))


def _classification_review_reason(confidence: float, all_scores: dict) -> Optional[str]:
    reasons = []

    if confidence < CLASSIFICATION_CONFIDENCE_THRESHOLD:
        reasons.append(
            f"confidence {confidence:.4f} is below the configured threshold "
            f"{CLASSIFICATION_CONFIDENCE_THRESHOLD:.2f}"
        )

    positive_scores = {
        label: score for label, score in all_scores.items() if score > 0
    }
    if positive_scores:
        top_score = max(positive_scores.values())
        tied_labels = [
            label for label, score in positive_scores.items() if score == top_score
        ]
        if len(tied_labels) > 1:
            reasons.append(
                "ambiguous top match: " + ", ".join(tied_labels)
            )

    if not reasons:
        return None

    return "; ".join(reasons).capitalize() + "."


def _build_classification_output(label: str, confidence: float, scores: dict) -> ClassificationOut:
    reason = _classification_review_reason(confidence, scores)
    return ClassificationOut(
        label=label,
        confidence=confidence,
        all_scores=scores,
        needs_review=reason is not None,
        review_reason=reason,
    )


def _score_candidate(payload: AssignRequestIn, candidate: Personnel) -> RankedCandidate:
    urgency_weight = URGENCY_WEIGHT.get(payload.request_urgency, 0.5)
    distance_km = _haversine_km(
        payload.request_latitude,
        payload.request_longitude,
        candidate.latitude,
        candidate.longitude,
    )
    specialization_match = candidate.specialization == payload.request_category
    proximity_score = 1 / (1 + distance_km)
    specialization_score = 1.0 if specialization_match else 0.3
    workload_score = 1 / (1 + candidate.current_workload)
    score = (
        0.4 * proximity_score
        + 0.35 * specialization_score
        + 0.25 * workload_score
    ) * (0.5 + 0.5 * urgency_weight)

    return RankedCandidate(
        personnel_id=candidate.id,
        name=candidate.name,
        score=round(float(score), 4),
        distance_km=round(distance_km, 2),
        specialization_match=specialization_match,
    )


def _rank_candidates(payload: AssignRequestIn) -> List[RankedCandidate]:
    ranked = [
        _score_candidate(payload, candidate)
        for candidate in payload.candidates
        if candidate.is_available
    ]
    ranked.sort(key=lambda item: (-item.score, item.personnel_id))
    return ranked


def _assignment_review_reason(ranking: List[RankedCandidate]) -> Optional[str]:
    if not ranking:
        return "No available response personnel were supplied for assignment scoring."

    top = ranking[0]
    reasons = []

    if top.score < ASSIGNMENT_MIN_SCORE:
        reasons.append(
            f"top assignment score {top.score:.4f} is below the configured "
            f"minimum {ASSIGNMENT_MIN_SCORE:.2f}"
        )

    if len(ranking) >= 2:
        margin = top.score - ranking[1].score
        if margin < ASSIGNMENT_MIN_MARGIN:
            reasons.append(
                f"top-two score margin {margin:.4f} is below the configured "
                f"minimum {ASSIGNMENT_MIN_MARGIN:.2f}"
            )

    if not reasons:
        return None

    return "; ".join(reasons).capitalize() + "."


@app.get("/health")
def health():
    return {
        "status": "ok",
        "classifier": "tokenization (keyword-matching)",
        "ruleset_version": RULESET_VERSION,
        "classification_confidence_threshold": CLASSIFICATION_CONFIDENCE_THRESHOLD,
        "assignment_min_score": ASSIGNMENT_MIN_SCORE,
        "assignment_min_margin": ASSIGNMENT_MIN_MARGIN,
    }


@app.post(
    "/classify/request-type",
    response_model=ClassificationOut,
    dependencies=[Depends(require_service_key)],
)
def classify_request_type(payload: ClassifyTextIn):
    label, confidence, scores = classify_category(payload.text)
    return _build_classification_output(label, confidence, scores)


@app.post(
    "/classify/urgency",
    response_model=ClassificationOut,
    dependencies=[Depends(require_service_key)],
)
def classify_urgency_endpoint(payload: ClassifyTextIn):
    label, confidence, scores = classify_urgency(payload.text)
    return _build_classification_output(label, confidence, scores)


@app.post(
    "/classify/full",
    response_model=FullClassifyOut,
    dependencies=[Depends(require_service_key)],
)
def classify_full(payload: FullClassifyIn):
    category = _build_classification_output(*classify_category(payload.text))
    urgency = _build_classification_output(*classify_urgency(payload.text))

    reasons = []
    if category.needs_review:
        reasons.append(f"Request type: {category.review_reason}")
    if urgency.needs_review:
        reasons.append(f"Urgency: {urgency.review_reason}")

    needs_review = bool(reasons)

    return FullClassifyOut(
        category=category,
        urgency=urgency,
        needs_review=needs_review,
        review_reason=" ".join(reasons) if reasons else None,
    )


@app.post(
    "/assign/response",
    response_model=AssignResponseOut,
    dependencies=[Depends(require_service_key)],
)
def assign_response(payload: AssignRequestIn):
    ranked = _rank_candidates(payload)
    review_reason = _assignment_review_reason(ranked)

    return AssignResponseOut(
        recommended_personnel_id=None if review_reason else ranked[0].personnel_id,
        ranking=ranked,
        needs_review=review_reason is not None,
        review_reason=review_reason,
    )
