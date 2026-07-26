# Art INPA Local Docker

## Location

The complete local development copy is stored in `D:\VPS\art-inpa`.

## Architecture

- `art-inpa-web`: Nginx on `http://localhost:8088`
- `art-inpa-app`: PHP 8.2 FPM / Laravel behind Nginx
- `art-inpa-queue`: Laravel database queue worker
- `art-inpa-scheduler`: Laravel scheduler runner
- `art-inpa-vite`: Vite development server on `http://localhost:5173`
- `vps-mysql`: existing shared MySQL 8.4 container; no database container is created by this project

The application joins the existing external Docker network `vps-internal` and connects to the dedicated `art_inpa_local_dev` database.

The container mounts a local-only `PluginRouteLoader` compatibility override so route syntax checks use PHP CLI under FPM. The application core file on the host is not modified.

Vite listens inside Docker on `0.0.0.0`, but `vite.config.js` pins the browser-facing origin and HMR host to `localhost`. This keeps Laravel's `public/hot` file usable from the Windows browser and prevents pages such as `/login` from loading unstyled.

## Commands

```powershell
cd D:\VPS\art-inpa
docker compose up -d --build
docker compose ps
docker compose logs -f app web queue
docker compose exec app php artisan about
docker compose exec app php artisan route:cache
docker compose down
```

Run `php artisan route:cache` again after adding or changing plugin routes. Use `php artisan route:clear` temporarily when actively debugging route registration.

## Vite Assets Build

The generated `/public/build` directory is excluded from Git and Docker build
contexts. Deployment must create it while building the image:

1. **Local development**: `art-inpa-vite` runs the Vite development server and
   writes `public/hot`.
2. **Docker image**: the `vite-assets` stage runs `npm ci` and `npm run build`.
   Its verified output is stored at `/opt/art-inpa/public/build`.
3. **Source bind mount**: the PHP entrypoint restores that immutable artifact
   when the bind-mounted `public/build/manifest.json` is absent.
4. **Coolify/Nixpacks**: `nixpacks.toml` runs the same build and fails the image
   build when the manifest is missing.

Production containers never install Node.js or compile frontend assets during
startup. An image without a manifest exits immediately instead of serving a
Laravel `ViteManifestNotFoundException`.

## Backups

- The untouched live environment backup is `.env.live.backup`.
- Source and database archives are in `backups\`.
- Local runtime credentials are recorded in `passwords.txt` and are excluded from Git.

Never use `.env.live.backup` as the active local `.env` without reviewing all production integrations first.
