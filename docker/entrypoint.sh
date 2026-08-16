#!/usr/bin/env sh
set -eu

php-fpm -t
apache2ctl configtest

php-fpm -D

attempt=0
while [ "$attempt" -lt 20 ]; do
    if pgrep -x php-fpm >/dev/null 2>&1; then
        break
    fi
    attempt=$((attempt + 1))
    sleep 0.25
done

if ! pgrep -x php-fpm >/dev/null 2>&1; then
    echo "PHP-FPM failed to start." >&2
    exit 1
fi

exec apache2ctl -D FOREGROUND
