#!/bin/sh
set -eu

cd /var/www/html
umask 0002

mkdir -p \
    storage/app/public \
    storage/app/public/settings \
    storage/app/plugin_uploads \
    storage/app/plugin_uploads/tmp \
    storage/app/plugin_uploads/extracted \
    storage/app/plugin_uploads/pending_updates \
    storage/app/platform \
    storage/app/platform/backup-checkpoints \
    storage/app/platform/plugin-install-checkpoints \
    storage/app/platform/plugin-uninstall-checkpoints \
    storage/app/platform/update-checkpoints \
    storage/app/platform/update-logs \
    storage/app/plugin_updates/backups \
    storage/app/plugin_uninstalls/removed_modules \
    storage/app/theme-overrides/core \
    storage/app/theme-overrides/namespaces/core \
    storage/app/theme-overrides/plugins \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chgrp www-data \
    storage/app/public \
    storage/app/public/settings \
    storage/app/platform \
    storage/app/platform/backup-checkpoints \
    storage/app/platform/plugin-install-checkpoints \
    storage/app/platform/plugin-uninstall-checkpoints \
    storage/app/platform/update-checkpoints \
    storage/app/platform/update-logs \
    storage/app/plugin_updates \
    storage/app/plugin_updates/backups \
    storage/app/plugin_uninstalls \
    storage/app/plugin_uninstalls/removed_modules \
    storage/app/theme-overrides \
    storage/app/theme-overrides/core \
    storage/app/theme-overrides/namespaces \
    storage/app/theme-overrides/namespaces/core \
    storage/app/theme-overrides/plugins \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod 2775 \
    storage/app/public \
    storage/app/public/settings \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

chmod 2770 \
    storage/app/platform \
    storage/app/platform/backup-checkpoints \
    storage/app/platform/plugin-install-checkpoints \
    storage/app/platform/plugin-uninstall-checkpoints \
    storage/app/platform/update-checkpoints \
    storage/app/platform/update-logs \
    storage/app/plugin_updates \
    storage/app/plugin_updates/backups \
    storage/app/plugin_uninstalls \
    storage/app/plugin_uninstalls/removed_modules \
    storage/app/theme-overrides \
    storage/app/theme-overrides/core \
    storage/app/theme-overrides/namespaces \
    storage/app/theme-overrides/namespaces/core \
    storage/app/theme-overrides/plugins

chgrp www-data \
    storage/app/plugin_uploads \
    storage/app/plugin_uploads/tmp \
    storage/app/plugin_uploads/extracted \
    storage/app/plugin_uploads/pending_updates

chmod 2770 \
    storage/app/plugin_uploads \
    storage/app/plugin_uploads/tmp \
    storage/app/plugin_uploads/extracted \
    storage/app/plugin_uploads/pending_updates

if [ ! -f vendor/autoload.php ]; then
    composer install --no-interaction --prefer-dist
fi

# Source bind mounts hide files baked into /var/www/html. Restore the verified
# build artifact from the immutable image when the mounted tree is empty.
if [ ! -s public/build/manifest.json ] && [ -s /opt/art-inpa/public/build/manifest.json ]; then
    echo ">>> Restoring prebuilt Vite assets from the container image..."
    mkdir -p public/build
    cp -R /opt/art-inpa/public/build/. public/build/
fi

if [ ! -s public/build/manifest.json ]; then
    echo "ERROR: Vite manifest is missing. The deployment image is incomplete." >&2
    echo "Rebuild the image from this repository; do not start Laravel without the frontend build stage." >&2
    exit 1
fi

INSTALLATION_FLAG="${INSTAAL_IS_ACTIVE:-${INSTAAL_IS_ATIVE:-0}}"

if [ -z "${APP_KEY:-}" ] && [ -r storage/app/platform/installation.env ]; then
    APP_KEY="$(php -r '
        $content = (string) file_get_contents("/var/www/html/storage/app/platform/installation.env");
        if (preg_match("/^APP_KEY=(.*)$/m", $content, $match) === 1) {
            echo trim(trim($match[1]), "\"\x27");
        }
    ')"

    if [ -n "$APP_KEY" ]; then
        export APP_KEY
    fi
fi

if [ -z "${APP_KEY:-}" ] && [ "$INSTALLATION_FLAG" != "1" ]; then
    echo ">>> Generating the first-run Laravel application key..."
    APP_KEY="$(php -r 'echo "base64:".base64_encode(random_bytes(32));')"
    export APP_KEY

    php -r '
        $key = getenv("APP_KEY");
        $write = static function (string $path, bool $protect = false) use ($key): void {
            $content = is_file($path) ? (string) file_get_contents($path) : "";
            $line = "APP_KEY=\"".str_replace(["\\", "\"", "\n", "\r"], ["\\\\", "\\\"", "", ""], $key)."\"";
            $pattern = "/^APP_KEY=.*$/m";
            $content = preg_match($pattern, $content)
                ? (string) preg_replace($pattern, $line, $content)
                : rtrim($content).PHP_EOL.$line.PHP_EOL;
            file_put_contents($path, ltrim($content), LOCK_EX);
            if ($protect) {
                @chmod($path, 0660);
            }
        };

        $runtime = "/var/www/html/storage/app/platform/installation.env";
        $write($runtime, true);

        $environment = "/var/www/html/.env";
        if (is_file($environment) && is_writable($environment)) {
            $write($environment);
        }
    '
fi

if [ -z "${APP_KEY:-}" ] && [ "$INSTALLATION_FLAG" = "1" ]; then
    echo "ERROR: The platform is marked as installed but APP_KEY is missing." >&2
    echo "Restore the original persistent APP_KEY; generating a replacement would invalidate encrypted data." >&2
    exit 1
fi

php artisan package:discover --ansi >/dev/null

exec "$@"
