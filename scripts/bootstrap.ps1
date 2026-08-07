$ErrorActionPreference = "Stop"

python -m venv .venv
& .\.venv\Scripts\python.exe -m pip install --upgrade pip
& .\.venv\Scripts\python.exe -m pip install -e ".[dev]"

Write-Host "ForgeFlow development environment is ready."
Write-Host "Activate it with: .\.venv\Scripts\Activate.ps1"
