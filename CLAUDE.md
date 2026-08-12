# kzmielec.pl — working rules

These rules are repeated here on purpose. They also live in `~/projects/CLAUDE.md`,
which is shared by every Paradise Media project and **is not part of this
repository** — on any other machine it is absent, and then nothing states them.
Where the two disagree, this file wins: it describes this site.

## How we work

- **Ask before acting.** No code changes without a green light, and no code shown
  in a reply without being asked for.
- **Polish in conversation. English in everything committed** — commit messages,
  PR descriptions, code comments, script output and documentation — and without
  Polish diacritics.
- **Commit only on explicit consent, and push on a separate one.** Finished work
  waits in the working tree until then.
- **Work on `main` directly.** No branches, no worktrees, no PRs: DDEV serves the
  main checkout only, so work on a branch is invisible in the browser. This is the
  one place the shared rules say the opposite.
- **Nothing on production without consent for that specific change.**
- SOLID, DRY, WordPress Coding Standards. PHPStan level 8 in all four own packages.
- Sizes in `rem`, never `px` (16px = 1rem).

## How this project differs from the shared rules

The shared file assumes Kinsta and a GitHub Actions deployment through the DevOps
reusable workflow. Neither applies here: this site runs on a LiteSpeed host and is
deployed by hand with rsync, following a runbook kept outside the repository.
Access data belongs outside every repository — the pre-commit hook refuses a commit
that carries it, and `scripts/scan-secrets.sh` checks the history.

This repository is **public**, and it holds the whole WordPress install.

## Local environment

DDEV. The `php` on PATH is the Windows build and hangs PHPStan, so quality gates
run through the container:

```bash
ddev exec 'cd wp-content/themes/kzmielec && php vendor/bin/phpstan analyse --no-progress'
bash scripts/tests/run-all.sh    # from the HOST: the runner drives `ddev wp` itself
```

## Where to read next

`.claude/PROJECT-NOTES.md` for the current state, the decisions that still hold and
the traps that cost time to find. `README.md` for the shape of the install, linking
to a README in each of the five own packages — a fact lives in the package that
owns it.

<!-- cce-block-version: 4 -->
## Context Engine (CCE)

This project uses Code Context Engine for intelligent code retrieval and
cross-session memory.

### Searching the codebase

**You MUST use `context_search` instead of reading files directly** when
exploring the codebase, answering questions about code, or understanding how
things work. This is a hard requirement, not a suggestion. `context_search`
returns the most relevant code chunks with confidence scores instead of whole
files, and tracks token savings automatically.

When to use `context_search`:
- Answering questions about the codebase ("how does X work?", "where is Y?")
- Exploring structure or architecture
- Finding related code, functions, or patterns
- Any time you would otherwise read a file just to understand it

When to use `Read` instead:
- You need to edit a specific file (read before editing)
- You need the exact, complete content of a known file path

Other search tools:
- `expand_chunk` — get full source for a compressed result
- `related_context` — find what calls/imports a function

### Cross-session memory — use it actively

This project has persistent memory across Claude Code sessions. **You must
use it both ways: recall before answering, record after deciding.** Memory
that is not recorded is lost; memory that is not recalled does nothing.

**Before answering a non-trivial question, call `session_recall`.**
Especially when:
- The question touches architecture, design, or naming choices
- The user asks "what / why / how did we ..."
- You are about to recommend an approach the team may have already chosen
  or already rejected

Pass a topic phrase, not a single word — e.g. `session_recall("auth flow")`,
not `session_recall("auth")`. Recall is vector-similarity-based, so paraphrases
match. If recall returns relevant entries, lead with them ("Per a prior
decision: ...") instead of re-deriving the answer.

**After making a non-obvious decision, call `record_decision`.** Especially:
- Choosing one library / pattern / approach over another
- Resolving an ambiguity in the spec or requirements
- Establishing a convention the project should follow going forward
- Anything you would not want to re-litigate next session

Format: `record_decision(decision="...", reason="...")`. Keep both fields
short and specific — they are surfaced verbatim at the start of future
sessions.

**After meaningful work in a file, call `record_code_area`.** Especially when:
- You added or substantially modified a function/class
- You traced through a non-obvious flow and want future-you to find it fast

Format: `record_code_area(file_path="...", description="...")`.

Skip recording for trivial reads, formatting changes, or one-off lookups —
the goal is durable signal, not an event log.

### Drilling deeper from a recall hit

`session_recall` results are tagged with the source session id, e.g.
`[turn sid:abc123|n:5]`. To drill in:

- `session_timeline(session_id="abc123")` — walk the per-turn summaries of
  that session in order. Use this when the user asks "what was the
  reasoning?" or "how did we get there?".
- `session_event(event_id=N)` — fetch a specific tool event's raw input
  and output (capped at 4 KB at read time). Use this when a turn summary
  references a tool result you actually need to inspect.

Both are read-only and cheap. Prefer them over re-running tool calls or
asking the user to re-paste context.

### Output style

Respond in compressed style. Drop articles (a, an, the) in prose. Use
sentence fragments over full sentences. Use short synonyms (fix not resolve,
check not investigate). Pattern: [thing] [action] [reason]. [next step].
No filler, hedging, pleasantries, trailing summaries, or restating what
the user said. One sentence if one sentence is enough.

When suggesting code changes, show only the changed lines with 3 lines of
context. Never rewrite entire files. Multiple changes in one file: show each
change separately. Never echo back unchanged code the user already has.

Code blocks, file paths, commands, error messages: always written in full.
Security warnings and destructive action confirmations: use full clarity.
<!-- /cce-block -->
