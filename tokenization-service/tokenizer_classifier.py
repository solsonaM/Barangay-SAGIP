"""
Barangay SAGIP — Tokenization-Based Request Classifier
========================================================

Per project direction, this replaces the earlier scikit-learn (TF-IDF +
Calibrated LinearSVC) classifiers with a pure tokenization / keyword-matching
approach. No model is trained and no model file is loaded — classification
is done entirely by matching tokens/phrases from the incoming report text
against hand-curated keyword dictionaries for each category and urgency
level, then scoring by match count.

This is a deterministic, fully-transparent, and fully-auditable approach:
every classification can be explained by pointing at exactly which phrases
in the resident's text matched which category/urgency. There is nothing to
train, no accuracy/precision/recall to report from a held-out split, and no
dataset-generation step is needed anymore.

How it works
------------
1. Normalize the input text (lowercase, strip punctuation into token
   boundaries).
2. For each category (or urgency level), count how many of its keyword
   phrases appear as substrings of the normalized text.
3. The category/urgency with the most matches wins. "Confidence" here is
   not a statistical probability — it's the winning label's share of total
   matches across all labels, which is a reasonable, explainable proxy but
   should be described as such (not as calibrated model confidence) in any
   documentation or thesis write-up.
4. If nothing matches at all, fall back to the lowest-commitment label
   (general_assistance / average) and flag needs_review, same as before.
"""
import re
from typing import Dict, List, Tuple

# ---------------------------------------------------------------------------
# Keyword dictionaries
# ---------------------------------------------------------------------------
# Phrases (not just single words) are matched as substrings against the
# normalized text, since multi-word Filipino/Bikol phrases carry meaning
# that single tokens often don't (e.g. "hindi humihinga" vs "hindi").

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
    """Lowercase and collapse whitespace/punctuation for substring matching."""
    text = text.lower()
    text = re.sub(r"[^\w\s\-]", " ", text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def _score(text: str, keyword_dict: Dict[str, List[str]]) -> Dict[str, int]:
    """
    Count keyword-phrase matches per label, using word-boundary matching
    (not raw substring search) so a short keyword like "ulan" (rain) can't
    falsely match inside an unrelated word like "ambulansya".
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


def classify(text: str, keyword_dict: Dict[str, List[str]], fallback_label: str) -> Tuple[str, float, Dict[str, float]]:
    """
    Returns (winning_label, confidence, all_scores) where confidence is the
    winning label's share of total matches (not a statistical probability).
    Falls back to `fallback_label` with confidence 0.0 when nothing matches
    at all, which the caller uses to trigger the Feature 5 review flag.
    """
    normalized = _normalize(text)
    raw_scores = _score(normalized, keyword_dict)
    total_matches = sum(raw_scores.values())

    if total_matches == 0:
        all_scores = {label: 0.0 for label in keyword_dict}
        return fallback_label, 0.0, all_scores

    all_scores = {label: round(count / total_matches, 4) for label, count in raw_scores.items()}
    winning_label = max(all_scores, key=all_scores.get)
    return winning_label, all_scores[winning_label], all_scores


def classify_category(text: str) -> Tuple[str, float, Dict[str, float]]:
    return classify(text, CATEGORY_KEYWORDS, fallback_label="general_assistance")


def classify_urgency(text: str) -> Tuple[str, float, Dict[str, float]]:
    return classify(text, URGENCY_KEYWORDS, fallback_label="average")
