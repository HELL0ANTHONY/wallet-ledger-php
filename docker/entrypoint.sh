#!/bin/sh
set -e
[ -d /workspace/vendor ] || composer install --no-interaction --prefer-dist
exec "$@"
