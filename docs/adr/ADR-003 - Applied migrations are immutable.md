# ADR-003: Applied Migrations Are Immutable

## Status

Accepted

Date: 2026-06-01

## Owner

The sql-mig migration history boundary owns this decision.

Applied-history recording, validation, and review policy must treat applied migrations as append-only
history.

## Decision

A migration is applied when the active history policy records it as already executed or otherwise
recognized as part of the authoritative history for a target database environment.

Once a migration has been applied to any environment whose applied history is intended to be reused,
compared, audited, or treated as authoritative, its authored migration artifact must not be modified.

Formatting, comment, and whitespace-only edits to applied migrations are still modifications.

Corrections to changes introduced by previously applied migrations must be introduced through new
migrations rather than by editing historical migrations.

sql-mig treats migration history as an append-only record of database changes.

Baseline and import policies may define how pre-existing database history enters applied history, but
they must preserve the append-only history boundary.

The technical mechanisms used to detect or prevent modification may evolve, but they must preserve
this append-only history boundary.

## Why

Migration history supports execution traceability and auditability.

Changing an applied migration makes it unclear what was executed against a database and breaks
reproducibility across environments.

An append-only migration history preserves the relationship between authored migration artifacts,
recorded applied history, and the resulting database state.

## Consequences And Invariants

* Corrections after an applied migration are introduced through new migrations.
* Historical migrations remain traceable for inspection and auditing through the active history,
  archive, or retention policy.
* Migration history can be compared across environments.
* sql-mig must not silently accept applied-history drift between recorded applied history, authored
  migration artifacts, and the active history policy.
* Fixing an applied migration requires creating a new migration rather than editing the existing one.
* Baseline, archive, and retention policies may change how applied history is represented, but they
  must preserve existing applied facts as traceable history.
* Squashing, rewriting, or replacing applied migrations requires a superseding ADR or explicit
  baseline/archive policy.

## Validation

Validation must preserve the append-only history boundary.

The specific mechanisms for artifact identity, drift handling, audit storage, baseline import,
archival, and content retention may evolve under separate policies, and ADRs are required when those
mechanisms alter this boundary.

## Revisit When

Reconsider if immutable applied migrations become incompatible with a later baseline, archival, or
retention model.

## References

* [ADR-001: SQL Files as the Source of Truth](<ADR-001 - SQL files as source of truth.md>)
