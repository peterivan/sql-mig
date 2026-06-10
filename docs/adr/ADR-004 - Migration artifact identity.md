# ADR-004: Migration Artifact Identity

## Status

Proposed

Date: 2026-06-10

## Owner

The sql-mig migration artifact boundary owns this decision.

Migration discovery, applied-history recording, drift detection, and cross-environment comparison
consume this boundary.

## Decision

A migration artifact is an authored SQL migration file plus the stable identity information sql-mig
uses to distinguish it from other migration artifacts.

The migration artifact identity consists of:

* the migration version identifier;
* the authored SQL content.

Authored SQL content means the exact authored bytes or text content according to the active artifact
policy, not the semantic meaning of the SQL. Comment-only and whitespace-only changes produce a
different migration artifact identity once applied history depends on that artifact.

The artifact filename, path, and human-readable label are traceability and discovery metadata. They
are not identity-bearing unless the active artifact policy derives the migration version identifier
from them and that identifier changes.

Once a migration artifact becomes part of applied history, its identity must remain stable. Changing
any identity-bearing part of an applied migration artifact creates a different artifact and must be
handled by the active history, baseline, archive, or drift policy rather than being treated as the
same applied migration.

The migration version identifier participates in artifact identity and provides the primary ordering
coordinate used to place migrations in deterministic order. Ordering policy may define how version
identifiers are parsed and compared, but it must not make ordering depend on mutable metadata outside
the migration artifact identity.

## Why

ADR-001 defines authored SQL files as the source of truth. ADR-003 defines applied migration history
as append-only. sql-mig needs a stable artifact identity boundary between those decisions so it can
discover migrations, compare environments, record applied history, and detect drift without coupling
those responsibilities to a specific checksum algorithm, audit schema, storage format, or archive
layout.

SQL content alone is not enough because two migrations can contain identical SQL while representing
different database changes at different points in history.

The version identifier alone is not enough because the authored SQL content is the source artifact
whose modification must be detectable once applied history depends on it.

The filename is not identity-bearing by itself because human-readable names, storage paths, and
archive names may change without changing migration intent. When a filename carries the migration
version identifier, changing that identifier changes identity; changing only representation metadata
does not.

## Consequences And Invariants

* Applied history refers to migration artifacts by stable identity, not by storage implementation.
* Drift detection compares recorded applied history with current migration artifacts according to an
  active history and artifact policy.
* Baseline, archive, and retention policies may change how artifacts are stored or represented, but
  they must preserve stable identity for applied artifacts.
* Renaming, moving, or archiving an artifact does not change identity unless it changes an
  identity-bearing attribute.
* A checksum, hash, signature, manifest entry, or database row may be used to represent identity, but
  no specific mechanism is required by this ADR.
* Directory layout, filename parsing implementation, audit-table schema, archive format, baseline
  format, and drift-detection implementation are outside this decision.
* Migration ordering must be deterministic from identity-bearing artifact information, with the
  version identifier as the primary ordering coordinate.
* Baseline identity, archive identity, and storage identity are separate concerns from migration
  artifact identity unless a later ADR explicitly combines them.

## Validation

Review changes to discovery, ordering, applied-history recording, drift detection, baseline import,
and archive handling against this identity boundary.

Validation must preserve the distinction between the artifact identity and the mechanisms used to
store, hash, serialize, archive, or compare it.

## Revisit When

Reconsider if sql-mig needs to support multiple migration artifacts with the same version identifier,
identity not based on filenames, or a history model where applied artifact identity can intentionally
change.

## References

* [ADR-001: SQL Files as the Source of Truth](<ADR-001 - SQL files as source of truth.md>)
* [ADR-003: Applied Migrations Are Immutable](<ADR-003 - Applied migrations are immutable.md>)
