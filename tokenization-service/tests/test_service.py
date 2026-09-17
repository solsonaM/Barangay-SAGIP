import os
import unittest
from unittest.mock import patch

from fastapi import HTTPException

from main import LOW_CONFIDENCE_THRESHOLD, _haversine_km, classify_full, require_service_key, FullClassifyIn
from tokenizer_classifier import classify_category, classify_urgency


class TokenizationServiceTest(unittest.TestCase):
    def test_medical_text_is_classified_as_medical(self):
        label, confidence, _ = classify_category("May lagnat at hindi humihinga ang pasyente")
        self.assertEqual(label, "medical")
        self.assertGreater(confidence, 0)

    def test_fire_text_is_classified_as_fire(self):
        label, confidence, _ = classify_category("May sunog at makapal na usok sa bahay")
        self.assertEqual(label, "fire")
        self.assertGreater(confidence, 0)

    def test_unknown_text_is_flagged_for_review(self):
        result = classify_full(FullClassifyIn(text="Please help me with something unusual"))
        self.assertTrue(result.needs_review)
        self.assertTrue(
            result.category.confidence < LOW_CONFIDENCE_THRESHOLD
            or result.urgency.confidence < LOW_CONFIDENCE_THRESHOLD
        )

    def test_urgency_classifier_returns_valid_label(self):
        label, confidence, _ = classify_urgency("Hindi humihinga at kailangan ng ambulansya ngayon")
        self.assertIn(label, {"critical", "high", "average", "low"})
        self.assertGreaterEqual(confidence, 0)
        self.assertLessEqual(confidence, 1)

    def test_haversine_distance_is_zero_for_same_point(self):
        self.assertAlmostEqual(_haversine_km(13.5925, 124.2049, 13.5925, 124.2049), 0.0, places=6)

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
