# ADR-001: SQL Files as the Source of Truth

## Status

Accepted

Date: 2026-06-01

## Owner

The sql-mig migration artifact boundary owns this decision.

Migration discovery, validation, execution, and audit recording consume this boundary and must treat
authored SQL files as the authoritative migration input.

## Decision

sql-mig treats authored `.sql` migration files as the source of truth for database migrations.

The authored SQL file defines the migration intent and the database changes the migration is
responsible for.

sql-mig may build the SQL submitted to the database for a run by adding operational statements around
the authored SQL. That run SQL is part of executing the migration, but it is not a replacement source
artifact and must not redefine the authored SQL.

## Why

sql-mig prioritizes explicitness, auditability, and operational clarity over abstraction.

Framework migrations, ORM metadata, and declarative schema diffs can be useful, but they add a
translation step between the authored change and the SQL executed by the database.

That translation step is outside the sql-mig boundary. External tools may generate SQL as a starting
point, but once the generated SQL becomes the migration artifact, the SQL file is authoritative.

sql-mig validates and records SQL migrations; it does not generate them from application models.

## Consequences And Invariants

* Migration artifacts remain authored SQL rather than framework migration objects, ORM metadata, or
  schema-diff representations.
* Migration discovery, validation, run SQL, and audit records must preserve the SQL file as
  the authoritative source artifact.
* sql-mig does not need to explain or validate a higher-level migration language.
* sql-mig may parse, inspect, lint, validate, or document authored SQL, but it must not replace that
  SQL with a runtime model, schema diff, or framework migration object as the authoritative migration
  representation.
* Generated SQL is acceptable as an authoring aid before it becomes the migration artifact; runtime
  derivation from models or schema diffs is outside this boundary.

## Validation

Review changes to migration discovery, run SQL construction, and audit recording against this
boundary.

Tooling may validate migration artifacts, and audit recording may reference those artifacts, but
neither may replace the authored SQL with a derived migration representation.

## Revisit When

Reconsider if sql-mig expands beyond explicit SQL migrations or SQL-first authoring becomes a
significant maintenance barrier.
