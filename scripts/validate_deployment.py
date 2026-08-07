#!/usr/bin/env python3
"""Fail-closed validation for manually requested ForgeFlow deployments."""

from __future__ import annotations

import argparse
import json
import re
import sys
from dataclasses import asdict, dataclass

ALLOWED_ENVIRONMENTS = ("dev", "staging", "production")
IMAGE_PATTERN = re.compile(
    r"^(?P<registry>[a-z0-9.-]+(?:\:[0-9]+)?)/"
    r"(?P<path>[a-z0-9._/-]+)/(?P<name>[a-z0-9._-]+):"
    r"(?P<tag>[A-Za-z0-9][A-Za-z0-9._-]{0,127})$"
)
DISALLOWED_TAGS = {"latest", "main", "master", "develop", "development"}
SEMVER_PATTERN = re.compile(
    r"^v(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)\.(?:0|[1-9][0-9]*)"
    r"(?:-[0-9A-Za-z.-]+)?(?:\+[0-9A-Za-z.-]+)?$"
)


class ValidationError(ValueError):
    """Raised when a deployment request violates policy."""


@dataclass(frozen=True)
class DeploymentRequest:
    environment: str
    image: str
    tag: str


def validate_deployment(environment: str, image: str) -> DeploymentRequest:
    """Validate an environment and immutable-looking image reference."""

    if environment not in ALLOWED_ENVIRONMENTS:
        raise ValidationError(f"environment must be one of: {', '.join(ALLOWED_ENVIRONMENTS)}")

    match = IMAGE_PATTERN.fullmatch(image)
    if not match:
        raise ValidationError(
            "image must include a registry, repository path, image name, and explicit tag"
        )

    tag = match.group("tag")
    if tag.lower() in DISALLOWED_TAGS:
        raise ValidationError(
            f"mutable image tag '{tag}' is not allowed; use a release or commit tag"
        )

    if environment == "production" and not (
        SEMVER_PATTERN.fullmatch(tag) or re.fullmatch(r"[0-9a-f]{7,40}", tag)
    ):
        raise ValidationError("production images must use a semantic release tag or Git commit SHA")

    return DeploymentRequest(environment=environment, image=image, tag=tag)


def parse_args() -> argparse.Namespace:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--environment", required=True)
    parser.add_argument("--image", required=True)
    return parser.parse_args()


def main() -> int:
    args = parse_args()
    try:
        request = validate_deployment(args.environment, args.image)
    except ValidationError as exc:
        print(f"deployment_validation=failed reason={exc}", file=sys.stderr)
        return 2

    print(json.dumps(asdict(request), sort_keys=True))
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
