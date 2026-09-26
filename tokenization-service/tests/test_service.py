import os
import unittest
from unittest.mock import patch

from fastapi import HTTPException

from main import (
    ASSIGNMENT_MIN_MARGIN,
    ASSIGNMENT_MIN_SCORE,
    CLASSIFICATION_CONFIDENCE_THRESHOLD,
    FullClassifyIn,
    AssignRequestIn,
    Personnel,
    _classification_review_reason,
    _haversine_km,
    _read_unit_interval_env,
    assign_response,
    classify_full,
    require_service_key,
)
from tokenizer_classifier import classify, classify_category, classify_urgency


class TokenizationServiceTest(unittest.TestCase):
    def test_clear_medical_text_is_classified_without_review(self):
        result = classify_full(
            FullClassifyIn(text="Hindi humihinga ang pasyente at kailangan ng ambulansya ngayon")
        )
        self.assertEqual(result.category.label, "medical")
        self.assertEqual(result.urgency.label, "critical")
        self.assertFalse(result.needs_review)
        self.assertEqual(result.category.confidence, 1.0)
        self.assertEqual(result.urgency.confidence, 1.0)

    def test_clear_fire_text_is_classified_as_fire(self):
        label, confidence, _ = classify_category("May sunog at malaki ang apoy")
        self.assertEqual(label, "fire")
        self.assertEqual(confidence, 1.0)

    def test_mixed_language_input_is_supported(self):
        label, confidence, _ = classify_category("May sunog ha balay, need help please")
        self.assertEqual(label, "fire")
        self.assertEqual(confidence, 1.0)

    def test_unknown_text_is_flagged_for_review(self):
        result = classify_full(FullClassifyIn(text="Please help me with something unusual"))
        self.assertTrue(result.needs_review)
        self.assertIn("below the configured threshold", result.review_reason.lower())

    def test_empty_text_falls_back_and_is_low_confidence(self):
        label, confidence, scores = classify_category("")
        self.assertEqual(label, "general_assistance")
        self.assertEqual(confidence, 0.0)
        self.assertTrue(all(score == 0.0 for score in scores.values()))

    def test_keyword_collision_is_flagged_as_ambiguous_when_rules_overlap(self):
        collision_rules = {
            "medical": ["paso"],
            "fire": ["paso"],
        }
        label, confidence, scores = classify(
            "May paso",
            collision_rules,
            fallback_label="medical",
        )
        self.assertIn(label, {"medical", "fire"})
        self.assertEqual(confidence, 0.5)
        self.assertEqual(scores["medical"], 0.5)
        self.assertEqual(scores["fire"], 0.5)

        reason = _classification_review_reason(confidence, scores)
        self.assertIn("ambiguous top match", reason.lower())

    def test_tie_detection_helper_reports_ambiguity(self):
        reason = _classification_review_reason(
            0.5,
            {"medical": 0.5, "fire": 0.5, "peace_order": 0.0},
        )
        self.assertIn("ambiguous top match", reason.lower())

    def test_urgency_classifier_returns_valid_label_and_score(self):
        label, confidence, _ = classify_urgency(
            "Hindi humihinga at kailangan ng ambulansya ngayon"
        )
        self.assertEqual(label, "critical")
        self.assertGreaterEqual(confidence, 0)
        self.assertLessEqual(confidence, 1)

    def test_haversine_distance_is_zero_for_same_point(self):
        self.assertAlmostEqual(
            _haversine_km(13.5925, 124.2049, 13.5925, 124.2049),
            0.0,
            places=6,
        )

    def test_assignment_ignores_unavailable_personnel(self):
        payload = AssignRequestIn(
            request_category="medical",
            request_urgency="critical",
            request_latitude=13.5925,
            request_longitude=124.2049,
            candidates=[
                Personnel(
                    id=1,
                    name="Unavailable",
                    specialization="medical",
                    latitude=13.5925,
                    longitude=124.2049,
                    is_available=False,
                    current_workload=0,
                ),
                Personnel(
                    id=2,
                    name="Available",
                    specialization="medical",
                    latitude=13.5925,
                    longitude=124.2049,
                    is_available=True,
                    current_workload=0,
                ),
            ],
        )
        result = assign_response(payload)
        self.assertEqual(result.recommended_personnel_id, 2)
        self.assertFalse(result.needs_review)
        self.assertEqual(len(result.ranking), 1)

    def test_assignment_low_score_is_reviewed(self):
        payload = AssignRequestIn(
            request_category="medical",
            request_urgency="low",
            request_latitude=0.0,
            request_longitude=0.0,
            candidates=[
                Personnel(
                    id=1,
                    name="Far and Busy",
                    specialization="fire",
                    latitude=80.0,
                    longitude=80.0,
                    is_available=True,
                    current_workload=20,
                )
            ],
        )
        result = assign_response(payload)
        self.assertTrue(result.needs_review)
        self.assertIsNone(result.recommended_personnel_id)
        self.assertIn("below the configured minimum", result.review_reason.lower())
        self.assertEqual(result.ranking[0].score < ASSIGNMENT_MIN_SCORE, True)

    def test_assignment_small_margin_is_reviewed(self):
        candidate = Personnel(
            id=1,
            name="Responder One",
            specialization="medical",
            latitude=13.5925,
            longitude=124.2049,
            is_available=True,
            current_workload=0,
        )
        duplicate = candidate.model_copy(update={"id": 2, "name": "Responder Two"})
        payload = AssignRequestIn(
            request_category="medical",
            request_urgency="critical",
            request_latitude=13.5925,
            request_longitude=124.2049,
            candidates=[candidate, duplicate],
        )
        result = assign_response(payload)
        self.assertTrue(result.needs_review)
        self.assertIsNone(result.recommended_personnel_id)
        self.assertIn("top-two score margin", result.review_reason.lower())
        self.assertEqual(result.ranking[0].score, result.ranking[1].score)
        self.assertLess(ASSIGNMENT_MIN_MARGIN, 1.0)

    def test_assignment_prefers_specialization_when_other_factors_are_close(self):
        payload = AssignRequestIn(
            request_category="medical",
            request_urgency="critical",
            request_latitude=13.5925,
            request_longitude=124.2049,
            candidates=[
                Personnel(
                    id=1,
                    name="Medical",
                    specialization="medical",
                    latitude=13.60,
                    longitude=124.21,
                    is_available=True,
                    current_workload=0,
                ),
                Personnel(
                    id=2,
                    name="Fire",
                    specialization="fire",
                    latitude=13.59,
                    longitude=124.20,
                    is_available=True,
                    current_workload=0,
                ),
            ],
        )
        result = assign_response(payload)
        self.assertEqual(result.recommended_personnel_id, 1)
        self.assertGreater(result.ranking[0].score, result.ranking[1].score)

    def test_threshold_configuration_is_env_driven(self):
        with patch.dict(os.environ, {"TEST_TOKENIZATION_THRESHOLD": "0.62"}, clear=False):
            self.assertEqual(
                _read_unit_interval_env("TEST_TOKENIZATION_THRESHOLD", 0.45),
                0.62,
            )

    def test_threshold_configuration_rejects_invalid_values(self):
        with patch.dict(os.environ, {"TEST_TOKENIZATION_THRESHOLD": "2"}, clear=False):
            with self.assertRaises(RuntimeError):
                _read_unit_interval_env("TEST_TOKENIZATION_THRESHOLD", 0.45)

    def test_threshold_defaults_are_explicit(self):
        self.assertEqual(CLASSIFICATION_CONFIDENCE_THRESHOLD, 0.45)
        self.assertEqual(ASSIGNMENT_MIN_SCORE, 0.35)
        self.assertEqual(ASSIGNMENT_MIN_MARGIN, 0.05)

    def test_service_key_rejects_missing_key(self):
        with patch.dict(os.environ, {}, clear=True):
            with self.assertRaises(HTTPException) as context:
                require_service_key(None)
        self.assertEqual(context.exception.status_code, 503)

    def test_service_key_rejects_wrong_key(self):
        with patch.dict(os.environ, {"TOKENIZATION_SERVICE_KEY": "correct-key"}, clear=True):
            with self.assertRaises(HTTPException) as context:
                require_service_key("wrong-key")
        self.assertEqual(context.exception.status_code, 401)

    def test_service_key_accepts_correct_key(self):
        with patch.dict(os.environ, {"TOKENIZATION_SERVICE_KEY": "correct-key"}, clear=True):
            self.assertIsNone(require_service_key("correct-key"))


if __name__ == "__main__":
    unittest.main()
