# Security Policy

## Supported baseline

Security fixes should target the current `main` branch and the latest production release.

The repository CI enforces:

- Composer dependency auditing.
- npm dependency auditing.
- Python dependency auditing with `pip-audit`.
- Gitleaks secret scanning.
- CodeQL analysis for PHP, JavaScript/TypeScript, and Python.
- High and critical vulnerability scanning of production container images with Trivy.
- PostgreSQL-backed application tests before a production release.

## Reporting a vulnerability

Do not disclose an unpatched security vulnerability in a public GitHub issue or pull request.

If GitHub private vulnerability reporting is enabled for this repository, use that mechanism. Otherwise, contact the repository owner privately through an existing trusted channel and include the affected component, reproduction steps, impact, and any suggested mitigation.

Do not include real resident data, credentials, API keys, or other personal information in a report.

## Secrets

Never commit `.env` files, production credentials, service keys, database passwords, private certificates, or real resident information. Rotate any credential that is accidentally committed or exposed.
