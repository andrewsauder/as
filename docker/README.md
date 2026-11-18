# Deploy an AS application from gcgov/deploy

AS applications run on IIS today. This folder builds an AS application as the two images that
`gcgov/deploy` deploys for an api: `php` and `nginx`. `gcgov/deploy`'s `build.yml` builds them with
`kind: api`, and its deploy flow runs them like any `gcgov/framework` api.

The application repository holds no copy of the PHP or nginx settings. They come from the AS
submodule, so an AS update reaches every application at its next build.

## What this folder holds

| File | Purpose |
|---|---|
| `Dockerfile` | The build. Copy it to the application root as `Dockerfile`. |
| `dockerignore` | Keeps secrets and local files out of the build. Copy it to the application root as `.dockerignore`. |
| `build-release.sh` | The steps `update-production.ps1` runs on IIS, for Linux. The Dockerfile runs it. |
| `php/conf.d/as.ini` | The PHP settings. They replace the `var/srv/app.<environment>/php.ini` files. |
| `php/php-fpm.d/zz-as.conf` | The PHP-FPM pool. |
| `nginx/default.conf.template` | The nginx site. It replaces the IIS `web.config`. |
| `nginx/as-fastcgi-params` | What nginx tells PHP about each request. |
| `nginx/40-as-render-config.sh` | Writes the nginx site at container start from `PHP_FPM_HOST` and `AS_BASE_PATH`. |
| `compose.example.yaml` | An example `gcgov/deploy` entry. It passes that repository's compose checks. |

## Prepare the application repository

**Run in the application's checkout**, from its root:

```bash
cp AS/docker/Dockerfile Dockerfile
```

```bash
cp AS/docker/dockerignore .dockerignore
```

Then make these changes:

1. Edit the two lists at the top of `Dockerfile`. `AS_PHP_EXTENSIONS` names the PHP extensions the
   application uses. `AS_WRITABLE_DIRS` names every directory the application writes while it
   runs, such as `www/receipts`. The rest of the image is read-only to the application.
2. Add `.github/workflows/release.yml`. It calls `gcgov/deploy`'s `build.yml` with `kind: api`.
   Part 3 of `docs/runbooks/add-an-application.md` in `gcgov/deploy` shows the file and the
   `OPS_DISPATCH_TOKEN` secret it needs.
3. Check `var/config.xml`. The environment the container runs, usually `prod`, needs a `<srv>`
   whose `<server_name>` is the host name Traefik routes to the application. Its `<base_url>`
   includes the path, such as `https://apps.garrettcountymd.gov/landfill`.
4. Give that `<srv>` Linux values where it holds Windows paths. `<phppath>` is
   `/usr/local/bin/php`. `<cronpath>` is `/var/www/app/app/cron/`, with the trailing slash.
   `<tmppath>` is `/var/www/app/var/tmp`.
5. Commit `composer.lock`. Without it, each build installs the newest packages that
   `composer.json` allows, as `update-production.ps1` did.

The application keeps its secrets in `var/config.local.xml`, which git does not track. Nothing in
the build reads that file. `build-release.sh` stops the build when it finds the file in the build
context, because every image layer would keep a copy.

## Prepare the gcgov/deploy entry

`compose.example.yaml` is the starting point for `zones/<zone>/apps/<app>/compose.yaml`.
Replace `as-app-template` with the application's slug, and `/template` with its path.

The secrets file is one key in `zones/<zone>/apps/<app>/secrets.sops.yaml`. The key is
`config.local.xml`, and the value is the whole file:

```yaml
config.local.xml: |
    <config>
        <environment>
            <prod>
                ...
            </prod>
        </environment>
    </config>
```

`bin/provision` writes it to `/etc/gcgov/secrets/<app>/config.local.xml`. The compose file mounts
that directory at `/run/secrets/<app>`, and `AS_CONFIG_LOCAL_FILE` names the file.

## Settings the containers read

Each setting is an environment variable in the compose file. On IIS none of them is set, and AS
behaves as it did before.

