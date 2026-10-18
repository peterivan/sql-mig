---
name: github-issue
description: Draft and file a GitHub issue for this repo, then create its linked branch and draft pull request with gh, and later mark it ready and squash-merge it. Use when the user asks to draft, write up, file or create an issue, to start work on an issue (create its branch / PR), or to merge its PR.
allowed-tools: Bash(gh:*), Bash(git fetch:*), Bash(git switch:*), Bash(git branch:*), Bash(git pull:*)
---

Issues are the unit of work in this repo (see `AGENTS.md` → *Work intake*). This
skill covers four steps. Each one runs only when the user asks for it.

## 1. Draft

Show the draft in chat. Never file it while drafting.

- **Title**: short English, says what should change
  (e.g. `Normalize Doctrine pgsql:// URLs before invoking psql`).
- **Description** records **why** the work exists, not how to build it. Use the
  sections that apply:
  - **Request**: who wants what and why.
  - **Current behaviour**: what happens today, with facts verified in the code
    or against the local database.
  - **Expected behaviour**: acceptance expectations, written as observable
    behaviour.
  - **To verify**: open questions.
  - **Out of scope**: what this issue does not cover.
- Leave out implementation detail (class names, file paths, the fix itself)
  unless the user asks for it. That belongs in the dev note and the PR.
- **Labels**: every issue gets at least one. Read the current labels with
  `gh label list` on every draft; do not rely on a remembered list. Propose
  the best-fitting ones: one for the kind of work (`bug`, `enhancement`,
  `documentation`, …) plus any that fit. Pick only from existing labels; never
  create a new label unless the user asks.
- Revise the draft on feedback and show it again.

## 2. File, only on the user's explicit go

A request to draft or revise is not a go. Wait for a separate "file it".

Write the description to a file in the scratchpad (never the repo), then run:

```sh
gh issue create --title "<title>" --body-file <scratchpad>/issue.md \
  --label "<label>" [--label "<label>" …]
```

Add no assignee unless the user asks. Report the number, URL and labels.

## 3. Branch and draft pull request

1. **Check the issue is open.** Run `gh issue view <number>`. If it is closed,
   stop: a closed issue is never reopened, so the work needs a new issue.
2. **Check for an existing branch and PR.** Run `git fetch --prune`, then
   `gh issue develop --list <number>` and `gh pr list --search "<number>"`.
   One issue gets one branch.
   - Branch and PR both exist: switch to the branch and stop.
   - Branch exists but has no PR: `git switch <branch>` and continue at step 5
     once there is a commit to push.
3. **Build the slug** from the issue title: lowercase, replace every run of
   non `[a-z0-9]` characters with `-`, trim `-` at both ends, keep it under
   ~60 characters cutting at a `-` boundary. Branch = `<number>-<slug>`,
   e.g. `7-normalize-doctrine-pgsql-urls`.
4. **Create the linked branch on GitHub from `main` and switch to it:**

   ```sh
   gh issue develop <number> --base main --name <number>-<slug> --checkout
   ```

   This is the only way branches are created here (`AGENTS.md`). The dev note
   `docs/dev-notes/<branch>.md` is created with the first commit on the
   branch, not here.
5. **Open the draft PR** after the first commit is pushed
   (`git push -u origin <branch>` is allowed only for a branch created in
   step 4):

   ```sh
   gh pr create --draft --base main --title "<issue title>" \
     --body-file <scratchpad>/pr.md
   ```

   The body ends with a line `Closes #<number>` (exactly once) followed by the
   `🤖 Generated with [Claude Code](https://claude.com/claude-code)` line.
   Verify with `gh pr view --json body`.
6. **Assign to the user.** Whenever you switch to an issue's branch to work on
   it, assign both the issue and its PR to the authenticated user
   (`gh api user --jq .login`), unless the user names someone else:

   ```sh
   gh issue edit <number> --add-assignee <login>
   gh pr edit <pr> --add-assignee <login>
   ```

## 4. Mark ready and merge, only when the user asks

```sh
gh pr ready <pr>
gh pr checks <pr> --watch
gh pr merge <pr> --squash --delete-branch
```

The merge waits for the required checks. If GitHub refuses the merge, read
`gh pr view <pr> --json mergeStateStatus,mergeable` for the reason; a branch
behind `main` is updated with `gh pr update-branch <pr>`. After the merge the
issue closes automatically (`Closes #<number>`). Then run
`git switch main && git pull --ff-only`.
