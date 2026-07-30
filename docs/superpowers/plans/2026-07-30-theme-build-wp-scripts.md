# Theme Build → @wordpress/scripts Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace the theme's hand-rolled dual webpack build with `@wordpress/scripts`, producing one optimal output file per entry that PHP always loads from a single path.

**Architecture:** `@wordpress/scripts` (v27, same major as the plugins) drives the build via a thin `webpack.config.js` override that restores the theme's multi-entry layout (frontend/editor/print + auto-discovered patterns + block-styles) and points output at `assets/`. All three PHP asset loaders drop their `.min`/environment suffix and load one canonical path each. A pre-commit hook rebuilds so committed assets are always optimized.

**Tech Stack:** WordPress classic theme (PHP), `@wordpress/scripts` ^27, webpack 5 (via wp-scripts), SCSS, TypeScript, DDEV.

## Global Constraints

- Scope: `wp-content/themes/kzmielec` only. Do NOT touch plugins, content, markup, or visual output.
- Visual parity is the acceptance bar: rebuilt assets must render identically to today.
- Node/npm run **inside the DDEV web container** (`ddev exec` or `ddev npm`), matching the plugin build.
- Font output MUST stay `assets/webfont/[name][ext]` (no hash) — `header.php` preloads `cinzel-v26-latin.woff2` / `cinzel-v26-latin-ext.woff2` by exact name.
- Image output MUST stay `assets/media/[name][ext]` — SCSS `url()` references.
- Single output per entry: NO `.min.*` variants.
- Never delete or overwrite the static committed files `assets/js/logo.js` and `assets/js/admin/belief-settings.js` (enqueued by `LogoSettings.php` / `BeliefSettings.php`; not build outputs).
- Runtime npm deps required by the build: `masonry-layout` ^4.2.2, `imagesloaded` ^5.0.0.
- `package.json` MUST keep `"sideEffects": ["*.scss","*.css"]` — load-bearing; without it production tree-shaking drops the SCSS side-effect import and the CSS disappears.
- Expect autoprefixer to add vendor prefixes wp-scripts didn't before — CSS is not byte-identical; judge parity by rendering, not by diff.
- Cache-busting stays on `filemtime` in all loaders.
- Commit after each task. Do the whole migration on a feature branch, not `main`.

---

### Task 1: New wp-scripts build produces correct assets

**Files:**
- Create: `wp-content/themes/kzmielec/package.json`
- Create: `wp-content/themes/kzmielec/webpack.config.js`
- Create: `wp-content/themes/kzmielec/tsconfig.json`
- Move: `wp-content/themes/kzmielec/webpack/src/` → `wp-content/themes/kzmielec/src/`
- Verify against: `wp-content/themes/kzmielec/webpack/webpack.common.js` (source of the entry-discovery logic to port)

**Interfaces:**
- Produces build outputs consumed by the PHP loaders in Task 2:
  - `assets/js/frontend.js`, `assets/css/frontend.css`
  - `assets/js/editor.js`, `assets/css/editor.css`
  - `assets/js/print.js`, `assets/css/print.css`
  - `assets/css/patterns/{slug}-style.css`, `assets/js/patterns/{slug}-script.js`
  - `assets/css/block-styles/{name}.css`
  - `assets/webfont/[name][ext]`, `assets/media/[name][ext]`
  - lazy masonry chunk(s) loaded at runtime via webpack `publicPath`

- [ ] **Step 1: Create a feature branch**

```bash
cd /home/lukasz/projects/kzmielec
git checkout -b theme-build-wp-scripts
```

- [ ] **Step 2: Move the source tree**

```bash
cd wp-content/themes/kzmielec
git mv webpack/src src
```

Expected: `src/frontend.ts`, `src/sass/`, `src/patterns/`, `src/block-styles/`, `src/fonts/`, `src/image/`, `src/js/` now exist at theme root.

- [ ] **Step 3: Create `package.json`**

```json
{
  "name": "kzmielec-theme",
  "sideEffects": ["*.scss", "*.css"],
  "scripts": {
    "build": "wp-scripts build --source-maps",
    "start": "wp-scripts start",
    "postinstall": "cd ../../.. && git config core.hooksPath .githooks"
  },
  "dependencies": {
    "imagesloaded": "^5.0.0",
    "masonry-layout": "^4.2.2"
  },
  "devDependencies": {
    "@wordpress/scripts": "^27.9.0"
  }
}
```

Note: `--webpack-src-dir` is passed but the real entry/output control is the `webpack.config.js` below (wp-scripts auto-loads a root `webpack.config.js`).

- [ ] **Step 4: Create `webpack.config.js`**

