# Portfolio and interview story

## One-line description

ForgeFlow is a controlled operational automation reference project using GitHub Actions, PHP, Ansible, and disposable Linux/container infrastructure.

## What the project demonstrates

ForgeFlow separates operator-facing automation into explicit operational categories instead of placing unrelated delivery tasks into one large pipeline.

The public operating model is:

1. Validate Changes
2. Deploy Component
3. Server Operations
4. Service & Application Operations
5. Health Checks
6. Deploy Environment
7. Rollback

Continuous Integration sits underneath the model as a supporting quality gate.

## Current implementation

Health Checks and Validate Changes are implemented and exercised against the disposable public demo topology.

Deploy Component currently implements the planning boundary: approved component, approved target, resolved hosts, bounded blast radius, source revision, and secret-free evidence.

The execution stage is intentionally being built separately rather than carrying forward the repository's older registry-specific deployment example.

## Design principles

- friendly operator selections
- stable internal operation identifiers
- explicit allowlists
- fail-closed target authorization
- bounded blast radius
- secret-free execution manifests and plans
- read-only validation where appropriate
- structured GitHub summaries and evidence
- Ansible for controlled Linux operational execution
- disposable infrastructure for the public demonstration

The goal is to keep the project small enough to understand end to end while still demonstrating patterns that map cleanly to real operational automation.
