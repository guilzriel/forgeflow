#!/usr/bin/env bash
set -euo pipefail

python3 -m venv .venv
. .venv/bin/activate
python -m pip install --upgrade pip
python -m pip install -e ".[dev]"

echo "ForgeFlow development environment is ready."
echo "Activate it with: source .venv/bin/activate"
