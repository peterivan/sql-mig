# ADR-002: Migration Authors Own Transaction Behavior

## Status

Accepted

Date: 2026-06-01

## Owner

The sql-mig migration execution boundary owns this decision.

Transaction ownership is shared:

* migration authors own transaction intent expressed in authored SQL files;
* engine policies own the transaction forms sql-mig supports for each target database engine;
* the sql-mig execution boundary owns enforcement of the selected engine policy.

## Decision

sql-mig does not silently invent transaction behavior for migrations.

For this ADR, transaction behavior means transaction intent expressed by the authored migration and
the engine policy governing whether that intent is supported.

This ADR does not define every database behavior that can affect transactional execution, such as
isolation levels, locking semantics, audit atomicity, autocommit behavior, DDL transaction semantics,
or connection/session state.

Migration authors must make transaction intent visible in the SQL file when the target engine policy
requires explicit transaction control.

sql-mig may validate transaction boundaries and may reject migrations whose transaction behavior is
not supported by the selected engine policy.

## Why

Transaction scope is part of migration behavior.

Automatically adding transaction boundaries would hide execution semantics from the migration author
and could produce behavior that differs from the reviewed SQL.

Keeping transaction behavior in the authored migration file makes it visible, reviewable, and tied to
the database change.

Different database engines may support different transaction forms, DDL behavior, savepoint behavior,
and operational constraints. Those differences should be handled by engine policy rather than by
replacing authored SQL with a portable transaction abstraction.

## Consequences And Invariants

* Transaction intent remains reviewable in the authored migration file.
* sql-mig must not automatically wrap authored SQL in transactions unless a later ADR explicitly
  changes this decision.
* Adding transaction-control statements around authored SQL is a transaction-policy change, not an
  ADR-001 run-SQL operation.
* Transaction-control statements that affect migration transaction scope are governed by this ADR and
  the selected engine policy, even when they are emitted as run SQL.
* Engine policies may define which transaction forms are accepted, rejected, or unsupported.
* Migration authors are expected to understand the transaction semantics of the target database
  engine.
* A database operation that conflicts with the selected engine policy is unsupported for that engine
  until a later ADR or engine design supports it.

## Validation

Transaction validation is engine-specific and follows the selected engine policy.

## Revisit When

Reconsider if sql-mig needs to make transaction behavior implicit, portable, or generated outside the
authored migration artifact.

## References

* [ADR-001: SQL Files as the Source of Truth](<ADR-001 - SQL files as source of truth.md>)
