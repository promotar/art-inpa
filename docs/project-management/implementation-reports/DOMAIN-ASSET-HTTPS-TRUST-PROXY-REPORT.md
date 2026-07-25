# Domain Asset HTTPS Trust Proxy Report

Date: 2026-07-01

## Objective

Fix CSS/JS not loading correctly when the Laravel site is opened through the HTTPS domain, without restructuring the project or changing application logic.

## Root Cause

The Laravel application is behind a reverse proxy. Requests to `https://staging.art-inpa.com` reached Apache/Laravel over internal HTTP, but Laravel was not configured to trust the reverse proxy `X-Forwarded-*` headers.

Because of that, Laravel generated plugin asset URLs as:

```text
http://staging.art-inpa.com/platform/plugins/...
```

On the HTTPS domain this creates browser mixed-content blocking, so CSS/JS appears broken. The same assets worked from the IP because the IP was loaded over plain HTTP.

## Findings

- `.env`:
  - `APP_URL=https://staging.art-inpa.com`
  - `ASSET_URL` not set
  - no `VITE_*` values found
- Domain check:
  - `https://art-inpa.com` currently returns a WordPress/LiteSpeed site, not this Laravel app.
  - `https://staging.art-inpa.com` reaches this Laravel app through Caddy.
- Apache document root:
  - `/var/www/store.z4rank.com/public_html`
  - not the Laravel project root
  - `public_html/index.php` bootstraps `../laravel/vendor/autoload.php` and `../laravel/bootstrap/app.php`
- Vite build:
  - `public/build/manifest.json` exists
  - `public_html/build/manifest.json` exists
  - build assets exist
- Asset failure:
  - before fix: domain HTML emitted HTTP asset URLs
  - after fix: domain HTML emits HTTPS asset URLs and each asset returns 200

## Change Made

Updated:

```text
bootstrap/app.php
```

Added Laravel 12 proxy trust configuration for the internal proxy addresses observed in Apache logs:

```text
127.0.0.1
::1
10.10.0.2
192.168.1.195
```

The trusted headers are:

```text
X-Forwarded-For
X-Forwarded-Host
X-Forwarded-Port
X-Forwarded-Proto
X-Forwarded-Prefix
```

## Commands Run

```text
php -l bootstrap/app.php
php artisan optimize:clear --no-ansi
php artisan config:clear --no-ansi
php artisan view:clear --no-ansi
php artisan config:cache --no-ansi
php artisan route:cache --no-ansi
php artisan view:cache --no-ansi
```

## Verification

After the fix, the domain HTML emits:

```text
https://staging.art-inpa.com/platform/plugins/art-inpa-front-news-theme/css/art-inpa-front-news-theme.css
https://staging.art-inpa.com/platform/plugins/blog/css/blog.css
https://staging.art-inpa.com/platform/plugins/art-inpa-front-news-theme/js/art-inpa-front-news-theme.js
https://staging.art-inpa.com/platform/plugins/ai-assistant/css/ai-assistant.css
https://staging.art-inpa.com/platform/plugins/ai-assistant/js/ai-assistant.js
```

All tested assets returned `HTTP/2 200` with the correct CSS/JS content type.

## Backup

```text
/root/codex-backups/domain-asset-proxy-20260701-011340
```

## Rollback

Restore:

```text
/root/codex-backups/domain-asset-proxy-20260701-011340/app.php
```

to:

```text
/var/www/store.z4rank.com/laravel/bootstrap/app.php
```

Then rebuild Laravel caches.
