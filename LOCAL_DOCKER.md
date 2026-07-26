# Art INPA Local Docker

## Runtime

The project runs as one Laravel application service:

- `art-inpa-app`: Apache 2 + PHP 8.2 + Laravel on `http://localhost:8088`
- `vps-mysql`: shared infrastructure outside this Compose project

The application joins the external `vps-internal` network. This project does
not create Nginx, Vite, queue, scheduler, cache, or database containers.

Queue work uses `QUEUE_CONNECTION=sync`, so dispatched jobs execute in the web
request instead of waiting for a missing worker. The repository currently has
no scheduled application tasks.

## Commands

```powershell
cd D:\VPS\art-inpa
docker compose up -d --build
docker compose ps
docker compose logs -f app
docker compose exec app php artisan about
docker compose exec app php artisan route:cache
docker compose down
```

Run `php artisan route:cache` again after changing core or plugin routes.

## Frontend Assets

The Dockerfile has a Node build stage that runs `npm ci` and `npm run build`.
Only its verified `public/build` output is copied into the final Apache/PHP
image. The running container does not include or start Node/Vite.

The source bind mount can hide image files, so the entrypoint restores the
immutable build artifact when `public/build/manifest.json` is absent. An image
without a valid manifest exits before Laravel starts.

For active frontend development, run `npm run dev` directly from the project
workspace. It is a development command, not a Docker service.

## Data And Secrets

- MySQL remains in the shared `vps-mysql` container.
- The active local `.env` is owner-only and excluded from Git.
- Plugin packages and uploaded files remain in this repository bind mount.
- Recovery points are recorded under `backups\` and in `backups-log.txt`.
