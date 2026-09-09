"""
Trains the two text classification models used by Barangay SAGIP:

  1. Request-type classifier   (Feature 3: ML-Based Request Classification)
  2. Urgency classifier        (Feature 4: Urgency / Priority Classification)

Both are TF-IDF + Linear SVM pipelines (via CalibratedClassifierCV so we get
probability/confidence scores, not just hard labels) — a solid, explainable
baseline appropriate for a capstone-scale dataset. Swap in a fine-tuned
multilingual transformer later (see proposal Section 9.1) once a larger,
real dataset is available without changing the API surface.
"""
import json
import joblib
import pandas as pd
from sklearn.feature_extraction.text import TfidfVectorizer
from sklearn.svm import LinearSVC
from sklearn.calibration import CalibratedClassifierCV
from sklearn.pipeline import Pipeline
from sklearn.model_selection import train_test_split
from sklearn.metrics import classification_report, accuracy_score

DATA_PATH = "data/sample_requests.csv"
MODEL_DIR = "models"


def train_one(df, label_col, model_name):
    # Split by base_template (not by individual row) so that near-duplicate
    # prefix/suffix variants of the same sentence never appear in both train
    # and test. Splitting by row alone would leak near-identical text across
    # the split and produce an inflated, unrealistic accuracy score.
    templates = df["base_template"].unique()
    train_templates, test_templates = train_test_split(
        templates, test_size=0.25, random_state=42
    )
    train_df = df[df["base_template"].isin(train_templates)]
    test_df = df[df["base_template"].isin(test_templates)]

    X_train, y_train = train_df["text"], train_df[label_col]
    X_test, y_test = test_df["text"], test_df[label_col]

    pipeline = Pipeline([
        ("tfidf", TfidfVectorizer(ngram_range=(1, 2), min_df=1, lowercase=True)),
        ("clf", CalibratedClassifierCV(LinearSVC(class_weight="balanced"), cv=3)),
    ])

    pipeline.fit(X_train, y_train)
    y_pred = pipeline.predict(X_test)

    report = classification_report(y_test, y_pred, output_dict=True, zero_division=0)
    acc = accuracy_score(y_test, y_pred)

    print(f"\n=== {model_name} ===")
    print(f"Accuracy: {acc:.3f}")
    print(classification_report(y_test, y_pred, zero_division=0))

    # Refit on the full dataset before saving, so the shipped model uses all
    # available labeled data (common practice once the held-out eval is done).
    pipeline.fit(df["text"], df[label_col])
    joblib.dump(pipeline, f"{MODEL_DIR}/{model_name}.pkl")

    return {"accuracy": acc, "report": report}


def main():
    df = pd.read_csv(DATA_PATH)
    metrics = {}
    metrics["request_type_classifier"] = train_one(df, "category", "request_type_classifier")
    metrics["urgency_classifier"] = train_one(df, "urgency", "urgency_classifier")

    with open(f"{MODEL_DIR}/training_metrics.json", "w") as f:
        json.dump(metrics, f, indent=2)

    print("\nSaved models to ./models/  (request_type_classifier.pkl, urgency_classifier.pkl)")
    print("Saved evaluation metrics to ./models/training_metrics.json")


if __name__ == "__main__":
    main()
