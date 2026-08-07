# Validation record

Validation performed on August 6, 2026 before packaging the initial project.

## Passed locally

- Python bytecode compilation
- 21 unit and policy tests
- 96.43% application test coverage; required threshold is 90%
- FastAPI endpoint tests
- Deployment environment and image-policy tests
- Mutable image-tag rejection tests
- Health-wait script test using a temporary HTTP server
- YAML parsing for GitHub, Ansible, Compose, and lint configuration
- TOML parsing for `pyproject.toml`
- Editable Python package build and import
- Check that workflow conditions do not directly evaluate secret values

## Requires GitHub or a Docker/Ansible host

The packaging environment did not provide Docker, Ansible, `ruff`, `mypy`, `yamllint`, or `ansible-lint` executables. The repository includes CI jobs for those checks, but the following must be confirmed by the first GitHub run:

- GitHub Actions expression and hosted-runner execution
- Ruff formatting and linting
- mypy strict type checking
- YAML linting
- Ansible linting and collection installation
- Docker image build
- Trivy container scan
- GHCR publishing and provenance attestation
- Ansible execution against a Docker-capable managed host
- Environment approvals and remote SSH material

No claim is made that an external deployment was performed during packaging.
