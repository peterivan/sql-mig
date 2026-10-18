# 1 – Initial standalone implementation of sql-mig

## 2026-10-18

### Problem

The package was developed in this directory outside git: only `LICENSE` was
committed on `main`. The code, Compose environment and tooling needed to land
on a branch with a pull request, and the repository needed the working
conventions used by the owner's other projects (issue-driven branches, dev
notes, agent config).

### Findings from the first review of the code

Verified locally with PHP 8.5.11 and the Compose PostgreSQL 16 database:

- `composer check` is green (PHPStan max, Mago lint/analyze/format).
- `validate` lists the probe migration, `apply` applies it and writes the
  audit row, a second `apply` reports nothing pending.
- A modified already-applied file fails the checksum check.
- A migration that errors mid-way rolls back fully: no table, no audit row.
- `postgres://`, `postgresql://` and `pdo-pgsql://` URLs with Doctrine query
  parameters work.

Defects and limitations found are recorded in
`docs/shortcomings/1-initial-standalone-implementation.md` as candidate
follow-up issues; none of them is fixed on this branch, which only brings the
existing code in.

### Decisions

- **Branch, not main.** Issue #1 tracks the initial implementation; the branch
  `1-initial-standalone-implementation` was created as the issue's linked
  branch with `gh issue develop`, and draft PR #2 closes the issue.
- **Process ported from the GitLab projects, adapted to GitHub.** The GitLab
  flow (`glab mr create --related-issue`, MR pipelines) maps to
  `gh issue develop` for the branch and `gh pr create --draft` with
  `Closes #<n>` for the PR. Differences kept in mind: GitHub needs the `Closes` line explicitly, and branch creation is a
  two-step (branch, then PR after the first push).
- **English everywhere.** The repository is public on GitHub, so issues, dev
  notes, commits, skills and code comments are written in English.
- **No spec-driven proposal tooling.** The library is small and its scope is
  already written down in `PLAN.md`; a proposal layer would add ceremony
  without a second consumer of the specs.
- **Agent config in `.agents/` with `.claude` as a symlink**, same as the
  sibling projects, so Codex/OpenCode and Claude Code read one tree.
- **`composer ci` is an alias of `composer check`** so the command matches
  the muscle memory from the other projects; `check` stays for the README.
- **`.idea/` is ignored**; it was untracked and carries only IDE state.
- `PLAN.md`, `PLAN-TODO.md` and `HANDOFF.md` stay at the root as history.
  Under the new rules such notes would live in the issue or the dev note;
  moving them is not worth a churny diff on the first branch.

### Implementation

- `AGENTS.md` + `CLAUDE.md` (imports it).
- `.agents/skills/github-issue/SKILL.md`, `.agents/agents/ci-runner.md`.
- `docs/dev-notes/`, `docs/shortcomings/` with this branch's files.
- `.editorconfig`, `.gitattributes`, `.gitignore` additions (`/tmp/`,
  `/.idea/`, Claude scheduled-task lock).

### Validation

- `composer ci` green on the host PHP 8.5 after the alias was added.
- Runtime validation of `validate`/`apply` against the Compose database as
  listed under *Findings*; the database container and volume were removed
  afterwards.
- **No CI for now.** A GitHub Actions workflow with the dev-note and
  `composer ci` gates was drafted and then dropped at the owner's request;
  the gates are run by the agent before a PR is marked ready (AGENTS.md).
- **Repository settings** (set through the GitHub API, not in git): squash
  is the only merge method, the squash commit takes the PR title and body,
  branches are deleted on merge, Wiki is off. Ruleset "main: pull requests
  only" (active, no bypass actors) blocks deletion and force-pushes of
  `main` and requires a pull request with resolved review threads; no
  approvals are required because the repository has one maintainer.
- `.github/PULL_REQUEST_TEMPLATE.md` gives human-opened PRs the same
  Summary / Validation / `Closes #` shape the skill uses.

## 2026-10-18 — Code review fixes

### Findings

A review of the branch diff found ten defects. Three overlapped with the
shortcomings list (missing migrations path, audit table created before
validation, `str_getcsv` deprecation); seven were new. The two serious ones:

- `bin/sql-mig` required `__DIR__/../vendor/autoload.php`, which does not
  exist when the package is installed as a dependency, so `vendor/bin/sql-mig`
  fatals in a consumer project.
- The statement tokenizer never flushed a trailing statement without `;`, so
  text after the final `commit;` passed validation and psql executed it in
  autocommit after the transaction and the audit row were committed.

### Decisions

- **Fix on this branch** rather than file issues: both serious findings make
  the shipped tool wrong, and the rest were small. The cheap shortcomings
  that shared code paths with the fixes (temp-file label in errors, password
  visible in the process list, `pgsql://` scheme) went in too. Left as
  shortcomings: `E''` strings, transaction keyword synonyms, advisory
  locking.
