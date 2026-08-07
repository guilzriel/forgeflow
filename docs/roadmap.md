# Roadmap

ForgeFlow starts with a deliberately understandable deployment path. Contributions should preserve the fail-closed model.

## Near term

- Pin third-party GitHub Actions to full commit SHAs with automated update tooling
- Add integration tests using a disposable Docker host
- Add signed container verification before deployment
- Add deployment manifests containing digest, actor, source revision, targets, and result
- Add a dry-run view that resolves target hostnames without connecting
- Add an optional reverse proxy with TLS for the demo environment

## Later

- Terraform module for a disposable cloud lab
- Kubernetes deployment option with the same planning contract
- OpenTelemetry traces and Prometheus metrics
- Progressive delivery and automated rollback based on service-level signals
- Policy-as-code checks with Open Policy Agent
- Reusable workflow release that other repositories can call

## Non-goals

- Accepting arbitrary shell commands from workflow inputs
- Storing real infrastructure credentials in the repository
- Becoming a full commercial deployment product
- Hiding platform decisions behind unexplained automation
