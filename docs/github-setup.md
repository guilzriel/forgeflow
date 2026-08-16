# ForgeFlow GitHub setup

ForgeFlow uses GitHub Actions as the operator interface for the public automation demo.

## Repository settings

Protect `main` and require pull requests plus passing Continuous Integration checks before merge.

The public demo does not require private infrastructure, deployment SSH keys, a container registry release workflow, or a self-hosted runner.

## Active Actions

The repository currently exposes:

- Health Checks
- Validate Changes
- Deploy Component
- Continuous Integration

Health Checks and Validate Changes are implemented operational workflows.

Deploy Component currently provides controlled planning and will receive its execution path as the next implementation stage.

Continuous Integration is the supporting quality gate and is not one of the seven operational categories.

## Seven operational categories

The target operator model is:

1. Validate Changes
2. Deploy Component
3. Server Operations
4. Service & Application Operations
5. Health Checks
6. Deploy Environment
7. Rollback

Additional GitHub workflows should only be added when they directly support this operating model.
