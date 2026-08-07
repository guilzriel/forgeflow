#!/usr/bin/env python3
"""Wait for an HTTP health endpoint without requiring third-party packages."""

from __future__ import annotations

import argparse
import json
import sys
import time
import urllib.error
import urllib.request


def wait_for_health(url: str, timeout: float, interval: float) -> dict[str, object]:
    deadline = time.monotonic() + timeout
    last_error = "no request attempted"

    while time.monotonic() < deadline:
        try:
            with urllib.request.urlopen(url, timeout=min(interval, 5)) as response:
                payload = json.load(response)
                if response.status == 200 and payload.get("status") == "healthy":
                    return payload
                last_error = f"unexpected status={response.status} payload={payload}"
        except (urllib.error.URLError, TimeoutError, json.JSONDecodeError) as exc:
            last_error = str(exc)
        time.sleep(interval)

    raise TimeoutError(f"health endpoint did not become ready: {last_error}")


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--url", required=True)
    parser.add_argument("--timeout", type=float, default=60)
    parser.add_argument("--interval", type=float, default=2)
    args = parser.parse_args()

    try:
        payload = wait_for_health(args.url, args.timeout, args.interval)
    except TimeoutError as exc:
        print(f"health_check=failed reason={exc}", file=sys.stderr)
        return 1

    print(f"health_check=passed payload={json.dumps(payload, sort_keys=True)}")
    return 0


if __name__ == "__main__":
    raise SystemExit(main())
