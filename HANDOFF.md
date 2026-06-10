# SQL Mig Handoff

This directory is intended to become the root of the future `peterivan/sql-mig` repository.

## Current State

- Package identity is `peterivan/sql-mig`.
- PHP namespace is `PeterIvan\SqlMig`.
- Runtime baseline is PHP `>=8.5`.
- Symfony Console is constrained to `^8.1` and locked at `v8.1.0`.
- CLI entry point is `bin/sql-mig`.
- Implemented commands:
  - `php bin/sql-mig validate`
  - `php bin/sql-mig apply`
- No tests are intentionally present yet.
- Local test environment uses FrankenPHP and PostgreSQL via root `Dockerfile` and `docker-compose.yaml`.
- Static-analysis/lint/format tooling is wired through Composer scripts.
- All project commands should be run inside the app container.
- ADRs use a compact project-specific format in `docs/adr/`.

## Local Environment

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app composer check
docker compose exec app php bin/sql-mig validate
docker compose exec app php bin/sql-mig apply
```

Default local database URL:

```text
postgresql://sql_mig:sql_mig@database:5432/sql_mig
```

PostgreSQL is exposed to the host on port `54329`.

The app image is Alpine-based `dunglas/frankenphp:1-php8.5-alpine`.
Mago downloads its `x86_64-unknown-linux-musl` binary on first use in the container.

The app image copies Composer from the official `composer:2` image and installs only:

- `postgresql-client`
- `unzip`

`git` is intentionally not installed in the app image.

## Implemented Core

- Config resolution from CLI flags, then env, then defaults.
- Migration discovery from `database/migrations/*.sql`.
- Version token parsing:
  - `YYYY-MM-DD_HHMM_description.sql`
  - `YYYY-MM-DD_HHMM_NN_description.sql`
- Non-sequenced migrations sort as sequence `0`.
- Explicit `_00` sequence is rejected.
- SHA-256 checksum calculation from original file content.
- Database URL normalization for Doctrine/Symfony-style PostgreSQL URLs.
- Transaction-boundary validation through a lightweight tokenizer.
- Transaction validation currently requires one top-level explicit transaction per migration:
  - first top-level statement must be `BEGIN`
  - last top-level statement must be `COMMIT`
  - additional top-level `BEGIN`/`COMMIT` blocks inside the migration are rejected
  - PL/pgSQL `begin`/`end` blocks and savepoints are not treated as top-level transaction blocks
- Audit insert injection before the final top-level `COMMIT`, using the same tokenizer position as validation.
- Audit table creation for `apply`.
- `validate` treats a missing audit table as empty history.
- `psql` execution with `ON_ERROR_STOP=1`.
- PHPStan and Mago tooling via `composer analyse`, `composer check`, and `composer mago:format` inside the app container.
- `composer check` currently passes inside the app container.
- PHPStan runs without `--debug` in the container and uses `--memory-limit=512M`.

## Known Gaps

- Advisory locking is not wired into the runner yet.
- Current `AdvisoryLock` class is not enough by itself because PostgreSQL advisory locks require a persistent database session; separate `psql` invocations release session locks immediately.
- `executionTimeMs` in audit metadata is currently written as `0`.
- No tests or CI have been added.
- No Symfony bundle wrapper migration has been done yet.
- No `status`, `history`, `--dry-run`, `--format=json`, lock timeout, or baseline command yet.
- `database/migrations/` currently contains imported real-system legacy migrations using names such as `000 - Extensions.sql`; these do not match the current sortable filename parser.
- Legacy migration review found real issues:
  - `008 - Automatic offer reservation.sql` starts with `begin;` but ends with top-level `end;`, not `commit;`.
  - `011 - Access to boarder card code in account.sql`, `012 - Reservation by day.sql`, and `013 - Reservation by day refactoring.sql` end with `commit` without a semicolon, so the current tokenizer does not see a final top-level `COMMIT`.
- Do not silently rename or edit imported legacy migrations if they represent already-applied shared/persistent history; that would conflict with ADR-003 unless a baseline/import decision is made.

## ADRs

- `docs/adr/ADR-001 - SQL files as source of truth.md`: authored `.sql` files are authoritative migration artifacts; run SQL may wrap them operationally but must not redefine them.
- `docs/adr/ADR-002 - Migration authors own transaction behavior.md`: transaction intent belongs with authored SQL and selected engine policy; sql-mig must not silently invent transaction behavior.
- `docs/adr/ADR-003 - Applied migrations are immutable.md`: applied migration history is append-only; baseline/archive/retention may change representation but must preserve traceable applied facts.

Recommended next ADR:

- ADR-004: legacy migration import/baseline policy. This should decide whether imported historical migrations are executable, reference-only, renamed, fixed, or represented by a baseline before normal sql-mig-managed history begins.

## Important Files

- `composer.json`: package metadata and PHP/Symfony Console requirements.
- `bin/sql-mig`: CLI executable.
- `docs/adr/README.md`: ADR index, filename format, and writing guidance.
- `src/Cli/Application.php`: manual dependency wiring.
- `src/Config/DatabaseUrlNormalizer.php`: `psql`-compatible URL cleanup.
- `src/Runner/MigrationRunner.php`: apply/validate orchestration.
- `src/Migration/TransactionBoundaryValidator.php`: SQL tokenizer and boundary detection.
- `src/Migration/AuditSqlInjector.php`: audit row injection.
- `src/Audit/AuditRepository.php`: audit-table reads and creation.
- `src/Runner/PsqlExecutor.php`: shell-out execution through `psql`.
- `phpstan.neon.dist`: PHPStan configuration.
- `mago.toml`: Mago lint/analyze/format configuration.
- `Dockerfile`: FrankenPHP app image with Composer copied from the Composer image.
- `docker-compose.yaml`: local FrankenPHP/PostgreSQL environment.
- `.dockerignore`: excludes local repo metadata, `vendor`, and tool caches from image build context.

## Recommended Next Steps

1. Start the environment with `docker compose up -d --build`.
2. Install/update dependencies with `docker compose exec app composer install`.
3. Run `docker compose exec app composer check`.
4. Decide legacy migration import/baseline policy before renaming or editing imported real-system migrations.
5. Verify `validate`/`apply` against PostgreSQL using migrations that match the current filename parser, or after the import/baseline policy is settled.
6. Redesign execution around a single `psql` session or transaction script so advisory locking is real.
7. Add focused tests once the execution/session model is settled.

## Last Verified

The latest verified command was:

```bash
docker compose run --rm app composer check
```

It passed with PHPStan, Mago lint/analyze, and Mago format-check.

Additional verification:

```bash
docker compose run --rm app php bin/sql-mig validate
```

This currently fails during migration discovery because imported legacy files such as `000 - Extensions.sql` do not match the current filename parser.
