"""
Barangay SAGIP — Tokenization Microservice

Serves request classification, urgency classification, response assignment,
and a health endpoint. Request-type and urgency classification use the
repository's transparent tokenization / keyword-phrase matching approach.
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
    description="Keyword-tokenization request classification, urgency scoring, and response assignment for Barangay SAGIP.",
    version="2.1.0",
)


class ClassifyTextIn(BaseModel):
    text: str = Field(..., min_length=1, description="Free-text request description")


class ClassificationOut(BaseModel):
    label: str
    confidence: float
    all_scores: dict


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


class FullClassifyIn(BaseModel):
    text: str = Field(..., min_length=1)


class FullClassifyOut(BaseModel):
    category: ClassificationOut
    urgency: ClassificationOut
    needs_review: bool
    review_reason: Optional[str] = None


URGENCY_WEIGHT = {"critical": 1.0, "high": 0.75, "average": 0.5, "low": 0.25}
LOW_CONFIDENCE_THRESHOLD = 0.45


def require_service_key(x_service_key: Optional[str] = Header(default=None)) -> None:
    """Authenticate Laravel-to-FastAPI service-to-service calls."""
    expected = os.getenv("TOKENIZATION_SERVICE_KEY")
    if not expected:
        raise HTTPException(status_code=503, detail="Service authentication is not configured")
    if not x_service_key or not hmac.compare_digest(x_service_key, expected):
        raise HTTPException(status_code=401, detail="Unauthorized")


def _haversine_km(lat1, lon1, lat2, lon2) -> float:
    R = 6371.0
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    a = math.sin(dphi / 2) ** 2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2) ** 2
    return R * 2 * math.asin(math.sqrt(a))


@app.get("/health")
def health():
    return {"status": "ok", "classifier": "tokenization (keyword-matching)"}


@app.post("/classify/request-type", response_model=ClassificationOut, dependencies=[Depends(require_service_key)])
def classify_request_type(payload: ClassifyTextIn):
    label, confidence, scores = classify_category(payload.text)
    return ClassificationOut(label=label, confidence=confidence, all_scores=scores)


@app.post("/classify/urgency", response_model=ClassificationOut, dependencies=[Depends(require_service_key)])
def classify_urgency_endpoint(payload: ClassifyTextIn):
    label, confidence, scores = classify_urgency(payload.text)
    return ClassificationOut(label=label, confidence=confidence, all_scores=scores)


@app.post("/classify/full", response_model=FullClassifyOut, dependencies=[Depends(require_service_key)])
def classify_full(payload: FullClassifyIn):
    cat_label, cat_confidence, cat_scores = classify_category(payload.text)
    urg_label, urg_confidence, urg_scores = classify_urgency(payload.text)

    category = ClassificationOut(label=cat_label, confidence=cat_confidence, all_scores=cat_scores)
    urgency = ClassificationOut(label=urg_label, confidence=urg_confidence, all_scores=urg_scores)

    needs_review = category.confidence < LOW_CONFIDENCE_THRESHOLD or urgency.confidence < LOW_CONFIDENCE_THRESHOLD
    reason = "No confident keyword match — route to human validation before dispatch." if needs_review else None

    return FullClassifyOut(
        category=category,
        urgency=urgency,
        needs_review=needs_review,
        review_reason=reason,
    )


@app.post("/assign/response", response_model=AssignResponseOut, dependencies=[Depends(require_service_key)])
def assign_response(payload: AssignRequestIn):
    urgency_weight = URGENCY_WEIGHT.get(payload.request_urgency, 0.5)
    ranked: List[RankedCandidate] = []

    for c in payload.candidates:
        if not c.is_available:
            continue
        distance_km = _haversine_km(
            payload.request_latitude, payload.request_longitude, c.latitude, c.longitude
        )
        specialization_match = c.specialization == payload.request_category
        proximity_score = 1 / (1 + distance_km)
        specialization_score = 1.0 if specialization_match else 0.3
        workload_score = 1 / (1 + c.current_workload)
        score = (
            0.4 * proximity_score
            + 0.35 * specialization_score
            + 0.25 * workload_score
        ) * (0.5 + 0.5 * urgency_weight)

        ranked.append(RankedCandidate(
            personnel_id=c.id,
            name=c.name,
            score=round(float(score), 4),
            distance_km=round(distance_km, 2),
            specialization_match=specialization_match,
        ))

    ranked.sort(key=lambda r: r.score, reverse=True)
    recommended = ranked[0].personnel_id if ranked else None
    return AssignResponseOut(recommended_personnel_id=recommended, ranking=ranked)
