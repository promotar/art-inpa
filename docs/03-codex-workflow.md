# Codex Workflow

## Working Rules

- Keep implementation aligned with the current phase.
- Do not build future-phase features early.
- Document every implementation task.
- Use Git from the beginning.
- Keep generated structure easy to review.

## Change Tracking

For each task:

- Update `IMPLEMENTATION_LOG.md`.
- Update `DECISIONS.md` when decisions are made.
- Update `CHANGELOG.md` when notable changes occur.
- Avoid unrelated refactors.

## Protected Areas

Do not modify:

- Laravel core files.
- `vendor/`.
- Framework internals.

## Phase 0 Scope

Phase 0 is setup only. It creates the Laravel app, documentation, and empty directory structure.
