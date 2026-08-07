# ForgeFlow

**A community-ready reference project for controlled application delivery with GitHub Actions, Ansible, Docker, FastAPI, automated validation, security scanning, environment approvals, health checks, and rollback.**

ForgeFlow is intentionally small enough to understand, but structured like a real internal developer platform. It demonstrates how a commit can move through repeatable quality gates, become a versioned container, and deploy through a controlled operational interface.

> This is a generic public project. It contains no private hostnames, credentials, employer configuration, or proprietary infrastructure code.

## What it demonstrates

- Pull-request CI for tests, linting, typing, YAML, Ansible, and container scanning
- A containerized FastAPI reference service with liveness, readiness, and version endpoints
- Versioned multi-architecture images published to GitHub Container Registry
- Build provenance attestations
- Manual deployment planning before execution
- GitHub Environments for approvals, secrets, variables, and deployment history
- A reusable deployment workflow instead of duplicated pipelines
- Fail-closed deployment input validation
- Ansible-based rolling deployment, post-deployment health checks, and automatic recovery
- A documented community contribution and security model

## Architecture

```text
Pull request
    |
    v
GitHub Actions CI
  - Python quality and tests
  - Ansible/YAML validation
  - Container build and Trivy scan
    |
    v
Version tag (vX.Y.Z)
    |
    v
GHCR image + provenance attestation
    |
    v
Manual deployment request
  - Validate environment and immutable image tag
  - Generate downloadable deployment plan
  - Optional execution flag
    |
    v
Protected GitHub Environment
  - Approval / branch restrictions
  - Environment-scoped inventory and SSH key
    |
    v
Reusable Ansible deployment
  - Preflight
  - Serial deployment
  - Health validation
  - Automatic recovery to the previous image on failure
```

See [docs/architecture.md](docs/architecture.md) for the design rationale.

## Quick start

### Requirements

- Python 3.12+
- Docker with Docker Compose
- Git
- Optional: GNU Make

### Windows PowerShell

```powershell
Set-Location forgeflow
powershell -ExecutionPolicy Bypass -File .\scripts\bootstrap.ps1
.\.venv\Scripts\Activate.ps1
pytest

docker compose up --build -d
python .\scripts\wait_for_health.py --url http://localhost:8000/health --timeout 60
Invoke-RestMethod http://localhost:8000/version | Format-List
```

Stop the demo:

```powershell
docker compose down
```

### Linux or macOS

```bash
cd forgeflow
./scripts/bootstrap.sh
source .venv/bin/activate
make all
make compose-up
curl --fail http://localhost:8000/version
```

### API endpoints

| Endpoint | Purpose |
|---|---|
| `/` | Service identity |
| `/health` | Liveness check |
| `/ready` | Readiness check |
| `/version` | Version, commit, environment, and hostname |
| `/docs` | Interactive OpenAPI documentation |

## Repository map

```text
.github/workflows/        CI, release, deployment, and reusable workflow
ansible/                  Inventories, playbooks, role, and collection requirements
docs/                     Architecture, deployment, and roadmap documentation
scripts/                  Bootstrap, deployment validation, and health-wait tools
src/forgeflow_demo/       FastAPI reference application
tests/                    Application and policy tests
Dockerfile                Multi-stage, non-root production image
compose.yaml              Hardened local container example
```

## Local quality gate

```bash
make all
```

The equivalent commands are:

```bash
ruff check src scripts tests
ruff format --check src scripts tests
mypy
pytest --cov=forgeflow_demo --cov-report=term-missing
yamllint .github ansible compose.yaml .yamllint.yml
python scripts/validate_deployment.py \
  --environment dev \
  --image ghcr.io/example/forgeflow:v0.1.0
```

Ansible lint also requires collections:

```bash
ansible-galaxy collection install -r ansible/requirements.yml
ansible-lint ansible
```

## Publish your first image

1. Create a public GitHub repository and push this project.
2. Keep GitHub Actions enabled with read/write package permissions for the release workflow.
3. Create and push a semantic version tag:

```bash
git tag v0.1.0
git push origin v0.1.0
```

The release workflow publishes:

```text
ghcr.io/<owner>/<repository>:v0.1.0
ghcr.io/<owner>/<repository>:sha-<commit>
```

## Configure deployment environments

Create `dev`, `staging`, and `production` under **Settings → Environments**.

For remote deployments, add these environment secrets:

| Secret | Purpose |
|---|---|
| `ANSIBLE_INVENTORY` | Complete YAML inventory for that environment |
| `DEPLOY_SSH_KEY` | SSH private key used only for the protected environment |
| `DEPLOY_KNOWN_HOSTS` | Trusted SSH host-key entries for the approved targets |

Recommended controls:

- Require reviewers for production
- Restrict production deployments to protected tags or branches
- Prevent administrators from bypassing protection when appropriate
- Keep inventories and SSH credentials environment-scoped
- Use a dedicated, least-privileged deployment account

The included `dev` inventory deploys to local Docker for demonstration. Staging and production examples use documentation-only addresses from the `192.0.2.0/24` block and cannot reach real hosts.

## Trigger a deployment

Open **Actions → Deploy → Run workflow** and enter:

```text
Environment: dev
Image: ghcr.io/<owner>/<repository>:v0.1.0
Execute: false
```

That creates and uploads a deployment plan without changing infrastructure. Review it, then run again with `Execute: true`.

Production accepts only a semantic `vX.Y.Z` release tag or a Git commit SHA. Mutable tags such as `latest`, `main`, and `develop` are rejected.

## Rollback

The Ansible role records the previously running image before replacing the container. A failed health check triggers automatic recovery when a previous image exists.

Manual rollback:

```bash
cd ansible
ansible-playbook \
  -i inventories/dev/hosts.yml \
  playbooks/rollback.yml
```

## Security model

- No arbitrary command or playbook inputs
- Explicit deployment environments
- Explicit immutable image references
- Source revision passed from planning to execution
- Protected environment secrets are unavailable before approval
- Serial deployment prevents an all-at-once outage
- Health validation gates success
- Previous image recorded for recovery
- Non-root, read-only container with Linux capabilities dropped
- Dependabot coverage for Python, GitHub Actions, and Docker
- Trivy scan for high and critical image vulnerabilities

Read [SECURITY.md](SECURITY.md) before reporting a vulnerability.

## Community

ForgeFlow is designed to be forked and adapted. Good contributions improve reusable platform capabilities rather than adding organization-specific secrets or infrastructure.

See [CONTRIBUTING.md](CONTRIBUTING.md), [CODE_OF_CONDUCT.md](CODE_OF_CONDUCT.md), [the GitHub publishing guide](docs/github-setup.md), [the portfolio story](docs/portfolio-story.md), and [the roadmap](docs/roadmap.md).

## License

MIT. See [LICENSE](LICENSE).
