# SQL Mig

Standalone PostgreSQL SQL file migration runner.

```bash
vendor/bin/sql-mig validate
vendor/bin/sql-mig apply
```

Configuration is resolved from CLI flags first, then environment variables, then defaults.
Doctrine/Symfony-style PostgreSQL URLs are normalized before invoking `psql`;
`pdo-pgsql://` is accepted, and Doctrine-only query parameters such as
`serverVersion` and `charset` are ignored.

```bash
vendor/bin/sql-mig apply \
  --database-url="postgresql://user:pass@host:5432/db" \
  --path=database/migrations \
  --audit-schema=Audit \
  --audit-table=DatabaseVersion
```

## Migration files

Each file in the migrations directory is named `YYYY-MM-DD_HHMM_description.sql`
or `YYYY-MM-DD_HHMM_NN_description.sql` and must start with `begin;` and end with
`commit;`. The audit row is inserted before that final commit, so the migration
and its record are committed together. Applied files are immutable: their
SHA-256 checksum (computed with line endings normalized to LF, recorded as
`sha256-lf`) is compared on every run.

## Exit codes

| Code | Meaning |
|---|---|
| 0 | Success |
| 1 | Configuration error (missing database URL, missing `psql`, bad identifier, missing migrations directory) |
| 2 | Validation error (bad filename, duplicate version, missing BEGIN/COMMIT, checksum mismatch, out-of-order or missing applied migration) |
| 3 | Execution error (`psql` failed: connection, SQL error) |

## Local FrankenPHP Environment

```bash
docker compose up -d --build
docker compose exec app composer install
docker compose exec app php bin/sql-mig validate
docker compose exec app php bin/sql-mig apply
```

The Compose environment uses PostgreSQL on the internal host `database` and exposes it on local port `54329`.

```bash
DATABASE_URL=postgresql://sql_mig:sql_mig@database:5432/sql_mig
```

## Tooling

```bash
docker compose exec app composer analyse
docker compose exec app composer check
docker compose exec app composer mago:format
```

`composer check` runs PHPStan, Mago lint, Mago analyze, and Mago format checks; `composer ci` is an alias of it.

## Contributing

Working conventions for humans and AI agents (issues, branches, dev notes, checks) are in [`AGENTS.md`](AGENTS.md).
