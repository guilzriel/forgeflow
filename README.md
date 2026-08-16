# ForgeFlow

ForgeFlow is a public PHP platform-engineering reference project focused on controlled CI/CD, infrastructure validation, operational health checks, configuration drift detection, and secure automation patterns.

The project is intentionally small enough to study end to end while demonstrating patterns that scale to real multi-service Linux environments.

## Runtime

ForgeFlow uses one deployable web runtime image containing:

- Apache HTTP Server
- PHP-FPM
- the ForgeFlow PHP application
- production Composer dependencies

Apache listens on port `8080` and proxies PHP requests to PHP-FPM on `127.0.0.1:9000` inside the same runtime image.

```text
Client
  |
  v
Apache :8080
  |
  v
PHP-FPM 127.0.0.1:9000
  |
  v
ForgeFlow PHP application
```

The application exposes `/health` and returns structured JSON describing service, runtime, status, and version.

## CI/CD and quality

The repository uses GitHub Actions for:

- PHP quality checks
- PHPUnit tests
- PHPStan static analysis
- PHP_CodeSniffer / PSR-12 checks
- Ansible linting and syntax validation
- container builds
- Trivy container vulnerability scanning
- GitHub code-scanning SARIF publication from `main`

## Operational automation

ForgeFlow exposes two operator-facing workflows.

### Run Health Checks

Read-only checks are available for:

- connectivity
- Apache
- PHP-FPM
- the ForgeFlow application
- Redis
- MariaDB/MySQL
- shared storage
- the complete demo environment

Operators select friendly labels only. PHP catalogue code resolves those selections to approved target roles, explicit host lists, maximum host counts, inventory paths, and exact Ansible playbooks.

No arbitrary command, hostname, inventory path, or playbook path is accepted from the workflow form.

### Validate Changes

The validation workflow performs read-only configuration comparisons for:

- Apache
- PHP
- MariaDB/MySQL

Only allowlisted host pairs are valid. Web-server comparisons cannot target database nodes, database comparisons cannot target web nodes, and section headings fail closed.

Reports display meaningful differences only; matching values are omitted.

## Public demo topology

`compose.demo.yaml` creates a disposable environment that can run on a GitHub-hosted runner:

```text
WEB-A  ----\
             +---- shared storage volume
WEB-B  ----/

DB-A
DB-B
Redis
```

Both web nodes use the same combined ForgeFlow image. Small intentional differences make drift reports useful:

| Component | A | B |
|---|---|---|
| Apache `Timeout` | `60` | `90` |
| PHP `memory_limit` | `128M` | `256M` |
| PHP `max_execution_time` | `60` | `90` |
| MariaDB `max_connections` | `100` | `150` |

The database password used by GitHub Actions is generated for the workflow run, masked, and never included in manifests or uploaded evidence.

## Controlled execution model

```text
GitHub Actions form
        |
        v
Operation + target catalogues
        |
        v
Fail-closed PHP resolution
        |
        v
Resolution reauthorization
        |
        v
Explicit blast radius + Ansible limit
        |
        v
Secret-free execution manifest
        |
        v
Read-only Ansible operation
        |
        v
Structured evidence + GitHub summary
```

The catalogue is the source of truth for stable operation IDs, friendly labels, target roles, playbooks, maximum host counts, and risk level.

Generated resolutions are reauthorized before execution so modified operation, target, host-list, inventory, or playbook fields are rejected.

## Evidence and reporting

Health checks produce:

- a secret-free execution manifest
- structured JSON health evidence
- a human-readable Markdown report

Full-environment health runs all applicable components even when an earlier component reports a failure. Missing expected evidence also forces the final result to fail.

Configuration comparisons produce:

- a secret-free execution manifest
- safe read-only captures
- a differences-only Markdown report

## Local runtime

Build and run the normal application:

```powershell
docker compose up --build -d
php .\scripts\wait-for-health.php --url="http://127.0.0.1:8080/health" --timeout=60
Invoke-RestMethod -Uri "http://127.0.0.1:8080/health"
docker compose down --remove-orphans
```

## Local demo topology

From PowerShell, set an ephemeral password in the current shell before starting the demo database nodes:

```powershell
$env:MARIADB_ROOT_PASSWORD = [guid]::NewGuid().ToString("N")
docker compose -f compose.demo.yaml up -d --build
php .\scripts\automation\wait-demo.php
```

The Ansible operational workflows are designed to run on Linux GitHub-hosted runners. The public demo inventory deliberately uses local Docker commands so no private SSH infrastructure is required.

When finished:

```powershell
docker compose -f compose.demo.yaml down --volumes --remove-orphans
Remove-Item Env:MARIADB_ROOT_PASSWORD
```

## Design direction

The next security layer is a provider-independent credential-file contract. Linux deployments can then use `systemd-creds`, while secret backends such as OpenBao or commercial providers can remain optional integrations rather than application dependencies.

See `docs/operational-automation.md` for more detail.