Ports the two auto-discovery functions from the old `webpack.common.js` verbatim, then overrides entry, output, the CSS-extract filename, and font/image output on top of the wp-scripts default.

```js
const path = require('path');
const fs = require('fs');
const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

function getPatternEntries() {
	const dir = path.resolve(__dirname, 'src/patterns');
	const entries = {};
	if (!fs.existsSync(dir)) return entries;
	fs.readdirSync(dir).forEach((folder) => {
		const p = path.join(dir, folder);
		if (!fs.statSync(p).isDirectory()) return;
		const scss = path.join(p, 'style.scss');
		const ts = path.join(p, 'script.ts');
		if (fs.existsSync(scss)) entries[`patterns/${folder}-style`] = scss;
		if (fs.existsSync(ts)) entries[`patterns/${folder}-script`] = ts;
	});
	return entries;
}

function getBlockStyleEntries() {
	const dir = path.resolve(__dirname, 'src/block-styles');
	const entries = {};
	if (!fs.existsSync(dir)) return entries;
	fs.readdirSync(dir).forEach((file) => {
		if (!file.endsWith('.scss')) return;
		entries[`block-styles/${file.replace('.scss', '')}`] = path.join(dir, file);
	});
	return entries;
}

module.exports = {
	...defaultConfig,
	entry: {
		frontend: path.resolve(__dirname, 'src/frontend.ts'),
		editor: path.resolve(__dirname, 'src/editor.ts'),
		print: path.resolve(__dirname, 'src/print.ts'),
		...getPatternEntries(),
		...getBlockStyleEntries(),
	},
	output: {
		...defaultConfig.output,
		path: path.resolve(__dirname, 'assets'),
		filename: 'js/[name].js',
		chunkFilename: 'js/[name].js',
		publicPath: 'auto',
	},
	plugins: defaultConfig.plugins.map((plugin) =>
		plugin.constructor && plugin.constructor.name === 'MiniCssExtractPlugin'
			? new MiniCssExtractPlugin({ filename: 'css/[name].css' })
			: plugin
	),
	module: {
		...defaultConfig.module,
		rules: [
			...defaultConfig.module.rules,
			{
				test: /\.(woff2?|eot|ttf|otf)$/,
				type: 'asset/resource',
				generator: { filename: 'webfont/[name][ext]' },
			},
			{
				test: /\.(png|jpe?g|gif|svg)$/,
				type: 'asset/resource',
				generator: { filename: 'media/[name][ext]' },
			},
		],
	},
};
```

- [ ] **Step 5: Create `tsconfig.json`**

```json
{
  "compilerOptions": {
    "target": "ES2020",
    "module": "ESNext",
    "moduleResolution": "node",
    "strict": false,
    "esModuleInterop": true,
    "skipLibCheck": true,
    "allowJs": true,
    "noEmit": true
  },
  "include": ["src/**/*"]
}
```

- [ ] **Step 6: Install deps inside the container**

Run: `ddev exec "cd wp-content/themes/kzmielec && npm install"`
Expected: `node_modules/` created at theme root, no errors.

- [ ] **Step 7: Build and inspect output paths**

Run: `ddev exec "cd wp-content/themes/kzmielec && npm run build"`
Then: `ls assets/css/frontend.css assets/js/frontend.js assets/css/patterns/ assets/css/block-styles/ assets/webfont/ assets/media/`
Expected: `frontend.css`/`frontend.js` exist; pattern + block-style CSS exist; fonts under `assets/webfont/`; images under `assets/media/`.

If fonts/images land elsewhere (e.g. `assets/[hash][ext]` or under `images/`), the font/image rules above did not override the wp-scripts defaults — adjust by matching wp-scripts' existing asset rule `test` and replacing (not appending) it, then rebuild until fonts are at `assets/webfont/` and images at `assets/media/`. This is the one config detail that may need iteration.

- [ ] **Step 8: Verify visual parity + masonry chunk load**

```bash
# renders unchanged
curl -sk -o /dev/null -w "home %{http_code}\n" https://kzmielec.ddev.site/
curl -sk -o /dev/null -w "404  %{http_code}\n" https://kzmielec.ddev.site/nie-ma-xyz/
curl -sk -o /dev/null -w "search %{http_code}\n" "https://kzmielec.ddev.site/?s=wiara"
# frontend.css actually enqueued (dev env still loads non-min path = frontend.css)
curl -sk https://kzmielec.ddev.site/ | grep -oE "assets/css/frontend[^\"']*"
```

Then screenshot homepage, 404, `/?s=wiara`, and a belief page (`/wizja/`) with the existing Playwright pattern and confirm they look identical to before.

