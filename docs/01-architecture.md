# Architecture

## Framework

Laravel is the backend framework for the platform. Laravel core, vendor files, and framework internals must remain untouched.

## Platform Layer

Platform code is reserved under `app/Platform`.

- `app/Platform/Core/Contracts`
- `app/Platform/Core/Services`
- `app/Platform/Core/Actions`
- `app/Platform/Core/DTOs`
- `app/Platform/Core/Support`
- `app/Platform/Core/Providers`
- `app/Platform/Admin`
- `app/Platform/Api`
- `app/Platform/Shared`
- `app/Platform/Theme`

## Module Layer

Functional modules are installed dynamically under `modules/`. Core must not
import a concrete module namespace.

Current source packages:

- `modules/front-builder`
- `modules/PageBuilder`

Additional distributable plugins may exist as ZIP packages until installed.

## Installation Model

Each client will initially have an independent installation. Shared patterns may be extracted only after the platform conventions are clear and documented.
