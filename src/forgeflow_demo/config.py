"""Runtime settings for the ForgeFlow demo service."""

from functools import lru_cache
from typing import Literal

from pydantic import Field
from pydantic_settings import BaseSettings, SettingsConfigDict


class Settings(BaseSettings):
    """Configuration loaded from environment variables."""

    model_config = SettingsConfigDict(
        env_prefix="FORGEFLOW_",
        env_file=".env",
        env_file_encoding="utf-8",
        extra="ignore",
    )

    environment: str = Field(default="local", min_length=1, max_length=64)
    version: str = Field(default="0.1.0", min_length=1, max_length=128)
    commit: str = Field(default="development", min_length=1, max_length=128)
    ready: bool = True
    log_level: Literal["DEBUG", "INFO", "WARNING", "ERROR", "CRITICAL"] = "INFO"


@lru_cache
def get_settings() -> Settings:
    """Return cached settings for the process."""

    return Settings()