For masonry: the blog index renders `.news`; confirm the dynamic chunk is fetched:
```bash
curl -sk https://kzmielec.ddev.site/ | grep -oE "assets/js/frontend.js[^\"']*"   # loads
```
Load a `.news` page in Playwright and confirm no console error and the masonry chunk request returns 200 (network). If `.news` has no cards there are no visual changes — the goal is that the chunk *loads without 404* (publicPath correct).

Fonts (most sensitive): confirm the `@font-face` resolves and the file loads.
```bash
# emitted at the exact preloaded name, no hash:
ls assets/webfont/cinzel-v26-latin.woff2 assets/webfont/cinzel-v26-latin-ext.woff2
# the @font-face url in the built CSS points at webfont/:
grep -oE "url\([^)]*cinzel[^)]*\)" assets/css/frontend.css
# and the browser actually fetches it (200, not 404):
curl -sk -o /dev/null -w "font %{http_code}\n" https://kzmielec.ddev.site/wp-content/themes/kzmielec/assets/webfont/cinzel-v26-latin.woff2
```
In the Playwright run, confirm the page renders in Cinzel (serif) — a 404 font would fall back to a system serif and the headings would look different.

Autoprefixer note: the CSS will contain **more** vendor prefixes than before
(wp-scripts runs autoprefixer; the old build did not). This is expected — judge
the screenshots on rendering, not on a text diff of the CSS.

- [ ] **Step 8b: Verify CSS is written to a file under watch**

Run `ddev theme:watch` (or `npm run start`) in the background, edit a color in
`src/sass/pages/page.scss`, save, and confirm `assets/css/frontend.css` changes
on disk (wp-scripts extracts CSS to a file in watch mode, not JS-injected — PHP
loads it as a `<link>`). Stop watch when done.

```bash
ls -la --time-style=+%T assets/css/frontend.css   # mtime updates after a save
```

- [ ] **Step 9: Commit**

```bash
git add wp-content/themes/kzmielec/package.json wp-content/themes/kzmielec/webpack.config.js wp-content/themes/kzmielec/tsconfig.json wp-content/themes/kzmielec/src wp-content/themes/kzmielec/assets
git commit -m "Theme build: add @wordpress/scripts config, move src to theme root"
```

---

### Task 2: Drop the .min suffix in all three PHP loaders + fix pattern glob

**Files:**
- Modify: `wp-content/themes/kzmielec/App/BasicTheme/RegisterAssets.php`
- Modify: `wp-content/themes/kzmielec/App/Core/BlockStyles.php`
- Modify: `wp-content/themes/kzmielec/App/Core/PatternAssets.php`

**Interfaces:**
- Consumes: the single-path build outputs from Task 1.

- [ ] **Step 1: `RegisterAssets.php` — remove suffix**

Delete the `private string $suffix;` property, the `$this->suffix = $this->get_asset_suffix();` line in the constructor, and the entire `get_asset_suffix()` method. Change the four enqueue paths to drop `{$this->suffix}`:

```php
$this->enqueue_asset( 'style', 'kzmielec-admin-style', '/assets/css/backend.css' );
// ...
$this->enqueue_asset( 'script', 'kzmielec-script', '/assets/js/frontend.js' );
$this->enqueue_asset( 'style', 'kzmielec-styles', '/assets/css/frontend.css' );
$this->enqueue_asset( 'style', 'kzmielec-print-styles', '/assets/css/print.css', array(), true, 'print' );
```

- [ ] **Step 2: `BlockStyles.php` — remove suffix**

Delete its `get_asset_suffix()` method and the two `$asset_suffix = ...` lines; change both paths:

```php
$css_path = '/assets/css/block-styles/dynamic-images-banner-hero.css';
// ...
$css_path = '/assets/css/block-styles/heading-section-line.css';
```

- [ ] **Step 3: `PatternAssets.php` — remove suffix AND fix source glob**

Delete its `get_asset_suffix()` method and every `$asset_suffix = ...` line; drop `{$asset_suffix}` from all four `patterns/{slug}-style` / `{slug}-script` paths. Change the source-dir glob:

```php
// was: glob( "{$theme_dir}/webpack/src/patterns/*", GLOB_ONLYDIR );
$pattern_dirs = glob( "{$theme_dir}/src/patterns/*", GLOB_ONLYDIR );
```

- [ ] **Step 4: Verify no suffix logic remains**

Run: `grep -rn "asset_suffix\|get_asset_suffix\|{\$this->suffix}\|webpack/src" wp-content/themes/kzmielec/App/`
Expected: no matches.

- [ ] **Step 5: Verify patterns + block styles still enqueue**

