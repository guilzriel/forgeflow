# Portfolio and interview story

## One-line description

ForgeFlow is a planning-first application delivery platform that uses GitHub Actions as the controlled operator interface and Ansible as the deployment engine.

## What the project proves

- You can design reusable CI/CD instead of duplicating pipelines.
- You understand the boundary between workflow orchestration and infrastructure logic.
- You validate operator input before privileged execution.
- You can publish and attest container images.
- You use protected environments to separate planning from credentials and approvals.
- You understand rolling deployment, health validation, and rollback.
- You can build the reference application as well as the platform that delivers it.
- You documented the project so another engineer can run and extend it.

## A useful interview explanation

> I built ForgeFlow to demonstrate how I approach platform engineering. A pull request goes through code, Ansible, YAML, and container security checks. Version tags publish an immutable image to GHCR. Deployment starts with a non-privileged planning job that validates the environment and image and produces an artifact. Execution is separate, gated by a protected GitHub Environment, and delegated to a reusable Ansible workflow. Ansible deploys one host at a time, validates service health, and restores the previous image when the new version fails validation.

## Design decisions to discuss

### Why a demo API is included

The API makes platform behavior observable. Health, readiness, version, commit, environment, and hostname can be checked by humans, containers, Ansible, and deployment workflows.

### Why deployment is manual

Manual dispatch keeps the reference project understandable and demonstrates approvals and operational input. The same reusable workflow could later be called automatically after promotion criteria are met.

### Why production tag rules are stricter

Production deployments should identify reproducible content. ForgeFlow rejects mutable tags and requires a release tag or commit SHA for production.

### Why Docker installation is not automated

Host bootstrap has a different privilege and lifecycle boundary from application deployment. Keeping it separate prevents the application role from silently changing the host platform.

### Why recovery is not called perfect rollback

The project restores the previous image after a failed health check, but real rollback can also involve databases, queues, caches, and external state. The documentation states that boundary instead of overstating the guarantee.

## Natural next improvements

The roadmap includes Terraform, Kubernetes, deployment manifests, signed-image verification, observability, and progressive delivery. Each extension can be added without replacing the core planning contract.
