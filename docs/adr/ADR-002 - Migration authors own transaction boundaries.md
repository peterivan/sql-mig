# ADR-002: Migrations Use Explicit Transaction Boundaries

## Status

Accepted

Date: 2026-06-01

## Decision

sql-mig does not automatically wrap migrations in transactions.

The current sql-mig execution model requires every migration to be executed within a single explicit transaction defined in the migration file.

Each migration must begin with a top-level `BEGIN` and end with a top-level `COMMIT`. Additional top-level transaction blocks inside the migration are not supported.

sql-mig validates these boundaries, executes the migration within that transaction, and records audit bookkeeping before the final `COMMIT`. Migration authors should not write sql-mig audit-table rows manually.

## Why

Transaction scope is part of migration behavior.

Automatically adding transaction boundaries would hide execution semantics from the migration author and could produce behavior that differs from the reviewed SQL.

Keeping transaction boundaries in the migration file makes them visible, reviewable, and versioned with the database change.

## Consequences

* Transaction behavior is visible in source control.
* Migration review includes transaction scope.
* Audit bookkeeping can be recorded in the same transaction as the migration.
* Migration authors are expected to understand PostgreSQL transaction semantics.
* PostgreSQL operations that cannot run inside a transaction are outside this migration model for now.

## Revisit When

Reconsider if sql-mig needs to support PostgreSQL operations that must run outside a transaction, such as `CREATE INDEX CONCURRENTLY`.

## References

* [ADR-001: SQL Files as the Source of Truth](<ADR-001 - SQL files as source of truth.md>)