```bash
curl -sk https://kzmielec.ddev.site/ | grep -oE "assets/css/patterns/[^\"']+|assets/css/block-styles/[^\"']+|assets/css/frontend[^\"']+"
```
Expected: pattern + block-style + frontend CSS URLs present. Screenshot the homepage and a belief page — pattern styling (banner-hero, page-belief) intact.

- [ ] **Step 6: Commit**

```bash
git add wp-content/themes/kzmielec/App
git commit -m "Theme: drop .min/env asset suffix in all 3 loaders; fix pattern source glob"
```

---

### Task 3: Clean orphaned dual-output from assets/

**Files:**
- Delete (build artifacts): `assets/**/*.min.js`, `assets/**/*.min.css`, stale chunk files.
- Preserve: `assets/js/logo.js`, `assets/js/admin/belief-settings.js`.

- [ ] **Step 1: List what will be removed (dry run)**

```bash
cd /home/lukasz/projects/kzmielec/wp-content/themes/kzmielec
find assets -name "*.min.js" -o -name "*.min.css" | sort
ls assets/js/362.min.js assets/js/632.min.js assets/js/src_js_masonry_masonry_js.js assets/js/vendors-*.js 2>/dev/null
```
Confirm `assets/js/logo.js` and `assets/js/admin/belief-settings.js` are NOT in the list.

- [ ] **Step 2: Remove orphaned min + stale chunk files**

```bash
find assets -name "*.min.js" -delete
find assets -name "*.min.css" -delete
rm -f assets/js/362.min.js assets/js/632.min.js
rm -f assets/js/src_js_masonry_masonry_js.js assets/js/src_js_masonry_masonry_js.js.map
rm -f assets/js/vendors-*.js assets/js/vendors-*.js.map
```

- [ ] **Step 3: Rebuild and confirm the new masonry chunk exists**

```bash
ddev exec "cd wp-content/themes/kzmielec && npm run build"
ls assets/js/ | grep -iE "masonry|vendors|chunk" || echo "check chunk name"
git status --short assets/js/logo.js assets/js/admin/belief-settings.js
```
Expected: a fresh masonry chunk exists; the two static files are unchanged (no `M`/`D`).

- [ ] **Step 4: Verify site still renders + masonry chunk 200**

Re-run the Task 1 Step 8 checks. Expected: homepage/404/search render 1:1; masonry chunk loads without 404.

- [ ] **Step 5: Commit**

```bash
git add -A wp-content/themes/kzmielec/assets
git commit -m "Theme: remove orphaned .min/dual-output assets; single build output"
```

---

### Task 4: Update ddev commands and delete the old webpack/ folder

**Files:**
- Modify: `.ddev/commands/web/build:all`
- Modify: `.ddev/commands/web/watch:all`
- Rename/replace: `.ddev/commands/web/theme:dev` + `theme:prod` → `theme:build`
- Keep: `.ddev/commands/web/theme:watch` (repoint to wp-scripts start)
- Delete: `wp-content/themes/kzmielec/webpack/`

- [ ] **Step 1: Replace `theme:dev` and `theme:prod` with `theme:build`**

```bash
cd /home/lukasz/projects/kzmielec
git rm .ddev/commands/web/theme:dev .ddev/commands/web/theme:prod
```

Create `.ddev/commands/web/theme:build`:
```bash
#!/bin/bash

## Description: Build theme assets (single optimized output)
## Usage: theme:build
## Example: ddev theme:build

cd /var/www/html/wp-content/themes/kzmielec && npm run build
```

- [ ] **Step 2: Repoint `theme:watch`**

Edit `.ddev/commands/web/theme:watch` command body:
```bash
cd /var/www/html/wp-content/themes/kzmielec && npm run start
```

- [ ] **Step 3: Update `build:all`**

Body:
```bash
echo "=== Building plugin ==="
cd /var/www/html/wp-content/plugins/custom-block-package && npm run build
echo ""
echo "=== Building theme ==="
cd /var/www/html/wp-content/themes/kzmielec && npm run build
echo ""
echo "=== All builds complete ==="
```

- [ ] **Step 4: Update `watch:all`**

Replace the theme line `cd .../themes/kzmielec/webpack && npm run watch &` with:
```bash
cd /var/www/html/wp-content/themes/kzmielec && npm run start &
```
(plugin line unchanged)

- [ ] **Step 5: Delete the old webpack folder**

```bash
git rm -r wp-content/themes/kzmielec/webpack
rm -rf wp-content/themes/kzmielec/webpack   # in case node_modules is untracked
```

- [ ] **Step 6: Verify commands work end-to-end**

