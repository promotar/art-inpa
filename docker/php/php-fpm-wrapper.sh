#!/bin/sh
set -eu

if [ "${1:-}" = "-l" ]; then
    exec /usr/local/bin/php "$@"
fi

exec /usr/local/sbin/php-fpm-real "$@"

