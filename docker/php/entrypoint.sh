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

php artisan package:discover --ansi >/dev/null

exec "$@"
