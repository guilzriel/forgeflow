# syntax=docker/dockerfile:1.7
FROM python:3.12-slim AS builder

ENV PIP_DISABLE_PIP_VERSION_CHECK=1 \
    PIP_NO_CACHE_DIR=1

WORKDIR /build
COPY pyproject.toml README.md ./
COPY src ./src
RUN python -m pip wheel --wheel-dir /wheels .

FROM python:3.12-slim AS runtime

ARG APP_VERSION=0.1.0
ARG VCS_REF=unknown

LABEL org.opencontainers.image.title="ForgeFlow Demo API" \
      org.opencontainers.image.description="Reference service deployed by ForgeFlow" \
      org.opencontainers.image.version="${APP_VERSION}" \
      org.opencontainers.image.revision="${VCS_REF}" \
      org.opencontainers.image.licenses="MIT"

ENV PYTHONDONTWRITEBYTECODE=1 \
    PYTHONUNBUFFERED=1 \
    FORGEFLOW_VERSION="${APP_VERSION}" \
    FORGEFLOW_COMMIT="${VCS_REF}"

RUN groupadd --system --gid 10001 forgeflow \
    && useradd --system --uid 10001 --gid forgeflow --home-dir /app forgeflow

WORKDIR /app
COPY --from=builder /wheels /wheels
RUN python -m pip install --no-cache-dir /wheels/* \
    && rm -rf /wheels

USER 10001:10001
EXPOSE 8000

HEALTHCHECK --interval=10s --timeout=3s --start-period=5s --retries=3 \
  CMD python -c "import urllib.request; urllib.request.urlopen('http://127.0.0.1:8000/health', timeout=2)"

CMD ["uvicorn", "forgeflow_demo.main:app", "--host", "0.0.0.0", "--port", "8000", "--no-access-log"]
