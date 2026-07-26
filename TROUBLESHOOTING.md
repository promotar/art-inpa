# Quick Troubleshooting for Vite Manifest Error

## Problem
```
Illuminate\Foundation\ViteManifestNotFoundException
Vite manifest not found at: /app/../public_html/build/manifest.json
```

## Quick Fix

### If using Docker (Recommended)
```bash
# Just restart the container - it will auto-build assets
docker compose down
docker compose up -d --build

# Or just rebuild:
docker compose restart app
```

### If building locally (with Node.js installed)
```bash
npm install
npm run build
```

### If building in Docker container
```bash
docker compose exec app npm install
docker compose exec app npm run build
```

### Verify it worked
```bash
# Check if manifest.json exists
ls -la public/build/manifest.json
docker compose exec app ls -la public/build/manifest.json

# Check app logs
docker compose logs app | grep -i vite
docker compose logs app | grep -i build
```

## Why This Happens

- `/public/build` is in `.gitignore` because it's generated, not source code
- When cloning from GitHub, this directory doesn't exist
- Vite needs `manifest.json` to map assets in production
- Solution: The app automatically builds it on first startup

## If Auto-Build Fails

1. **Check logs**: `docker compose logs app`
2. **Check disk space**: `docker exec art-inpa-app df -h`
3. **Check memory**: `docker stats`
4. **Manual build**:
   ```bash
   docker compose exec app apt-get update
   docker compose exec app apt-get install -y nodejs npm
   docker compose exec app npm install
   docker compose exec app npm run build
   ```

## For Development

Use `docker compose up -d` - the Vite container runs `npm run dev` automatically.

## For Production

- Auto-build on startup: Just start the container
- Or pre-build locally and commit:
  ```bash
  npm run build
  git add public/build/
  git commit -m "Build assets"
  git push
  ```

---

See [SETUP_GUIDE.md](SETUP_GUIDE.md) for complete documentation.
