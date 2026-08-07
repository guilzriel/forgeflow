"""FastAPI entry point for the ForgeFlow reference application."""

from __future__ import annotations

import logging
import socket
import time
from datetime import UTC, datetime

from fastapi import FastAPI, HTTPException, status
from pydantic import BaseModel

from forgeflow_demo.config import get_settings

settings = get_settings()
logging.basicConfig(
    level=settings.log_level,
    format="%(asctime)s level=%(levelname)s logger=%(name)s message=%(message)s",
)
logger = logging.getLogger("forgeflow")
started_monotonic = time.monotonic()

app = FastAPI(
    title="ForgeFlow Demo API",
    summary="A small service used to demonstrate a production-style delivery platform.",
    version=settings.version,
)


class ServiceInfo(BaseModel):
    service: str
    environment: str
    version: str
    commit: str
    hostname: str


class HealthInfo(ServiceInfo):
    status: str
    timestamp: datetime
    uptime_seconds: float


def service_info() -> ServiceInfo:
    """Build identifying information shared by API responses."""

    return ServiceInfo(
        service="forgeflow-demo",
        environment=settings.environment,
        version=settings.version,
        commit=settings.commit,
        hostname=socket.gethostname(),
    )


@app.get("/", response_model=ServiceInfo)
def root() -> ServiceInfo:
    """Return basic service identity."""

    return service_info()


@app.get("/health", response_model=HealthInfo)
def health() -> HealthInfo:
    """Return liveness information for container and deployment health checks."""

    info = service_info()
    return HealthInfo(
        **info.model_dump(),
        status="healthy",
        timestamp=datetime.now(UTC),
        uptime_seconds=round(time.monotonic() - started_monotonic, 3),
    )


@app.get("/ready", response_model=HealthInfo)
def readiness() -> HealthInfo:
    """Return readiness state; operators can force not-ready for demonstrations."""

    if not settings.ready:
        logger.warning("Readiness check failed because FORGEFLOW_READY is false")
        raise HTTPException(
            status_code=status.HTTP_503_SERVICE_UNAVAILABLE,
            detail="service is not ready",
        )
    return health()


@app.get("/version", response_model=ServiceInfo)
def version() -> ServiceInfo:
    """Return deployable version and source revision information."""

    return service_info()