| Variable | Container | Meaning |
|---|---|---|
| `AS_CONFIG_LOCAL_FILE` | `php` | The path of the secrets file, which AS merges over `var/config.xml` in place of `var/config.local.xml`. The file must exist, be readable and be valid XML, or every request fails and the health check fails. |
| `AS_SESSION_NAME` | `php` | The session cookie name: 1 to 64 letters and digits. Every AS application on one host name needs its own, because the cookie path is `/`. Required in a container, where AS cannot write its session name file. |
| `AS_ENVIRONMENT` | `php` | The `<environment>` in `var/config.xml` to run, such as `prod`. AS matches the host name only against that environment's `<srv>` entries and refuses any other host with HTTP 421. |
| `AS_SMTP_HOST`, `AS_SMTP_PORT` | `php` | An SMTP relay, for an application with no `<email><smtp>` in `var/config.xml`. The port defaults to 25. AS sends without a password. |
| `PHP_FPM_HOST` | `nginx` | The `php` service as `host:port`. Defaults to `php:9000`. |
| `AS_BASE_PATH` | `nginx` | The path the application answers under, such as `/landfill`. Empty for an application at the root of its own host name. |

Set `AS_ENVIRONMENT` in every container. Without it, a host name that matches no `<srv>` runs in
local testing mode. Some application templates print the whole session in a page in testing mode,
and the session holds the merged configuration, secrets included.

## What replaces each IIS piece

| On IIS | In the containers |
|---|---|
| `www/web-<environment>.config` rewrite rules | `nginx/default.conf.template`. Files under `www/` are served as they are. Every other path goes to `index.php` as `R0`. PHP files, `.config` files and dot files are never sent as files. |
| HTTPS on IIS | Traefik ends TLS. nginx passes `HTTPS=on` and port 443 to PHP when Traefik forwarded an HTTPS request, so `getFullURL()` builds the same URLs. |
| `var/srv/app.<environment>/php.ini` | `php/conf.d/as.ini`. Errors go to the container's output. |
| `update-production.ps1` | `build-release.sh`: the environment's files, `composer install`, and `version.json`. |
| `AS/bin/session-name.<host>.as` | `AS_SESSION_NAME`. |
| `mail()` through the `SMTP` host in `php.ini` | `AS_SMTP_HOST`. |
| `tools::createCron()` with a `.bat` file | The same call starts the job in the background on Linux. |
| PHP error log files under `var/srv/logs` | The container's output, which `gcgov/deploy` keeps in the host's journal. |

## Health

nginx answers `/healthz` by running `AS/bin/health.php` in the `php` container. The `nginx` image's
health check calls it, and so does Traefik. A deploy succeeds only when it passes. It checks:

- `var/config.xml` is valid and names the application.
- The secrets file from `AS_CONFIG_LOCAL_FILE` exists, is readable and is valid XML.
- An application that ships `var/config.local.example.xml` has `AS_CONFIG_LOCAL_FILE` set.
- The environment that `AS_ENVIRONMENT` names has a `<srv>`.
- The session name is valid and stays the same on every request.
- PHP can write the output cache and the session directory.

It does no network I/O, so a database outage does not take the application out of service. The
response says `ok` or `unavailable`. Each reason goes to the log, as a line that starts with
`AS health:`.

`/healthz` sits outside `AS_BASE_PATH`, so a router rule that matches only the application's path
keeps it off the internet. An application at the root of its own host name needs
``&& !Path(`/healthz`)`` in its router rule instead.

## Build the images by hand

**Run in the application's checkout**, after `git submodule update --init`:

```bash
docker build --target php -t <app>-php --build-arg APP_VERSION=$(git describe --tags --always) .
```

```bash
docker build --target nginx -t <app>-nginx .
```

## What gcgov/deploy needs

The changes here cover AS alone. Two things in `gcgov/deploy` decide whether an AS application
builds and runs there:

- **Submodules.** `build.yml` checks out the application without its submodules, so `AS/` is empty
  and the build stops at the first `COPY AS/docker/...`. The checkout step needs
  `submodules: recursive`. A private submodule also needs a token that can read it.
- **Scheduled jobs.** The Windows Task Scheduler starts the `app/cron/` scripts on IIS.
  `gcgov/deploy` has nothing that starts a job on a schedule, so these jobs need a design there.

## Limits

- A deploy or a restart signs every user out, because sessions live inside the `php` container.
  AS keeps its merged configuration, secrets included, in the session. Sessions that outlived a
  rotated secret would keep the old value.
- A directory that the application writes and nginx serves, such as `www/receipts`, needs a named
  volume in both services. `compose.example.yaml` shows it.
- Linux file names are case-sensitive. An `include` whose case differs from the file on disk
  works on IIS and fails in the container.
- `getProcessIDTree()` uses `wmic`, which exists only on Windows. Pervasive ODBC drivers have no
  Linux build.
