# Known shortcomings — initial standalone implementation

Findings from the first review of the code brought in by issue #1 (2026-10-18)
that were consciously left as they are so the branch only lands the existing
implementation. Each item is a candidate follow-up issue; file it when it is
picked up and remove it here.

## 1. Error messages name a temp file instead of the migration

A failing migration reports `Migration "/tmp/phpXXXX" failed with exit code 3`
because `PsqlExecutor::run()` passes its own tmpfile path to
`MigrationCommandFailed`. The reader cannot tell which migration failed
without scrolling psql's output.

**Possible fix:** let the runner pass the migration path (or filename) as a
label into the executor and use it in the exception; keep the psql output
lines, whose `psql:<tmp>:<line>` prefixes still match the original file's
line numbers up to the injected insert.

## 2. Doctrine `pgsql://` scheme is not normalized

Symfony projects commonly use `pgsql://user:pass@host/db`. `psql` ignores the
unknown scheme and tries the local unix socket, so the run fails with a
misleading "socket not found" error. The normalizer only rewrites `pdo-pgsql:`.

**Possible fix:** map `pgsql:` and `pdo_pgsql:` to `postgresql:` as well.

## 3. Database password visible in the process list

The full URL, credentials included, is passed as `--dbname`, so `ps` shows it
while psql runs.

**Possible fix:** strip the password from the URL and pass it through
`PGPASSWORD` in the child's environment, or write a temporary `PGPASSFILE`.

## 4. Missing migrations directory passes validation

`validate --path=nope` prints "Validation passed, pending: 0" because `glob`
returns an empty list. A typo in `--path` on a server silently skips every
migration.

**Possible fix:** fail discovery when the directory does not exist (or is not
readable).

## 5. Deprecated `str_getcsv` call

Under `E_ALL`, PHP 8.5 reports that the `$escape` parameter of `str_getcsv`
must be passed explicitly. The call in `PsqlExecutor::queryCsv` relies on the
default.

**Possible fix:** pass `escape: ''`.

## 6. License mismatch

`composer.json` declares MIT while the committed `LICENSE` is Apache 2.0.

**Possible fix:** pick one and align the other.

## 7. Tokenizer does not understand `E'...'` escape strings

`E'it\'s'` throws the statement tokenizer off and the file is rejected as
missing BEGIN/COMMIT. Rejection is the safe failure mode, but such a literal
cannot be used in a migration today.

**Possible fix:** when a `'` is preceded by `E`/`e` outside a word, treat `\'`
as an escaped quote inside the literal.

## 8. Only literal `begin;` and `commit;` are accepted

`BEGIN TRANSACTION`, `START TRANSACTION`, `COMMIT WORK`, `END` and a final
`commit` without a trailing semicolon are all rejected. Acceptable as a
convention, but the README does not state it.

**Possible fix:** document the convention in the README, or accept the
PostgreSQL synonyms in `TransactionBoundaryValidator`.

## 9. `apply` creates the audit table before validating

`MigrationRunner::apply` calls `ensureTable` first, so a run that then fails
validation still leaves the audit schema and table behind.

**Possible fix:** validate first, create the table only when there is
something to apply (or at least after validation passed).

## 10. `apply` discovers and hashes every file twice

Once inside `validate`, once again to filter pending files.

**Possible fix:** return the pending `MigrationFile` objects from validation.

## 11. Advisory locking is not wired

Two concurrent `apply` runs are protected only by the unique constraint on
`version`, which fails the second transaction after its DDL already ran. It
still rolls back, but with a constraint error rather than a lock message, and
the `AdvisoryLock` class is unused because advisory locks need one persistent
session while every statement here is a separate `psql` call.

**Possible fix:** run all pending migrations in a single `psql` session
(one script, or `psql` kept open on a pipe) that takes
`pg_advisory_lock` first. Known gap from `HANDOFF.md`.

## 12. Dead code

`AuditRepository::latestVersion` is unused (the runner derives the latest
version from `loadApplied`).

**Possible fix:** remove it with the advisory-lock redesign or on its own.
