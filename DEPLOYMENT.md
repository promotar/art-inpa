# Deployment Guide

## Build Contract

`public/build` is generated output and is not committed to Git. Every production
image must contain a non-empty `public/build/manifest.json` before Laravel
starts.

The application public root is always the `public` directory inside the
repository. Laravel resolves it through the framework default
`base_path('public')`; deployment code must not call `usePublicPath()` or point
to a sibling web directory.

The repository enforces this contract in both supported deployment paths:

- Coolify with Nixpacks reads `nixpacks.toml`, installs development build
  dependencies with `npm ci`, runs `npm run build`, and fails when the manifest
  is absent.
- Docker Compose builds the `vite-assets` stage in `docker/php/Dockerfile` and
  stores its verified output in the Apache/PHP image. The runtime entrypoint copies
  that output into an empty source bind mount.

Frontend compilation never happens during container startup.

## Coolify

1. Select the **Nixpacks** build pack.
2. Leave custom Install and Build commands empty so repository
   `nixpacks.toml` remains authoritative.
3. Configure runtime environment variables, including `APP_KEY`, database,
   cache, session, queue, mail, `APP_URL`, and `PLATFORM_VERSION`.
4. Set `APP_ENV=production` and `APP_DEBUG=false`.
5. Deploy with a clean build when replacing a previously broken image.

The build log must show:

```text
npm ci --include=dev
npm run build
test -s public/build/manifest.json
```

`package.json` pins Node `22.x`; do not override it with an older Coolify
Nixpacks Node version.

For this release, set:

```text
PLATFORM_VERSION=2.5.2
```

Do not add `public/build` to Git and do not set a Coolify build command that
overrides the repository build phase.

## Docker Compose

```bash
cp .env.example .env
docker compose -f docker-compose.prod.yml build --no-cache app
docker compose -f docker-compose.prod.yml up -d
docker compose -f docker-compose.prod.yml exec app php artisan migrate --force
```

Compose contains one `app` service. Apache and PHP run in that container and
serve Laravel directly on container port `80`. MySQL remains an external shared
service on `vps-internal`. Set `QUEUE_CONNECTION=sync`; no separate queue,
scheduler, Vite, or Nginx service is part of this project.

## Verification

```bash
test -s public/build/manifest.json
curl -fsS http://127.0.0.1/login >/tmp/login.html
grep -Eo 'build/assets/[^"]+\.(css|js)' /tmp/login.html
```

Request every reported CSS/JS URL and confirm HTTP `200`.

Inside a running Laravel container, this must resolve within the source root:

```bash
php artisan tinker --execute="echo public_path('build/manifest.json');"
```

## Rollback

Redeploy the previous image or Git revision. Database rollback is unnecessary
for this frontend build contract unless the release also contains migrations.
