# Module Structure

## Current Modules

- `modules/front-builder`
- `modules/PageBuilder`

Optional plugins are self-contained packages and are absent from runtime when
uninstalled. Core does not retain their controllers, commands, routes, models,
or tests.

## Future Structure

Future phases should define a standard module layout before adding functionality. Possible future concerns include:

- Module service providers.
- Routes.
- Controllers.
- Requests.
- Models.
- Migrations.
- Views.
- Policies and permissions.
- Tests.

The mandatory package and ownership contract is documented in
`docs/plugin-architecture.md` and `docs/plugin-lifecycle-contract.md`.