- **License is Apache 2.0** (owner's decision); `composer.json` said MIT
  while the committed `LICENSE` was Apache 2.0, and the manifest was wrong.
- **psql now reads SQL from stdin through `proc_open`** instead of a temp
  file and `exec`. That gives separate stdout/stderr (parsed CSV is no longer
  polluted by notices), a meaningful label in `MigrationCommandFailed`, and a
  child environment where the URL password travels as `PGPASSWORD` instead
  of a command-line argument. `--no-psqlrc --quiet` make the run independent
  of the user's `.psqlrc`.
- **Unterminated final statement counts as a statement.** psql executes it
  at end of input, so the tokenizer flushes it. A bare `commit` without `;`
  is therefore accepted; anything after `commit;` is rejected as "must end
  with COMMIT".
- **Validation now also fails on applied versions with no local file**
  (`MissingAppliedMigration`) and on a missing or unreadable migrations
  directory (`MigrationDirectoryNotFound`), so a wrong `--path` or a deleted
  migration cannot pass silently.
- **`apply` validates first** and creates the audit table only when there is
  something to apply. A migration that fails at execution still leaves the
  audit schema behind, because the audit insert inside the migration needs
  the table; that is inherent, only the validation-failure case was fixed.
- `ValidationResult` now carries the pending `MigrationFile` objects, so
  `apply` no longer discovers and hashes every file twice.
- The unique constraint is named `<table>_version_uq` so two audit tables in
  one schema no longer collide; `quoteLiteral` lives in `AuditSchema` next to
  `quoteIdentifier`; `Application::add()` became `addCommand()` (deprecated
  in symfony/console 7.4, removed in 8.0); `str_getcsv` passes `escape: ''`;
  the unused `AuditRepository::latestVersion` was removed.

### Validation

`composer ci` green. Against a fresh Compose PostgreSQL 16 (host PHP 8.5.11):

- `pgsql://…?serverVersion=16&charset=utf8` connects.
- `apply` twice with a `.psqlrc` containing `\timing on`, `\set ECHO queries`
  and `\set AUTOCOMMIT off`: first run applies, second reports nothing
  pending.
- Constraint names are `DatabaseVersion_version_uq` and `Other_version_uq`
  for two audit tables in the `Audit` schema.
- A final `commit` without `;` validates; `commit;` followed by a bare
  `insert` is rejected.
- A migration failing with division by zero reports the migration path and
  `ERROR:  division by zero`, rolls back, and writes no audit row. A file
  that fails validation with `--audit-schema=Typo2` leaves no schema behind.
- Removing an applied file fails with "has no local migration file";
  modifying it still fails the checksum check; `--path=nope` fails.
- `ps` shows `--dbname postgresql://sql_mig@…` without the password.
- No deprecation output under `error_reporting=E_ALL`.
- `bin/sql-mig --version` runs from a simulated `vendor/peterivan/sql-mig`
  layout through `$_composer_autoload_path`.

## 2026-10-18 — Design assessment, data details (proposal D)

### Decisions

The design review proposed five changes to data details that are cheap now
and expensive after the first tagged release. All five are in:

- **Checksums normalize line endings** (CRLF and CR to LF) before hashing,
  recorded in metadata as `checksumAlgorithm: "sha256-lf"`. A consumer with
  `core.autocrlf` no longer fails every applied migration. The stored
  `migration_content` keeps the original bytes.
- **`migration_content` is dollar-quoted** with a tag chosen to be absent
  from the content (`$sqlmig$`, `$sqlmig1$`, …), so backslashes and quotes
  need no escaping and `standard_conforming_strings` no longer matters.
  Short literals (version, filename, checksum, metadata JSON) keep quote
  doubling.
- **`version` is `collate "C"`** so ordering in SQL matches the byte-wise
  ordering the runner uses.
- **Execution time is measured** around the migration's psql run and added
  to the metadata with a best-effort `update` after the commit. A failure of
  that update is swallowed, because the migration is already committed and a
  failed run would wrongly suggest a re-run is needed. The `inject` method
  lost its `executionTimeMs` parameter.
- **Exit codes**: `SqlMigException` is abstract with three categories,
  `ConfigurationException` (1), `ValidationException` (2),
  `ExecutionException` (3). The commands return the exception's code.
- **Not done from the assessment**: the single-session redesign (A), the
  tool-owned transaction (B) and the gateway interface (C). They are
  separate issues; B is a format change that should land before the first
  tag.

### Validation

Fresh Compose PostgreSQL 16, host PHP 8.5.11, `composer ci` green:

- Exit codes: missing URL 1, missing directory 1, no BEGIN/COMMIT 2,
  connection failure 3.
- A migration containing `'it''s \ $sqlmig$ tricky'` applies and its stored
  content equals the file byte for byte.
- Metadata rows carry `executionTimeMs` (41 ms and 37 ms) and
  `checksumAlgorithm: sha256-lf`; the `version` column reports collation `C`.
- The applied file converted to CRLF still validates; appending a line fails
  the checksum check with exit 2.

### Tooling note

The checks for this entry were first run on the host, against the rule in
AGENTS.md. They were re-run inside the `app` container
(`docker compose exec app composer ci`, `validate`, `apply` against the
Compose database) and are green there; the container is the only supported
place to run them.
