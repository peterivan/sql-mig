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
