# AGENTS.md

> **⚠️ HIGHEST-PRIORITY — READ FIRST.** This is the **single source of truth** for
> the repo and binds **every agent** (Claude Code and any subagent/tool it spawns).
> These rules override default behavior; follow them exactly. Root `CLAUDE.md`
> imports this file so it auto-loads into context each session.

Guidance for AI agents (and humans) working in this repo.

## Project summary

`peterivan/sql-mig` is a standalone PostgreSQL SQL file migration runner: a
Composer library with a `bin/sql-mig` CLI (`validate`, `apply`) that executes
plain `.sql` migration files through `psql` and records them in an audit
table. Public repository on GitHub (`peterivan/sql-mig`).

**Language:** everything is written in English — code, comments, issues, pull
requests, dev notes, documentation and agent config. No other language.

## Repo layout

- `bin/sql-mig` — CLI entry point.
- `src/` — the package (`PeterIvan\SqlMig\`): `Cli/`, `Config/`, `Migration/`,
  `Audit/`, `Runner/`, `Exception/`.
- `database/migrations/` — sample migrations used by the local environment.
- `docs/` — `dev-notes/` (per branch) and `shortcomings/` (per issue). Nothing
  else (see *Where files go*).
- `.agents/` — agent config (skills, subagents); `.claude` is a symlink to it.
- `Dockerfile`, `compose.yaml` — local FrankenPHP + PostgreSQL environment.
- `tmp/` — gitignored scratch space for dumps and throwaway scripts.
- `PLAN.md`, `PLAN-TODO.md`, `HANDOFF.md` — the initial design and hand-off
  notes from before this process existed. Read-only history; new notes of
  that kind go to the issue or the dev note.

## Tech stack

PHP >= 8.5, Symfony Console, `psql` (PostgreSQL client) at runtime. Tooling:
PHPStan (level max) and Mago (lint, analyze, format) via Composer scripts.
Local environment: Docker Compose with FrankenPHP and PostgreSQL 16.

## Work intake, issues & branches — REQUIRED

The unit of work is the **GitHub issue**. One issue = one branch = one pull
request = one dev note. The `github-issue` skill (`.agents/skills/github-issue/`)
is the procedure for drafting and filing issues, creating their branch and
pull request, and merging. Branches and pull requests are created through
`gh`, never with plain `git` commands.

1. **Issue first.** Every feature, bugfix or infrastructure task starts with an
   existing GitHub issue. If you do not know its number, stop and ask — do
   not invent one, and do not start on `main`. The issue records **why** the
   work exists: the problem, context, reproduction, acceptance expectations,
   constraints. Titles are short and in English. **Every issue carries at least one
   label** from the existing ones (`gh label list`); do not invent new labels
   without asking.
2. **Branch comes from the issue** via `gh issue develop <number>`, which
   creates a linked branch `<number>-<slug>` on GitHub from `main`. Never
   `git checkout -b`, `git switch -c`, `git branch <name>` or `git push -u` to
   a branch GitHub has not created.
3. **One branch per issue.** If a branch for the issue already exists (locally
   or on origin), switch to it and continue; do not open a second one. The
   pull request closes the issue when it merges (`Closes #<number>` in its
   body); a closed issue is done and is never reopened. Records that arrive
   after the merge go as a comment on that closed issue. Leftover work gets
   its own issue. Analysis-only sessions write their findings into the issue.
4. **Never work around the process.** No `--no-verify`, no helper scripts or
   wrappers to "automate" branch or pull request creation. If a rule seems
   wrong, raise it in its own issue instead of bypassing it.

## Dev notes — REQUIRED

Every branch keeps a running technical decision log under `docs/dev-notes/`,
named after the branch: `docs/dev-notes/<branch-name>.md` (branch
`7-normalize-pgsql-scheme` → `docs/dev-notes/7-normalize-pgsql-scheme.md`).
Because one issue has one branch, the note is also the issue's single note.

- The note is a **running log**, updated continuously as work progresses — not
  written once at the end. It is not a transcript of user-agent communication
  and does not duplicate the issue.
- Capture technically significant information: the problem and its origin,
  findings and root cause, decisions and their reasons, rejected alternatives
  and dead ends, major implementation areas, validation performed, what went
  wrong and how it was resolved, known limitations and follow-up work. Use
  headings such as **Problem**, **Findings**, **Decisions**, **Why**,
  **Implementation** or **Validation** when they help; do not mechanically
  require every heading for a small task.
- If the user supplies an implementation-relevant decision from an external
  conversation, record the decision, its reason and constraints like any other
  decision. Do not record where it came from unless that is itself relevant.
- Prefer short, dated, append-only entries over rewriting history. Keep
  decisions and their *why*, not just the final state (the code shows that).
- Create the note with the first commit on the branch. Never create a second
  `docs/dev-notes/<number>-*.md` for the same issue — append to the existing
  one.