```bash
ddev theme:build 2>&1 | tail -3          # succeeds
ls wp-content/themes/kzmielec/webpack 2>&1 | grep -q "No such" && echo "webpack/ gone"
grep -rn "webpack" .ddev/commands/web/ | grep -v README   # no refs remain
```

- [ ] **Step 7: Commit**

```bash
git add .ddev/commands/web
git commit -m "ddev: single theme:build command; remove dev/prod split and webpack/"
```

---

### Task 5: Add the pre-commit build hook

**Files:**
- Create: `.githooks/pre-commit`

**Interfaces:**
- Consumes: `ddev theme:build` (Task 4).
- `core.hooksPath` is repointed to `.githooks` by the theme `package.json` `postinstall` (Task 1 Step 3).

- [ ] **Step 1: Create `.githooks/pre-commit`**

```bash
#!/bin/bash
# Rebuild theme assets when theme source/config is staged, so committed
# assets are always the optimized build. Skips when nothing theme-related
# is staged.
set -e

THEME="wp-content/themes/kzmielec"
if git diff --cached --name-only | grep -qE "^${THEME}/(src/|webpack\.config\.js|package\.json|tsconfig\.json)"; then
	echo "pre-commit: theme source staged → building assets…"
	if ! ddev theme:build >/dev/null 2>&1; then
		echo "pre-commit: 'ddev theme:build' failed (is ddev running?). Aborting commit." >&2
		exit 1
	fi
	git add "${THEME}/assets"
	echo "pre-commit: assets rebuilt and staged."
fi
```

- [ ] **Step 2: Make it executable and ensure the hooks path is set**

```bash
chmod +x .githooks/pre-commit
git config core.hooksPath .githooks
git config core.hooksPath   # prints ".githooks"
```

- [ ] **Step 3: Verify the hook fires**

```bash
# touch a source file and stage it
printf '\n/* hook test */\n' >> wp-content/themes/kzmielec/src/sass/frontend.scss
git add wp-content/themes/kzmielec/src/sass/frontend.scss
git commit -m "test: verify pre-commit build hook"
```
Expected: commit output shows "building assets…" and "assets rebuilt and staged"; the commit includes rebuilt `assets/`.

Then revert the test edit:
```bash
git revert --no-edit HEAD   # or: git reset --soft HEAD~1 and undo the scss line
```

- [ ] **Step 4: Commit the hook**

```bash
git add .githooks/pre-commit
git commit -m "hooks: pre-commit rebuilds theme assets so commits ship optimized"
```

---

### Task 6: Final full verification and merge

- [ ] **Step 1: No suffix / no webpack references anywhere**

```bash
cd /home/lukasz/projects/kzmielec
grep -rn "asset_suffix\|\.min\.\(js\|css\)\|webpack/src" wp-content/themes/kzmielec/App wp-content/themes/kzmielec/*.php .ddev/commands
```
Expected: no matches (aside from unrelated vendor).

- [ ] **Step 2: One output per entry, static files intact**

```bash
find wp-content/themes/kzmielec/assets -name "*.min.*" | head   # empty
ls wp-content/themes/kzmielec/assets/js/logo.js wp-content/themes/kzmielec/assets/js/admin/belief-settings.js
```

- [ ] **Step 3: Full visual regression**

Screenshot with Playwright and eyeball against pre-migration:
- `/` (homepage: hero, navigable-tiles, meetings, FB feed, Instagram, map)
- `/nie-ma-xyz/` (404)
- `/?s=wiara` (search grid)
- `/wizja/` (belief page — pattern + block styles)
- `/roznica-wyznan/` (comparison accordion)

Expected: all visually identical to before the migration.

- [ ] **Step 4: Merge the branch**

```bash
git checkout main
git merge --no-ff theme-build-wp-scripts -m "Theme build: migrate to @wordpress/scripts (single optimized output)"
```

- [ ] **Step 5: Confirm clean tree**

```bash
git status --short   # only untracked .claude/settings.json expected
```

---

## Self-review notes

- Spec coverage: toolchain (T1), single output + one path (T1+T2), all 3 loaders + glob (T2), font/image paths (T1 Step 7 constraint), masonry deps + chunk (T1), static-file preservation (T3), cleanup (T3), ddev commands + webpack removal (T4), pre-commit hook (T5), visual parity (T1/T3/T6). All covered.
- The one genuine unknown is wp-scripts v27's exact font/image asset-rule override (T1 Step 7) — handled with an explicit inspect-and-adjust step rather than a guessed final config.
- `.news`/masonry currently has no cards (0 posts); verification targets *chunk loads without 404* (publicPath correctness), not visual layout.
