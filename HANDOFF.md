# SQL Mig Handoff

This directory is intended to become the root of the future `peterivan/sql-mig` repository.

## Current State

- Package identity is `peterivan/sql-mig`.
- PHP namespace is `PeterIvan\SqlMig`.
- Runtime baseline is PHP `>=8.5`.
- CLI entry point is `bin/sql-mig`.
- Implemented commands:
  - `php bin/sql-mig validate`
  - `php bin/sql-mig apply`
- No tests are intentionally present yet.
- Local test environment uses FrankenPHP and PostgreSQL via root `Dockerfile` and `compose.yaml`.
- Static-analysis/lint/format tooling is wired through Composer scripts.
- All project commands should be run inside the app container.

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

The app image is Debian-based `dunglas/frankenphp:1-php8.5`, not Alpine. This is intentional:
Mago has a GNU/Linux binary already downloaded in `vendor`, while Alpine needs a separate musl
binary and may attempt a first-run GitHub download from inside the container.

The app image copies Composer from the official `composer:2` image and installs only:

- `postgresql-client`
- `unzip`

`git` was intentionally removed from the app image.

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

## Important Files

- `composer.json`: package metadata and PHP/Symfony Console requirements.
- `bin/sql-mig`: CLI executable.
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
- `compose.yaml`: local FrankenPHP/PostgreSQL environment.
- `.dockerignore`: excludes local repo metadata, `vendor`, and tool caches from image build context.

## Recommended Next Steps

1. Start the environment with `docker compose up -d --build`.
2. Install/update dependencies with `docker compose exec app composer install`.
3. Run `docker compose exec app composer check`.
4. Verify `validate`/`apply` against PostgreSQL.
5. Redesign execution around a single `psql` session or transaction script so advisory locking is real.
6. Add focused tests once the execution/session model is settled.

## Last Verified

The latest verified command was:

```bash
docker compose exec app composer check
```

It passed with PHPStan, Mago lint/analyze, and Mago format-check.