**Cadence:** update the note at meaningful checkpoints (confirmed root cause,
design decision, rejected approach, scope change, final validation). Do not
add noise for every shell command. At the end of a session, append a short
dated summary of the session's work.

## Git & commits — REQUIRED (all agents, same style)

**When to commit.** Only when the user asks. Never commit or push unprompted.
If you are on `main`, stop: satisfy the issue intake rule and switch to the
branch GitHub created for the issue.

**Message shape.**
- **Subject** — one concise line (~50–72 chars), no trailing period, no
  `type:` prefix. State *what changed*.
- **Body** (for anything non-trivial) — blank line, then *what* and *why* (not
  how; the diff shows how), wrapped ~72 cols.
- **Trailer** — end with a `Co-Authored-By:` line attributing the agent, e.g.

  ```
  Co-Authored-By: Claude Fable 5.1 <noreply@anthropic.com>
  ```

- **No marketing/emoji in commit messages.** The "generated with" note belongs
  in **pull request descriptions**: Claude Code ends them with
  `🤖 Generated with [Claude Code](https://claude.com/claude-code)`.

**Scope.** One logical change per commit. Run `composer ci` green before
committing code.

**Traceability.** Issue = why the task exists. Dev note = findings, reasoning,
decisions. Commit = the concrete implementation. Pull request = the final
implementation and validation summary, and it closes its issue with
`Closes #<number>`. Commits do not repeat the issue number unless asked.

## Where files go — REQUIRED

- **Analysis, findings, options, review notes** → the GitHub issue (as the
  description or a comment) or the branch dev note. Not a new Markdown file.
- **Out-of-scope findings from a review** that are candidate follow-up issues →
  `docs/shortcomings/<number>-<slug>.md`, one file per issue, each item a
  short paragraph with a possible fix. File the issue when the item is picked
  up and remove it from the file.
- **`docs/`** holds only `dev-notes/` and `shortcomings/`. No new top-level
  files or folders in `docs/` without an issue that says so.
- **Repo root** gets no new files. Dumps, samples, audits and hand-off notes
  belong in the issue, the dev note or `tmp/` (gitignored).
- **Agent config** (skills, Claude Code subagents) lives in `.agents/`. Claude
  Code reads it through the `.claude` symlink. Never create a real `.claude/`
  directory, no settings, hooks or helper scripts for process enforcement
  (rules live here).
- **No ad hoc or temporary scripts.** A throwaway command runs inline in the
  shell or from `tmp/` and is never committed.

## Editing guidance — REQUIRED

- Make the smallest change that fixes the current problem. Do not refactor,
  rename or "clean up" around it; if that is needed, it is its own issue.
- When the user names the approach up front, build it that way. Disagree in
  one line if you must, then follow the stated approach.
- Agents run containers only from what `compose.yaml` defines. Never
  `docker run`/pull other images on your own; ask first.
- Do not edit `vendor/` or fixtures to make a check pass — fix the cause.
- This is a library: keep the public surface small, keep PHPStan at level max
  and Mago green, and do not add dependencies without an issue that says so.

## Tooling — run inside the app container

PHP tools (`composer`, `php`, `phpstan`, `mago`, `bin/sql-mig`) run **inside
the `app` container**, which pins PHP 8.5 and the `psql` client.

```sh
docker compose up -d --build                      # start the stack (down to stop)
docker compose exec app composer install
docker compose exec app composer ci               # phpstan + mago lint/analyze/format check (alias of check)
docker compose exec app composer mago:format      # apply formatting
docker compose exec app php bin/sql-mig validate
docker compose exec app php bin/sql-mig apply
```

The `database` service is PostgreSQL 16, reachable from the container as
`postgresql://sql_mig:sql_mig@database:5432/sql_mig` (the container's default
`DATABASE_URL`) and from the host on port `54329`.

### Delegating to subagents (Claude Code)

Large tool output stays in the session's context, so route it to subagents in
`.agents/agents/` that report briefly and never fix anything themselves:

- `composer ci`, test runs, `sql-mig` runs against the local database → `ci-runner`
- broad code searches → the built-in `Explore` agent
- a single small check may stay in the main session with trimmed output (`| tail`)

## Checks before a pull request is marked ready — REQUIRED

There is no CI on this repository for now, so the agent runs the gates
itself before `gh pr ready` and records the result in the dev note:

- the source branch is `<number>-…`, `docs/dev-notes/<branch>.md` exists and
  the issue has no second note;
- `composer ci` is green inside the `app` container.

## Runtime validation — REQUIRED

Green static analysis does not prove the runner works. Before marking a pull
request ready, run the changed behaviour against the local PostgreSQL
(`validate` and `apply` through the `app` container) and record what was
exercised in the dev note. If that is not possible, say so in the note and in
the pull request; do not present it as done.
