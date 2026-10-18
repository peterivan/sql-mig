# Plan Follow-Up TODOs

## Before Implementing

- Specify the sortable version token parser precisely:
  - Define accepted regexes for `YYYY-MM-DD_HHMM_description.sql` and `YYYY-MM-DD_HHMM_NN_description.sql`.
  - Define how the token is extracted from the filename.
  - Define the comparison tuple, including how non-sequenced and sequenced migrations in the same minute are ordered.
  - Decide whether `2026-05-29_1430_description.sql` is equivalent to sequence `00`, sorts before `_01`, or is disallowed when sequenced files exist in the same minute.
- Resolve the `auditSchemaVersion` scope wording:
  - `PLAN.md` currently includes `auditSchemaVersion` in v1 metadata.
  - The v1.1 note should be reworded to focus on extending metadata with new keys, not first introducing `auditSchemaVersion`.
- Clarify the audit table SQL example:
  - Keep the current section as a column list, or replace it with full implementer-ready `create table if not exists "<schema>"."<table>" (...)` SQL.
  - Ensure the unique constraint on `version` remains explicit.

## Review Context

- `PLAN.md` is currently an untracked file.
- Run `git add PLAN.md PLAN-TODO.md` before reviewing staged diff or committing.
