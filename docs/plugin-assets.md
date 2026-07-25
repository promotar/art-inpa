# Plugin Asset Contract

Platform 2.2 loads plugin and theme assets through
`App\Platform\Core\Services\PluginAssetRegistry`.

## Manifest

Declare assets in `module.json`:

```json
{
  "type": "theme",
  "assets": {
    "source": "resources/assets",
    "admin": {
      "styles": ["css/admin.css"],
      "scripts": ["js/admin.js"]
    },
    "frontend": {
      "styles": ["css/frontend.css"],
      "scripts": ["js/frontend.js"]
    },
    "guest": {
      "styles": ["css/auth.css"],
      "scripts": []
    }
  }
}
```

Paths must be relative, remain inside `assets.source`, and match their declared
extension. Core publishes them to:

```text
public/platform/plugins/{plugin-slug}/{declared-path}
```

## Runtime Rules

- Only active plugins approved by `PluginRuntimeGate` contribute assets.
- Disabling a plugin removes all of its tags on the next request.
- Feature and service assets load before theme assets.
- A numeric `assets.priority` or `assets.{scope}.priority` overrides the default
  ordering when required.
- Missing or changed declared files are republished from the resolved local
  package path.
- Theme Editor CSS overrides load immediately after the matching plugin CSS.
- Core layouts include the shared `platform.plugin-assets` partial; plugins must
  never edit core layouts to inject their own files.

## Lifecycle

- Install: publishes from the installer-resolved package path.
- Activate/reactivate: registers canonical manifest metadata and republishes.
- Disable: runtime tags disappear; files remain for non-destructive reactivation.
- Uninstall: validates the complete managed directory tree before deletion,
  removes published assets, then continues the remaining lifecycle steps.
- Asset files are replaced atomically. A request never reads a partially copied
  CSS or JavaScript file.
- Managed directories use mode `0775` and managed files use mode `0664`, with
  group ownership configured by `PLATFORM_ASSET_GROUP` (`www-data` by default).
  CLI commands may run as `root`, while admin HTTP lifecycle actions run as the
  shared group, without granting world write access.
- Publisher, remover, and install rollback mutations are centralized in
  `ManagedAssetFilesystem`; plugins must not delete public assets themselves.

Repair assets created before this contract, then synchronize all packages:

```powershell
docker compose exec -T app php artisan platform:repair-plugin-assets
docker compose exec -T app php artisan platform:sync-plugin-assets --all
```

The repair command is bounded to `public/platform/plugins`. Do not apply a
recursive ownership change to the project bind mount.

## Verification

```powershell
docker compose exec -T app ./vendor/bin/phpunit tests/Feature/PluginAssetRegistryTest.php
docker compose exec -T app ./vendor/bin/phpunit tests/Feature/PluginInstallUninstallContractTest.php
docker compose exec -T vite npm run build
```
