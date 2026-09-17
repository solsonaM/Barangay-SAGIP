#!/bin/sh
set -eu

url="http://127.0.0.1${HEALTHCHECK_PATH:-/up}"

if wget -q -O /dev/null --timeout=5 "$url"; then
    exit 0
fi

exit 1
