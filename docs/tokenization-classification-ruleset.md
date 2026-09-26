# Barangay SAGIP Tokenization Classification Ruleset

**Ruleset version:** 1.0.0  
**Status:** Final project architecture  
**Implementation:** `tokenization-service/tokenizer_classifier.py` and `tokenization-service/main.py`

## 1. Purpose

Barangay SAGIP uses a deterministic, tokenization-based classification architecture. The system does not train a statistical model. It applies transparent hand-curated rules to resident request text and uses explicit scoring and review thresholds to decide whether a result can proceed automatically or must be checked by an authorized official.

This document is the authoritative technical ruleset for the classification service.

## 2. Text normalization

Before matching:

1. Convert the input to lowercase.
2. Replace punctuation with spaces.
3. Collapse repeated whitespace.
4. Preserve letters, numbers, underscores, spaces, and hyphens.
5. Match phrases using word boundaries so a short rule does not accidentally match inside an unrelated word.

A rule is counted at most once per label, even if the same phrase appears multiple times in the request.

## 3. Request-type rules

Each matching rule contributes one point to its category.

### Medical

- gamot
- sugat
- dumudugo
- lagnat
- sumasakit
- buntis
- ospital
- ambulansya
- humihinga
- unconscious
- seizure
- first aid
- bakuna
- dental
- senior citizen
- tiyan
- sumusuka
- nahihilo
- nanghihina
- nadapa
- nabali
- binti
- chest pain
- nalunod

### Fire

- sunog
- apoy
- usok
- umuusok
- liyab
- paso
- gasul
- bumbero
- fire safety
- fire extinguisher
- nasusunog
- sumasabog

### Peace / Order

- baril
- kutsilyo
- holdaper
- away
- lasing
- nanakot
- nagnanakaw
- blotter
- kahina-hinalang
- sumisigaw
- patalim
- saktan
- nag-aaway
- nag-iingay

### Disaster

- baha
- bumabaha
- lindol
- gumuho
- landslide
- bagyo
- ulan
- hangin
- puno
- poste
- kanal
- typhoon
- evacuation
- tumataas ang tubig
- creek

### General assistance

- burial assistance
- relief goods
- ayuda
- trabaho
- clearance
- indigency
- business permit
- barangay hall
- byahe
- requirements
- certificate
- permit

## 4. Urgency rules

Each matching rule contributes one point to its urgency level.

### Critical

- hindi humihinga
- hindi na humihinga
- unconscious
- seizure
- tinangay
- nakulong
- may dalang baril
- may hawak na patalim
- sumisigaw ng tulong
- mamamatay
- agad
- ngayon din
- malaki na ang apoy
- kumakalat na ang apoy
- nanganganib mabuhay

### High

- lagnat na mataas
- sumasakit ng husto
- hirap huminga
- sugat na malalim
- may usok
- kahina-hinalang
- tumataas nang mabilis
- bumagsak na malaking puno
- nag-iingay at lasing
- nadapa

### Average

- pwede po ba
- need po sana
- may naaamoy
- may nagnanakaw
- may sirang
- may baradong
- pwede po humingi
- sugat lang

### Low

- tanong ko lang
- tanong lang po
- kailan po
- meron po ba
- paano po
- anong oras
- saan po
- ano po ang

## 5. Label-selection and confidence

For either request type or urgency:

```
confidence(label) = matches_for_label / total_matches
```

The label with the highest match count is selected as the deterministic display label.

### Review conditions

A request-type or urgency result is marked `needs_review=true` when either condition is true:

1. **Low confidence:** confidence is below `TOKENIZATION_CONFIDENCE_THRESHOLD` (default `0.45`).
2. **Ambiguous tie:** two or more labels share the highest positive match score.

When there are no matches, the classifier returns the configured fallback label (`general_assistance` for request type, `average` for urgency) with confidence `0.0`; this is always treated as a review case.

The deterministic label returned for a tie is the first label declared in the relevant dictionary. That value is for display/audit only; the review flag prevents the tie from being treated as a confident automated result.

## 6. Response-assignment scoring

Only available responder candidates participate in the scoring calculation. The Laravel layer also excludes personnel with active assignments before calling the service.

For each candidate:

### Distance

Distance is calculated using the Haversine formula in kilometers.

```
proximity_score = 1 / (1 + distance_km)
```

### Specialization

```
specialization_score = 1.0  if responder specialization matches request category
                       0.3  otherwise
```

### Workload

```
workload_score = 1 / (1 + current_workload)
```

### Base suitability

```
base_score =
    0.40 * proximity_score
  + 0.35 * specialization_score
  + 0.25 * workload_score
```

### Urgency multiplier

```
critical = 1.00
high     = 0.75
average  = 0.50
low      = 0.25

urgency_multiplier = 0.5 + 0.5 * urgency_weight
```

### Final assignment score

```
assignment_score = base_score * urgency_multiplier
```

Candidates are sorted by descending assignment score, with personnel ID as the deterministic secondary sort key.

## 7. Assignment review guardrails

A recommendation is returned only when all required guardrails pass:

- At least one available candidate exists.
- Top assignment score is at least `TOKENIZATION_ASSIGNMENT_MIN_SCORE` (default `0.35`).
- When at least two candidates exist, the difference between the top two scores is at least `TOKENIZATION_ASSIGNMENT_MIN_MARGIN` (default `0.05`).

Otherwise, the service returns the full ranking with `recommended_personnel_id=null`, sets `needs_review=true`, and supplies a reason for manual handling.

These thresholds are configuration values, not magic numbers embedded in the decision path.

## 8. Configuration

The service reads these environment variables:

| Variable | Default | Purpose |
|---|---:|---|
| `TOKENIZATION_CONFIDENCE_THRESHOLD` | `0.45` | Minimum request-type/urgency confidence before review |
| `TOKENIZATION_ASSIGNMENT_MIN_SCORE` | `0.35` | Minimum top responder suitability score |
| `TOKENIZATION_ASSIGNMENT_MIN_MARGIN` | `0.05` | Minimum separation between first and second responder |
| `TOKENIZATION_RULESET_VERSION` | `1.0.0` | Version reported by the service health endpoint |

All four values must be between 0 and 1 where applicable.

## 9. Ambiguous and mixed-language input

The dictionaries intentionally include Filipino/English terms and common local wording. Mixed-language requests are processed through the same normalization and matching rules.

Keyword collisions are not silently resolved by a hidden learned model. When a collision produces a tie or a low score, the service exposes the ambiguity and routes the request to human validation.

## 10. Versioning

The ruleset version changes whenever the keyword dictionaries, score weights, fallback policy, tie policy, or review thresholds change in a way that affects classification behavior.

For every ruleset change:

1. Update this document.
2. Update `TOKENIZATION_RULESET_VERSION`.
3. Add or update automated tests for the changed behavior.
4. Record the change in Git history with a descriptive commit.

## 11. Engineering position

The tokenization/rule-based architecture is the final intended classification approach for this thesis implementation. Its strengths are transparency, auditability, deterministic behavior, low infrastructure cost, and direct human-review escalation for ambiguous cases. It must not be presented as trained machine-learning inference.
