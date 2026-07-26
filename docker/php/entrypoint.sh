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

# Build Vite assets if manifest.json doesn't exist
if [ ! -f public/build/manifest.json ]; then
    echo ">>> Building Vite assets (missing manifest.json)..."
    # Install Node.js globally if needed - the PHP container doesn't have it by default
    command -v npm >/dev/null 2>&1 || {
        echo "    Installing Node.js v22..."
        apt-get update >/dev/null
        apt-get install -y --no-install-recommends curl >/dev/null
        curl -fsSL https://deb.nodesource.com/setup_22.x | bash - >/dev/null 2>&1
        apt-get install -y --no-install-recommends nodejs >/dev/null
        apt-get clean >/dev/null
        rm -rf /var/lib/apt/lists/* >/dev/null
        echo "    Node.js installed"
    }
    echo "    Running npm install..."
    npm install >/dev/null 2>&1 || {
        echo "    Warning: npm install had issues, continuing..."
    }
    echo "    Running npm run build..."
    npm run build >/dev/null 2>&1 || {
        echo "    Warning: npm run build had issues"
    }
    if [ -f public/build/manifest.json ]; then
        echo "    ✓ Vite assets built successfully"
    else
        echo "    ! Warning: manifest.json not found after build"
    fi
fi

php artisan package:discover --ansi >/dev/null

exec "$@"
