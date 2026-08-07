# Deployment guide

## 1. Prepare a target host

The reference deployment expects:

- Linux
- Docker Engine
- Python available for Ansible
- The Python Docker SDK available to the managed host interpreter
- A deployment user able to use Docker through privilege escalation
- SSH host keys managed outside this repository
- Network access from the runner to the target
- Network access from the target to GHCR

ForgeFlow deliberately does not install Docker because host bootstrap and application deployment are different trust domains.

## 2. Create an inventory

Use an environment-scoped secret named `ANSIBLE_INVENTORY`:

```yaml
all:
  hosts:
    app-01:
      ansible_host: 203.0.113.20
      ansible_user: deploy
      forgeflow_environment: staging
      forgeflow_public_port: 8000
```

Use your real approved address only in the protected secret. Do not commit it.

## 3. Configure SSH

Add the private key as `DEPLOY_SSH_KEY` and trusted host-key lines as `DEPLOY_KNOWN_HOSTS`. Obtain those host keys through an out-of-band trusted process. Do not use `StrictHostKeyChecking=no` as a shortcut.

## 4. Protect environments

Suggested policy:

| Environment | Reviewers | Deployment source |
|---|---:|---|
| dev | Optional | `main` and release tags |
| staging | One reviewer | Protected branches and release tags |
| production | Two reviewers | Signed release tags |

The exact settings are repository configuration and cannot be embedded safely in workflow YAML alone.

## 5. Run a plan

Use the Deploy workflow with `Execute: false`. Download `deployment-plan-<run-id>` and verify the environment, image, tag, and source revision.

## 6. Execute

Run the same values with `Execute: true`. The protected environment approval occurs before environment secrets are exposed.

## 7. Confirm

Verify the workflow summary and the deployed endpoint:

```bash
curl --fail http://HOST:8000/version
curl --fail http://HOST:8000/health
```

## 8. Roll back

Automatic recovery runs on failed deployment health validation. A manual rollback uses `ansible/playbooks/rollback.yml` and the recorded `/opt/forgeflow/previous-image` file.
