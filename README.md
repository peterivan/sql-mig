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

`composer check` runs PHPStan, Mago lint, Mago analyze, and Mago format checks.
