.PHONY: help install test lint typecheck run compose-up compose-down build ansible-lint validate all

help:
	@echo "ForgeFlow commands:"
	@echo "  make install       Install the project and development tools"
	@echo "  make test          Run unit tests with coverage"
	@echo "  make lint          Run Ruff and YAML lint"
	@echo "  make typecheck     Run mypy"
	@echo "  make run           Run the API locally"
	@echo "  make compose-up    Build and start the container"
	@echo "  make compose-down  Stop the local container"
	@echo "  make ansible-lint  Validate Ansible content"
	@echo "  make validate      Run deployment-input validation examples"
	@echo "  make all           Run the full local quality gate"

install:
	python -m pip install --upgrade pip
	python -m pip install -e ".[dev]"

test:
	pytest --cov=forgeflow_demo --cov-report=term-missing

lint:
	ruff check src scripts tests
	ruff format --check src scripts tests
	yamllint .github ansible compose.yaml .yamllint.yml

typecheck:
	mypy

run:
	uvicorn forgeflow_demo.main:app --app-dir src --reload --port 8000

compose-up:
	docker compose up --build -d
	python scripts/wait_for_health.py --url http://localhost:8000/health --timeout 60

compose-down:
	docker compose down --remove-orphans

build:
	docker build --build-arg APP_VERSION=local --build-arg VCS_REF=development -t forgeflow:local .

ansible-lint:
	ansible-galaxy collection install -r ansible/requirements.yml
	ansible-lint ansible

validate:
	python scripts/validate_deployment.py --environment dev --image ghcr.io/example/forgeflow:v0.1.0

all: lint typecheck test validate
