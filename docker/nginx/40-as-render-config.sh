#!/bin/sh
#
# Writes /etc/nginx/conf.d/default.conf from the AS site template at container start.
# The nginx image's entrypoint runs every script in /docker-entrypoint.d/ before nginx.
#
# It reads two variables:
#   PHP_FPM_HOST   the php service as host:port, such as php:9000
#   AS_BASE_PATH   the path the application answers under, such as /landfill, or empty
#                  when it answers at the root of its host name
#
# A bad value stops the container, so a deploy fails at its health gate instead of
# serving the wrong paths.

set -eu

fail() {
	echo "$0: $*" >&2
	exit 1
}

PHP_FPM_HOST="${PHP_FPM_HOST:-php:9000}"
AS_BASE_PATH="${AS_BASE_PATH:-}"

printf '%s' "$PHP_FPM_HOST" | grep -Eq '^[A-Za-z0-9._-]+:[0-9]{1,5}$' \
	|| fail "PHP_FPM_HOST must be host:port, such as php:9000"

AS_BASE_LOCATIONS=''
if [ -n "$AS_BASE_PATH" ]; then
	printf '%s' "$AS_BASE_PATH" | grep -Eq '^(/[A-Za-z0-9._~-]+)+$' \
		|| fail "AS_BASE_PATH must be empty or a path such as /landfill, with no trailing slash"
	if printf '%s' "$AS_BASE_PATH" | grep -Eq '(^|/)\.\.?(/|$)'; then
		fail "AS_BASE_PATH must not contain . or .. as a path segment"
	fi
	# /landfill goes to /landfill/. A path outside the base path is not this application's.
	AS_BASE_LOCATIONS="
    location = $AS_BASE_PATH {
        return 301 $AS_BASE_PATH/\$is_args\$args;
    }

    location / {
        return 404;
    }"
fi

# The base path as a regular expression: a dot is the only character it allows that has a
# meaning in one.
AS_BASE_PATH_RE="$(printf '%s' "$AS_BASE_PATH" | sed 's/\./\\./g')"

export PHP_FPM_HOST AS_BASE_PATH AS_BASE_PATH_RE AS_BASE_LOCATIONS
# The names stay in single quotes: envsubst takes the list of names to replace, unexpanded.
# shellcheck disable=SC2016
envsubst '${PHP_FPM_HOST} ${AS_BASE_PATH} ${AS_BASE_PATH_RE} ${AS_BASE_LOCATIONS}' \
	< /etc/nginx/as/default.conf.template \
	> /etc/nginx/conf.d/default.conf

echo "$0: serving the application at '${AS_BASE_PATH:-/}' through $PHP_FPM_HOST"
