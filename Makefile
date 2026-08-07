.PHONY: install quality test build up down logs health

install:
composer install

quality:
composer quality

test:
composer test

build:
docker compose build

up:
docker compose up --build -d

down:
docker compose down --remove-orphans

logs:
docker compose logs -f

health:
php scripts/wait-for-health.php --url=http://127.0.0.1:8080/health --timeout=60
