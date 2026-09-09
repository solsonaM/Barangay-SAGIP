"""
Barangay SAGIP — ML Microservice

Serves the three ML-driven features from the proposal (Section 9):
  - Feature 3: ML-Based Request Classification   -> POST /classify/request-type
  - Feature 4: Urgency / Priority Classification  -> POST /classify/urgency
  - Feature 6: Response Assignment Classification -> POST /assign/response
  - Convenience combined endpoint                 -> POST /classify/full
  - Health check                                  -> GET  /health

Called by the Laravel app's App\Services\MLClassificationService over HTTP.
Run with:  uvicorn main:app --host 0.0.0.0 --port 8001
"""
import math
from typing import List, Optional

import joblib
from fastapi import FastAPI, HTTPException
from pydantic import BaseModel, Field

app = FastAPI(
    title="Barangay SAGIP ML Service",
    description="Request classification, urgency scoring, and response assignment for Barangay SAGIP.",
    version="1.0.0",
)

try:
    request_type_model = joblib.load("models/request_type_classifier.pkl")
    urgency_model = joblib.load("models/urgency_classifier.pkl")
except FileNotFoundError:
    request_type_model = None
    urgency_model = None


# ---------------------------------------------------------------------------
# Schemas
# ---------------------------------------------------------------------------

class ClassifyTextIn(BaseModel):
    text: str = Field(..., min_length=1, description="Free-text request description")


class ClassificationOut(BaseModel):
    label: str
    confidence: float
    all_scores: dict


class Personnel(BaseModel):
    id: int
    name: str
    specialization: str  # e.g. "medical", "fire", "peace_order", "disaster", "general_assistance"
    latitude: float
    longitude: float
    is_available: bool = True
    current_workload: int = 0  # number of active assignments right now


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
    text: str


class FullClassifyOut(BaseModel):
    category: ClassificationOut
    urgency: ClassificationOut
    needs_review: bool
    review_reason: Optional[str] = None


# ---------------------------------------------------------------------------
# Helpers
# ---------------------------------------------------------------------------

URGENCY_WEIGHT = {"critical": 1.0, "high": 0.75, "average": 0.5, "low": 0.25}

# Confidence below this triggers Feature 5 (Request Validation) to flag the
# report for human review instead of auto-dispatching it.
LOW_CONFIDENCE_THRESHOLD = 0.45


def _classify(model, text: str) -> ClassificationOut:
    if model is None:
        raise HTTPException(status_code=503, detail="Model not loaded. Run train_classifier.py first.")
    proba = model.predict_proba([text])[0]
    classes = model.classes_
    scores = {cls: round(float(p), 4) for cls, p in zip(classes, proba)}
    best_idx = proba.argmax()
    return ClassificationOut(
        label=classes[best_idx],
        confidence=round(float(proba[best_idx]), 4),
        all_scores=scores,
    )


def _haversine_km(lat1, lon1, lat2, lon2) -> float:
    R = 6371.0
    phi1, phi2 = math.radians(lat1), math.radians(lat2)
    dphi = math.radians(lat2 - lat1)
    dlambda = math.radians(lon2 - lon1)
    a = math.sin(dphi / 2) ** 2 + math.cos(phi1) * math.cos(phi2) * math.sin(dlambda / 2) ** 2
    return R * 2 * math.asin(math.sqrt(a))


# ---------------------------------------------------------------------------
# Endpoints
# ---------------------------------------------------------------------------

@app.get("/health")
def health():
    return {
        "status": "ok",
        "models_loaded": request_type_model is not None and urgency_model is not None,
    }


@app.post("/classify/request-type", response_model=ClassificationOut)
def classify_request_type(payload: ClassifyTextIn):
    return _classify(request_type_model, payload.text)


@app.post("/classify/urgency", response_model=ClassificationOut)
def classify_urgency(payload: ClassifyTextIn):
    return _classify(urgency_model, payload.text)


@app.post("/classify/full", response_model=FullClassifyOut)
def classify_full(payload: FullClassifyIn):
    """
    Convenience endpoint: runs both classifiers in one call and also applies
    the Feature 5 (Request Validation) low-confidence review rule, so Laravel
    only needs a single HTTP round trip when a resident submits a request.
    """
    category = _classify(request_type_model, payload.text)
    urgency = _classify(urgency_model, payload.text)

    needs_review = False
    reason = None
    if category.confidence < LOW_CONFIDENCE_THRESHOLD or urgency.confidence < LOW_CONFIDENCE_THRESHOLD:
        needs_review = True
        reason = "Low classification confidence — route to human validation before dispatch."

    return FullClassifyOut(category=category, urgency=urgency, needs_review=needs_review, review_reason=reason)


@app.post("/assign/response", response_model=AssignResponseOut)
def assign_response(payload: AssignRequestIn):
    """
    Feature 6: Response Assignment Classification.

    Implemented as a transparent, weighted scoring/ranking function over the
    available personnel supplied by Laravel (proximity, specialization match,
    urgency, and current workload) rather than a black-box model — this
    matches the "rule-weighted scoring baseline" the proposal names in
    Section 9.3, and keeps the assignment auditable for barangay officials.
    Swap in a learned ranking model later without changing this API shape.
    """
    urgency_weight = URGENCY_WEIGHT.get(payload.request_urgency, 0.5)
    ranked: List[RankedCandidate] = []

    for c in payload.candidates:
        if not c.is_available:
            continue
        distance_km = _haversine_km(
            payload.request_latitude, payload.request_longitude, c.latitude, c.longitude
        )
        specialization_match = c.specialization == payload.request_category

        # Score components (each normalized to roughly 0-1, higher is better):
        proximity_score = 1 / (1 + distance_km)          # closer = higher
        specialization_score = 1.0 if specialization_match else 0.3
        workload_score = 1 / (1 + c.current_workload)     # less busy = higher

        score = (
            0.4 * proximity_score
            + 0.35 * specialization_score
            + 0.25 * workload_score
        ) * (0.5 + 0.5 * urgency_weight)  # urgent requests weight the top score higher

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
