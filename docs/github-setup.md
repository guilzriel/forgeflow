# Publish ForgeFlow to GitHub

This guide uses Windows PowerShell, matching the repository's included bootstrap script.

## Option A: GitHub CLI

From the extracted `forgeflow` directory:

```powershell
git init
git branch -M main
git add .
git commit -m "Create ForgeFlow application delivery platform"

gh auth login
gh repo create forgeflow --public --source . --remote origin --push
```

## Option B: GitHub website and Git

1. Create an empty public repository named `forgeflow`.
2. Do not add a generated README, license, or `.gitignore`; this project already includes them.
3. Run:

```powershell
git init
git branch -M main
git add .
git commit -m "Create ForgeFlow application delivery platform"
git remote add origin https://github.com/YOUR-USERNAME/forgeflow.git
git push -u origin main
```

Replace `YOUR-USERNAME` with the GitHub account or organization that owns the repository.

## Repository settings

### Actions

Under **Settings → Actions → General**:

- Allow the actions used by this repository.
- Ensure workflows can receive the permissions declared in their YAML.
- Keep approval for workflows from first-time external contributors enabled.

### Branch protection

Protect `main` and require:

- Pull requests before merging
- Passing CI checks
- Conversation resolution
- No force pushes
- No branch deletion

### Environments

Create:

```text
dev
staging
production
```

For staging and production, configure required reviewers and deployment branch or tag restrictions.

For remote deployment, add these environment secrets:

```text
ANSIBLE_INVENTORY
DEPLOY_SSH_KEY
DEPLOY_KNOWN_HOSTS
```

Do not add sample or real secrets to the repository itself.

### Security

Enable the dependency graph, Dependabot alerts, Dependabot security updates, secret scanning, and private vulnerability reporting when those features are available for the repository.

## First CI run

Opening or pushing `main` triggers the CI workflow. It checks:

- Python formatting, linting, typing, tests, and coverage
- YAML and Ansible content
- Deployment policy validation
- Container build and Trivy vulnerability scan

## First release

After CI passes:

```powershell
git tag v0.1.0
git push origin v0.1.0
```

The release workflow builds multi-architecture images, publishes them to GHCR, and creates a provenance attestation.

## First deployment plan

Open **Actions → Deploy → Run workflow** and use:

```text
Environment: dev
Image: ghcr.io/YOUR-USERNAME/forgeflow:v0.1.0
Execute: false
```

Review the uploaded deployment plan. Run it again with `Execute: true` only after the target and image are correct.
