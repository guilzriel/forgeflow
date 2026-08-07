import json
import threading
from http.server import BaseHTTPRequestHandler, HTTPServer

from scripts.wait_for_health import wait_for_health


class HealthHandler(BaseHTTPRequestHandler):
    def do_GET(self) -> None:  # noqa: N802
        payload = json.dumps({"status": "healthy"}).encode()
        self.send_response(200)
        self.send_header("Content-Type", "application/json")
        self.send_header("Content-Length", str(len(payload)))
        self.end_headers()
        self.wfile.write(payload)

    def log_message(self, format: str, *args: object) -> None:
        return


def test_wait_for_health_returns_payload() -> None:
    server = HTTPServer(("127.0.0.1", 0), HealthHandler)
    thread = threading.Thread(target=server.serve_forever, daemon=True)
    thread.start()

    try:
        payload = wait_for_health(
            f"http://127.0.0.1:{server.server_port}/health",
            timeout=2,
            interval=0.05,
        )
    finally:
        server.shutdown()
        thread.join(timeout=2)

    assert payload == {"status": "healthy"}
