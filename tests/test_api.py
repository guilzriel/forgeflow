from fastapi.testclient import TestClient

from forgeflow_demo.main import app

client = TestClient(app)


def test_root_returns_service_identity() -> None:
    response = client.get("/")

    assert response.status_code == 200
    payload = response.json()
    assert payload["service"] == "forgeflow-demo"
    assert payload["environment"]
    assert payload["version"]
    assert payload["commit"]
    assert payload["hostname"]


def test_health_is_healthy() -> None:
    response = client.get("/health")

    assert response.status_code == 200
    payload = response.json()
    assert payload["status"] == "healthy"
    assert payload["uptime_seconds"] >= 0
    assert payload["timestamp"].endswith("Z")


def test_readiness_is_ready_by_default() -> None:
    response = client.get("/ready")

    assert response.status_code == 200
    assert response.json()["status"] == "healthy"


def test_version_matches_root_identity() -> None:
    root = client.get("/").json()
    version = client.get("/version").json()

    assert version == root


def test_openapi_document_is_available() -> None:
    response = client.get("/openapi.json")

    assert response.status_code == 200
    assert response.json()["info"]["title"] == "ForgeFlow Demo API"
