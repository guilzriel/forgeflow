# Architecture

## Design goal

ForgeFlow demonstrates a controlled path from source code to a verified deployment. GitHub Actions is the operator-facing control plane. Ansible contains the deployment behavior. The demo API exists to make health checks, version verification, image publishing, and rollback visible.

## Separation of responsibilities

### GitHub Actions

- Receives curated operator input
- Validates environment and image policy
- Records the exact source revision
- Produces a deployment plan artifact
- Applies protected GitHub Environment controls
- Selects the reusable deployment workflow
- Produces a human-readable run summary

### Ansible

- Connects to approved target hosts
- Verifies host prerequisites
- Inspects the current deployment
- Records the prior image
- Deploys serially
- Performs post-deployment health checks
- Restores the previous image after a failed health check

### Container image

- Encapsulates the application runtime
- Runs as an unprivileged user
- Exposes liveness, readiness, and version metadata
- Includes an OCI health check
- Carries build version and source revision labels

## Why deployment planning is separate

The manual workflow always validates first and uploads `deployment-plan.json`. The execution job is conditional on the explicit `execute` input. This makes it possible to inspect a request without granting infrastructure access or changing a host.

## Why GitHub Environments are used

Environment-scoped approvals and secrets keep production credentials unavailable to planning jobs. Each execution job references exactly one environment, allowing different reviewers, branch restrictions, inventories, SSH keys, and deployment history.

## Why mutable image tags are rejected

An operator should be able to reproduce exactly what was deployed. Tags such as `latest` and `main` can point to different content later. ForgeFlow requires explicit tags and applies stricter release-or-commit rules to production.

## Recovery behavior

Before deployment, Ansible inspects the running container and records its image. If the new container fails health validation, the role recreates the previous container. Recovery is not claimed when no previous image exists; the playbook fails clearly instead.

## Trust boundaries

- Pull-request code is untrusted until merged.
- Deployment secrets belong to protected environments, not repository variables.
- Public example inventories never contain private hosts.
- The repository does not install Docker on target systems automatically.
- A production implementation should use dedicated runners or a carefully secured network path to managed hosts.
