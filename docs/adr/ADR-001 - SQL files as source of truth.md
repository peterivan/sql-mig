# ADR-001: SQL Files as the Source of Truth

## Status

Accepted

Date: 2026-06-01

## Decision

sql-mig treats committed `.sql` migration files as the source of truth for database migrations.

The committed SQL defines the migration intent and the database changes the migration is responsible for.

sql-mig may add operational bookkeeping around that SQL, such as audit writes required to record successful execution. This bookkeeping is part of running the migration, not a translation from another migration language.

## Why

sql-mig prioritizes explicitness, auditability, and operational clarity over abstraction.

Framework migrations, ORM metadata, and declarative schema diffs can be useful, but they add a translation step between the authored change and the SQL executed by the database.

That translation step is outside the sql-mig boundary. External tools may generate SQL as a starting point, but once committed, the SQL file is authoritative.

sql-mig validates and records SQL migrations; it does not generate them from application models.

## Consequences

* Migration history stays transparent and framework-independent.
* PostgreSQL-specific DDL, DML, functions, indexes, constraints, and transaction-compatible operational statements can be used directly.
* sql-mig does not need to explain or validate a higher-level migration language.
* Developers are expected to understand SQL and PostgreSQL behavior.
* Cross-database portability is not a goal of this decision.

## Revisit When

Reconsider if sql-mig expands beyond explicit SQL migrations or SQL-first authoring becomes a significant maintenance barrier.

## References

* None.
