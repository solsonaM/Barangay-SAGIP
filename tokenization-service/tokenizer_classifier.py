"""
Barangay SAGIP — Rule-Based Tokenization Classifier
===================================================

The project intentionally uses a deterministic tokenization and keyword/
phrase-matching architecture. Incoming text is normalized, then matched
against hand-curated dictionaries for request categories and urgency levels.

There is no training pipeline, learned artifact, or statistical inference.
Every classification can be audited by inspecting the matched rules.
"""

import re
from typing import Dict, List, Tuple

CATEGORY_KEYWORDS: Dict[str, List[str]] = {
    "medical": [
        "gamot", "sugat", "dumudugo", "lagnat", "sumasakit", "buntis",
        "ospital", "ambulansya", "humihinga", "unconscious", "seizure",
        "first aid", "bakuna", "dental", "senior citizen", "tiyan",
        "sumusuka", "nahihilo", "nanghihina", "nadapa", "nabali", "binti",
        "chest pain", "nalunod",
    ],
    "fire": [
        "sunog", "apoy", "usok", "umuusok", "liyab", "paso", "gasul",
        "bumbero", "fire safety", "fire extinguisher", "nasusunog",
        "sumasabog",
    ],
    "peace_order": [
        "baril", "kutsilyo", "holdaper", "away", "lasing", "nanakot",
        "nagnanakaw", "blotter", "kahina-hinalang", "sumisigaw", "patalim",
        "saktan", "nag-aaway", "nag-iingay",
    ],
    "disaster": [
        "baha", "bumabaha", "lindol", "gumuho", "landslide", "bagyo",
        "ulan", "hangin", "puno", "poste", "kanal", "typhoon",
        "evacuation", "tumataas ang tubig", "creek",
    ],
    "general_assistance": [
        "burial assistance", "relief goods", "ayuda", "trabaho",
        "clearance", "indigency", "business permit", "barangay hall",
        "byahe", "requirements", "certificate", "permit",
    ],
}

URGENCY_KEYWORDS: Dict[str, List[str]] = {
    "critical": [
        "hindi humihinga", "hindi na humihinga", "unconscious", "seizure",
        "tinangay", "nakulong", "may dalang baril", "may hawak na patalim",
        "sumisigaw ng tulong", "mamamatay", "agad", "ngayon din",
        "malaki na ang apoy", "kumakalat na ang apoy", "nanganganib mabuhay",
    ],
    "high": [
        "lagnat na mataas", "sumasakit ng husto", "hirap huminga",
        "sugat na malalim", "may usok", "kahina-hinalang",
        "tumataas nang mabilis", "bumagsak na malaking puno",
        "nag-iingay at lasing", "nadapa",
    ],
    "average": [
        "pwede po ba", "need po sana", "may naaamoy", "may nagnanakaw",
        "may sirang", "may baradong", "pwede po humingi", "sugat lang",
    ],
    "low": [
        "tanong ko lang", "tanong lang po", "kailan po", "meron po ba",
        "paano po", "anong oras", "saan po", "ano po ang",
    ],
}


def _normalize(text: str) -> str:
    """Lowercase and collapse whitespace/punctuation into token boundaries."""
    text = text.lower()
    text = re.sub(r"[^\w\s\-]", " ", text)
    return re.sub(r"\s+", " ", text).strip()


def _score(text: str, keyword_dict: Dict[str, List[str]]) -> Dict[str, int]:
    """
    Count matching rules per label. Word boundaries prevent short keywords
    from matching inside unrelated words (for example, "ulan" inside a
    longer unrelated token).
    """
    scores = {}
    for label, phrases in keyword_dict.items():
        count = 0
        for phrase in phrases:
            pattern = r"\b" + re.escape(phrase).replace(r"\ ", r"\s+") + r"\b"
            if re.search(pattern, text):
                count += 1
        scores[label] = count
    return scores


def classify(
    text: str,
    keyword_dict: Dict[str, List[str]],
    fallback_label: str,
) -> Tuple[str, float, Dict[str, float]]:
    """
    Return (winning_label, confidence, all_scores).

    Confidence is the winning label's share of all matched rules, not a
    statistical probability. When no rule matches, the fallback label is
    returned with 0.0 confidence; the API layer converts that into review.

    If multiple labels tie for the highest match count, the first declared
    label remains the deterministic display value, but the API layer flags
    the result for human review.
    """
    normalized = _normalize(text)
    raw_scores = _score(normalized, keyword_dict)
    total_matches = sum(raw_scores.values())

    if total_matches == 0:
        all_scores = {label: 0.0 for label in keyword_dict}
        return fallback_label, 0.0, all_scores

    all_scores = {
        label: round(count / total_matches, 4)
        for label, count in raw_scores.items()
    }
    winning_label = max(all_scores, key=all_scores.get)
    return winning_label, all_scores[winning_label], all_scores


def classify_category(text: str) -> Tuple[str, float, Dict[str, float]]:
    return classify(text, CATEGORY_KEYWORDS, fallback_label="general_assistance")


def classify_urgency(text: str) -> Tuple[str, float, Dict[str, float]]:
    return classify(text, URGENCY_KEYWORDS, fallback_label="average")
