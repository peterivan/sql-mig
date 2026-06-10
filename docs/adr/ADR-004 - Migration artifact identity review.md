# ADR-004 Review Notes

Target: [ADR-004: Migration Artifact Identity](<ADR-004 - Migration artifact identity.md>)

## Self-Review

The proposal defines a durable boundary between ADR-001 and ADR-003. It does not define checksum
algorithms, audit storage, archive formats, baseline formats, directory layouts, or filename parsing
implementation.

The strongest part is the separation between identity-bearing attributes and representation
mechanisms. This should allow future PostgreSQL, SQLite, Firebird, baseline, archive, and retention
work to choose different storage or hashing mechanisms without changing the architectural boundary.

The proposed identity fields are:

* version identifier;
* authored SQL content.

This gives sql-mig enough information to distinguish artifacts, compare applied history, and detect
changes after a migration becomes applied.

The proposal intentionally treats filename, path, and human-readable label as traceability metadata
rather than identity-bearing fields. This keeps archive and retention policies free to move or rename
stored artifacts while preserving identity, as long as the migration version identifier and authored
SQL content remain stable.

## Adversarial Review

### Filename As Traceability Metadata May Be Too Loose

The proposal does not make filename identity-bearing. This protects archive and import flexibility,
but it may make human-facing rename drift less visible.

If the project wants human-readable migration labels to be immutable after apply, that should be
handled by a review policy or archive policy rather than by artifact identity.

The important distinction is:

* changing the version identifier changes identity;
* changing only a storage path or human-readable label does not change identity under ADR-004.

### Version Identifier As Ordering Coordinate May Hide Parser Policy

The proposal says the version identifier provides the ordering coordinate while avoiding parsing
rules. This is probably the right abstraction, but it should not prevent future ordering models from
adding secondary coordinates.

Future branch/sequence/group policies might need more than one ordering dimension. The proposal
allows ordering policy to define parsing and comparison, and now treats the version identifier as the
primary ordering coordinate rather than the only possible coordinate.

### Authored SQL Content As Identity Is Correct But Operationally Heavy

Treating authored SQL content as identity-bearing means whitespace and comment changes produce a
different artifact once applied. That matches ADR-003, but it is strict. The ADR should remain clear
that this is an architectural choice, not a checksum implementation detail.

### Baseline Compatibility Needs Care

The proposal says baseline policies must preserve stable identity for applied artifacts. That works
when baseline imports refer to known artifacts. It is less clear for a baseline that represents a
database state without one artifact per historical migration.

State baselines should remain separate from migration artifact identity unless a future ADR explicitly
combines them. ADR-004 should not force baseline records to pretend they are migration artifacts.

### Applied History Without Local Artifacts

Archive and retention policies may remove artifacts from the active migration directory. The proposal
requires stable identity but does not say whether identity must remain resolvable without the original
file. That is probably correct for ADR-004, but archive policy must handle it explicitly.

## Recommended Refinements Before Acceptance

1. Confirm that filename, path, and human-readable label should remain traceability metadata rather
   than identity-bearing fields.
2. Confirm that exact authored SQL content, not semantic SQL meaning, is identity-bearing.
3. Confirm that state baselines, archive records, and storage records have separate identities unless
   a future ADR combines them with migration artifact identity.
4. Keep checksum, audit table, manifest, archive, and storage details out of ADR-004.
5. Do not accept ADR-004 until the team confirms that renaming an applied migration without changing
   its version identifier or authored SQL content should not be treated as identity drift.
