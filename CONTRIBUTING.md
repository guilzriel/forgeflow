# Contributing

Thank you for improving ForgeFlow.

## Contribution principles

1. Keep examples generic and safe for public use.
2. Never submit credentials, private hostnames, internal IP addresses, or employer-specific configuration.
3. Prefer reusable platform capabilities over one-off organization workflows.
4. Validate inputs before privileged operations.
5. Include health checks and a recovery path for deployment changes.
6. Keep pull requests focused and explain operational impact.

## Development setup

```bash
python -m venv .venv
source .venv/bin/activate
python -m pip install -e ".[dev]"
make all
```

Windows PowerShell:

```powershell
python -m venv .venv
.\.venv\Scripts\Activate.ps1
python -m pip install -e ".[dev]"
pytest
ruff check src scripts tests
```

## Pull requests

- Open an issue for major architectural changes.
- Add or update tests.
- Update documentation when operator behavior changes.
- Describe blast radius and rollback behavior.
- Verify that generated files and secrets are not included.

## Commit style

Use clear imperative subjects, for example:

```text
Add deployment manifest validation
Harden container runtime settings
Document production environment controls
```

## Reviewing Ansible changes

Ansible changes should be idempotent where practical. Use fully qualified collection names, explicit file permissions, bounded retries, and clear failure messages. Do not disable SSH host-key checking in committed configuration.
