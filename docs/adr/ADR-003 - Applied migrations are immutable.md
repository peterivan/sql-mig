# ADR-003: Applied Migrations Are Immutable

## Status

Accepted

Date: 2026-06-01

## Decision

Once a migration has been applied to any shared or persistent environment, its committed migration file must not be modified.

Formatting, comment, and whitespace-only edits to applied migrations are still modifications.

Corrections to changes introduced by previously applied migrations must be introduced through new migrations rather than by editing historical migrations.

sql-mig treats migration history as an append-only record of database changes.

The project may use technical mechanisms to detect or prevent modification of applied migrations, but those mechanisms are not part of this decision.

## Why

Migration history serves as both an execution log and an audit trail.

Changing an applied migration makes it unclear what was executed against a database and breaks reproducibility across environments.

An append-only migration history preserves the relationship between source control, audit records, and the resulting database state.

## Consequences

* Database changes are introduced through new migrations.
* Historical migrations remain available for inspection and auditing.
* Migration history can be compared across environments.
* Fixing an applied migration requires creating a new migration rather than editing the existing one.
* Squashing, rewriting, or replacing applied migrations requires a separate decision.

## Revisit When

Reconsider if immutable applied migrations become incompatible with a later baseline, archival, or retention model.

## References

* [ADR-001: SQL Files as the Source of Truth](<ADR-001 - SQL files as source of truth.md>)
* [ADR-002: Migrations Use Explicit Transaction Boundaries](<ADR-002 - Migration authors own transaction boundaries.md>)
