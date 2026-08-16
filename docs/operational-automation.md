# ForgeFlow operational automation

ForgeFlow demonstrates a controlled GitHub Actions + Ansible operating model without requiring private infrastructure.

## Operator workflows

### Run Health Checks

The manual workflow exposes allowlisted, read-only checks for:

- Connectivity
- Apache
- PHP-FPM
- ForgeFlow application health
- Redis
- MariaDB/MySQL
- Shared storage
- Full environment health

The workflow never accepts arbitrary shell commands, inventory paths, playbook paths, or hostnames. A PHP catalogue resolves the friendly selection to an approved operation, exact host list, maximum blast radius, inventory, and Ansible playbook.

Every health run creates a secret-free execution manifest plus structured JSON and Markdown evidence. Full-environment health runs each component independently so a failure in one component does not prevent later checks from producing evidence.

### Validate Changes

The manual validation workflow supports read-only configuration drift comparison for:

- Apache
- PHP
- MariaDB/MySQL

Only approved host pairs may be selected. Web comparisons cannot target database hosts and database comparisons cannot target web hosts. Reports show differences only and omit matching settings.


### Deploy Component

Deploy Component replaces the repository's earlier release-image/reusable-deployment example.

The current workflow is intentionally planning-only. It demonstrates the public operator contract without carrying forward the old registry-specific deployment implementation:

- approved component selection
- approved target selection
- explicit resolved hosts
- bounded maximum host count
- exact source revision
- secret-free deployment plan

Controlled execution will be added as the next implementation stage.

## Seven operational categories

The intended operator-facing workflow model is:

1. Validate Changes
2. Deploy Component
3. Server Operations
4. Service & Application Operations
5. Health Checks
6. Deploy Environment
7. Rollback

Continuous Integration is a supporting quality gate and is not counted as one of the seven operational workflows.
## Public demo topology

`compose.demo.yaml` creates a disposable environment:

- two ForgeFlow web nodes using the same combined Apache + PHP-FPM image
- two MariaDB nodes
- one Redis node
- one shared Docker volume visible from both web nodes

Small intentional differences are injected so the comparison workflows have meaningful output:

| Component | A | B |
|---|---|---|
| Apache Timeout | 60 | 90 |
| PHP memory_limit | 128M | 256M |
| MariaDB max_connections | 100 | 150 |

The MariaDB root password is generated only for the workflow run and is masked. It is not written to manifests, reports, or uploaded artifacts.

## Architecture

```text
GitHub Actions operator form
        |
        v
PHP allowlist/catalogue resolution
        |
        +--> exact operation
        +--> exact target role
        +--> explicit host list
        +--> maximum host count
        +--> exact Ansible playbook
        |
        v
Secret-free execution manifest
        |
        v
Ansible read-only operational checks
        |
        v
Structured evidence + GitHub summary
```

The public demo inventory uses local Docker commands so GitHub-hosted runners can execute it for free. The same operation and playbook model can be paired with a normal SSH inventory for real Linux hosts.
