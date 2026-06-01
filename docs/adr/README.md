# ADR Guidelines

This repository uses a small ADR format.

ADRs document decisions that are worth remembering later.

## Index

* [ADR-001: SQL Files as the Source of Truth](<ADR-001 - SQL files as source of truth.md>)
* [ADR-002: Migrations Use Explicit Transaction Boundaries](<ADR-002 - Migration authors own transaction boundaries.md>)
* [ADR-003: Applied Migrations Are Immutable](<ADR-003 - Applied migrations are immutable.md>)

## File Names

Use this format:

```text
ADR-XXX - Human readable title.md
```

## General Rules

* ADRs are accepted by default when added to the repository.
* Keep ADRs short, usually one page or less.
* Focus on project-specific reasoning.
* Mention alternatives only when they clarify the decision.
* Avoid repeating the same trade-off in multiple sections.
* Keep accepted ADRs stable. If the decision changes, add a new ADR and mark the old one as superseded.

## Status Values

* `Accepted`: the decision is current.
* `Superseded`: the decision was replaced by a later ADR.
* `Rejected`: the option or proposal was considered but not adopted.

## Writing Guidance

Good ADRs:

* Explain why a decision was made.
* Capture trade-offs.
* Help future contributors understand the reasoning.

Avoid:

* Marketing language.
* Generic architecture essays.
* Restating external documentation.
* Describing implementation details that belong elsewhere.
* Listing options just to fill a template.
