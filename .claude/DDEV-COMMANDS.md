# DDEV commands -- Kzmielec

The project's custom DDEV commands. The files live in `.ddev/commands/web/` and
**are in the repository** (`.gitignore` excludes the rest of `.ddev/` but not
`commands/`), so they work straight after a clone. They used to be ignored and had
to be recreated by hand, which meant every machine could be building something
different.

## Build

| Command | What it does |
|---------|------|
| `ddev theme:build` | Builds the theme -- **one optimised version** (minified, with source maps) |
| `ddev plugin:build` | Builds **both** block plugins: custom-block-package and comparison-of-religions |
| `ddev build:all` | Builds everything: the theme and both block plugins (stops at the first failure) |

`custom-posts` has no build -- its `src/` is runtime code, not sources to compile.

Until 2026-08-04 `build:all` and `plugin:build` skipped `comparison-of-religions`
and ignored exit codes, so a failed or skipped build still ended with "All builds
complete".

## Watch

| Command | What it does |
|---------|------|
| `ddev theme:watch` | Watches the theme -- same file names, unminified output |
| `ddev watch:all` | Watches the theme and both block plugins in parallel (Ctrl+C to stop) |

Run **one at a time**: `watch` while iterating, `build` to release.

## How the theme build works

The theme uses `@wordpress/scripts`, like the plugins, with a thin
`webpack.config.js` in the theme directory. There is **one output file** per
entry, for example `assets/css/frontend.css` -- no `.min` variant. PHP always
loads the same path locally and in production; only the contents differ (`watch`
leaves it unminified, `build` minifies it).

The build **never runs on the server**. What goes to production is the built,
committed file from `assets/`.

The pre-commit hook (`.githooks/pre-commit`) runs `ddev theme:build` and stages
`assets/` by itself whenever a commit touches `src/` or the build config, so
there is nothing to remember before committing.

### Watch mode under WSL2 and Docker

Watch mode is unreliable for edits made from the host: native inotify events do
not cross the bind mount, and `watchOptions.poll` in the config is a mitigation,
not a fix. If you save a file and see no change, run `ddev theme:build` -- it is
immediate and certain.

## Other useful commands

| Command | What it does |
|---------|------|
| `ddev wp cache flush` | Clears the WordPress cache |
| `ddev wp user list` | Lists WP users |
| `ddev wp user update <login> --user_pass=<password>` | Resets a password |

## Paths inside the DDEV container

- Theme: `/var/www/html/wp-content/themes/kzmielec/` (sources in `src/`, output in `assets/`)
- Plugin: `/var/www/html/wp-content/plugins/custom-block-package/`

## Adding a command

1. Create a file at `.ddev/commands/web/<command-name>`
2. Give it a header:
   ```bash
   #!/bin/bash
   ## Description: What the command does
   ## Usage: command-name
   ## Example: ddev command-name
   ```
3. `chmod +x .ddev/commands/web/<command-name>`
4. DDEV picks the command up on its own
