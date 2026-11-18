#!/bin/sh
#
# Prepares an AS application inside the image build. These are the steps
# update-production.ps1 runs on an IIS server, for Linux:
#
#   1. refuse a build context that holds the secrets file
#   2. copy the environment's files over the defaults
#   3. install the Composer dependencies
#   4. write version.json
#
# The Dockerfile runs it from the application root, as root, in the build stage only.
#
# Usage: sh AS/docker/build-release.sh <environment> <version>

set -eu

environment="${1:?usage: build-release.sh <environment> <version>}"
version="${2:-unknown}"

fail() {
	echo "build-release.sh: $*" >&2
	exit 1
}

case "$environment" in
	''|*[!a-z0-9-]*) fail "the environment '$environment' must be lowercase letters, digits and hyphens, such as prod" ;;
esac

# 1. Every layer of an image keeps a copy of what was copied into it, and anyone who can
# pull the image can read it. The secrets reach a container as a mounted file instead.
if [ -e var/config.local.xml ]; then
	fail "var/config.local.xml is in the build context. It holds secrets. Add it to .dockerignore: copy AS/docker/dockerignore to .dockerignore."
fi

# A session name file pins one host's session name into every container. AS_SESSION_NAME
# sets the name in a container.
rm -f AS/bin/session-name.*.as

# 2. The environment's files, as update-production.ps1 copies them. IIS's
# www/web-<environment>.config has no use here: the nginx image replaces it.
if [ -f "app/config/environment-$environment.json" ]; then
	echo "build-release.sh: app/config/environment-$environment.json -> app/config/environment.json"
	cp "app/config/environment-$environment.json" app/config/environment.json
fi
if [ -f "composer-$environment.json" ]; then
	echo "build-release.sh: composer-$environment.json -> composer.json"
	cp "composer-$environment.json" composer.json
fi

# 3. Without a committed composer.lock, this resolves the newest versions composer.json
# allows, as update-production.ps1's `composer update` did. Commit composer.lock to build
# the same dependencies every time.
if [ -f composer.json ]; then
	composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
fi

# 4. The same shape update-production.ps1 writes. The release workflow passes the tag, or
# sha-<commit> for a build that is not a tag.
case "$version" in
	''|*[!A-Za-z0-9._+-]*) version=unknown ;;
esac
printf '{\n    "app": "%s",\n    "inherit": true\n}\n' "$version" > version.json
echo "build-release.sh: version $version"
