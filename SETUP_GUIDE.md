# Setup Guide

## Local Development

```bash
cp .env.example .env
docker compose up -d --build
docker compose exec app php artisan key:generate
docker compose exec app php artisan migrate --force
```

The local `vite` service runs `npm run dev`. Its `public/hot` file is local-only
and excluded from Git and Docker image contexts.

## Production Assets

Laravel `@vite` requires `public/build/manifest.json` when no Vite development
server is active. The manifest is generated during deployment:

- `nixpacks.toml` is the source of truth for Coolify/Nixpacks.
- `package.json` pins the frontend builder to Node `22.x`.
- `docker/php/Dockerfile` is the source of truth for Docker Compose images.
- `docker/php/entrypoint.sh` only restores assets already built into the image.

The runtime image intentionally does not contain npm and does not download
Node.js. A missing build artifact stops startup with an actionable error.

Laravel, Nginx, plugin assets, and Vite all use the repository `public`
directory. There is no external or sibling public directory.

## Manual Source Verification

```bash
npm ci --include=dev
npm run build
test -s public/build/manifest.json
```

This verifies source code but does not replace the image build during
deployment.

## Coolify Requirements

- Build pack: `Nixpacks`
- Repository root as the base directory
- No custom Build command overriding `nixpacks.toml`
- Production `APP_KEY` and database variables
- `APP_ENV=production`
- `APP_DEBUG=false`

After deployment, verify `/login` and the CSS/JS files emitted by `@vite`.
