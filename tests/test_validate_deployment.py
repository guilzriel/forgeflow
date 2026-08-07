import pytest
from scripts.validate_deployment import ValidationError, validate_deployment


def test_accepts_versioned_dev_image() -> None:
    request = validate_deployment("dev", "ghcr.io/example/forgeflow:v0.1.0")

    assert request.environment == "dev"
    assert request.tag == "v0.1.0"


def test_accepts_commit_sha_in_production() -> None:
    request = validate_deployment("production", "ghcr.io/example/forgeflow:0123456789abcdef")

    assert request.tag == "0123456789abcdef"


@pytest.mark.parametrize("environment", ["", "qa", "prod", "Production"])
def test_rejects_unknown_environment(environment: str) -> None:
    with pytest.raises(ValidationError, match="environment must be"):
        validate_deployment(environment, "ghcr.io/example/forgeflow:v1.0.0")


@pytest.mark.parametrize(
    "image",
    [
        "forgeflow:v1.0.0",
        "ghcr.io/forgeflow",
        "ghcr.io/example/forgeflow",
        "ghcr.io/Example/forgeflow:v1.0.0",
    ],
)
def test_rejects_malformed_image(image: str) -> None:
    with pytest.raises(ValidationError, match="image must include"):
        validate_deployment("dev", image)


@pytest.mark.parametrize("tag", ["latest", "main", "master", "develop"])
def test_rejects_mutable_tags(tag: str) -> None:
    with pytest.raises(ValidationError, match="mutable image tag"):
        validate_deployment("staging", f"ghcr.io/example/forgeflow:{tag}")


@pytest.mark.parametrize("tag", ["candidate-1", "vnext", "v1", "v1.2"])
def test_production_requires_release_or_commit_tag(tag: str) -> None:
    with pytest.raises(ValidationError, match="production images"):
        validate_deployment("production", f"ghcr.io/example/forgeflow:{tag}")
