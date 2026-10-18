# Known shortcomings — initial standalone implementation

Findings from the reviews of the code brought in by issue #1 (2026-10-18)
that were consciously left as they are. The defects found by the same reviews
were fixed on the branch (see the dev note). Each item is a candidate follow-up issue; file it when it is
picked up and remove it here.

## 1. Tokenizer does not understand `E'...'` escape strings

`E'it\'s'` throws the statement tokenizer off and the file is rejected as
missing BEGIN/COMMIT. Rejection is the safe failure mode, but such a literal
cannot be used in a migration today.

**Possible fix:** when a `'` is preceded by `E`/`e` outside a word, treat `\'`
as an escaped quote inside the literal.

## 2. Only literal `begin;` and `commit;` are accepted

`BEGIN TRANSACTION`, `START TRANSACTION`, `COMMIT WORK`, `END` and a final
`commit` without a trailing semicolon are all rejected. Acceptable as a
convention, but the README does not state it.

**Possible fix:** document the convention in the README, or accept the
PostgreSQL synonyms in `TransactionBoundaryValidator`.

## 3. Advisory locking is not wired

Two concurrent `apply` runs are protected only by the unique constraint on
`version`, which fails the second transaction after its DDL already ran. It
still rolls back, but with a constraint error rather than a lock message, and
the `AdvisoryLock` class is unused because advisory locks need one persistent
session while every statement here is a separate `psql` call.

**Possible fix:** run all pending migrations in a single `psql` session
(one script, or `psql` kept open on a pipe) that takes
`pg_advisory_lock` first. Known gap from `HANDOFF.md`.
