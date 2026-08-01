#!/usr/bin/env node

// The theme is the source of the design tokens, but a plugin builds on its own
// and must render under any theme, so it cannot import theme sources. Three
// things are therefore duplicated on purpose:
//
//   1. `src/scss/abstracts/_type.scss` — mirrored verbatim in both plugins
//   2. the breakpoints in `src/scss/abstracts/_tokens.scss` — shared names must
//      hold the same values (a plugin may add steps of its own)
//   3. the font-size scale in the theme's `theme.json`, which is JSON and cannot
//      import SCSS
//
// "Keep them in sync" in a comment is a wish. This turns it into a check.

import { readFileSync } from 'node:fs';

const THEME = 'wp-content/themes/kzmielec';
const PLUGINS = [
	'wp-content/plugins/custom-block-package',
	'wp-content/plugins/comparison-of-religions',
];

const problems = [];
const read = (path) => readFileSync(path, 'utf8');

// 1. The mirrored scale files must be byte-identical everywhere.
const MIRRORED = ['_type.scss', '_design.scss'];
const mirrorPath = (pkg, file) => `${pkg}/src/scss/abstracts/${file}`;
for (const file of MIRRORED) {
	const source = read(mirrorPath(THEME, file));
	for (const plugin of PLUGINS) {
		if (read(mirrorPath(plugin, file)) !== source) {
			problems.push(
				`${mirrorPath(plugin, file)} has drifted from ` +
					`${mirrorPath(THEME, file)} — copy the theme file over it.`
			);
		}
	}
}
const source = read(mirrorPath(THEME, '_type.scss'));

// 2. Breakpoints: the names a plugin shares with the theme must match.
function breakpoints(path) {
	const found = new Map();
	for (const [, name, value] of read(path).matchAll(
		/^\$bp-([a-z]+):\s*(\d+px)/gm
	)) {
		found.set(name, value);
	}
	return found;
}
const themeBps = breakpoints(`${THEME}/src/scss/abstracts/_tokens.scss`);
for (const plugin of PLUGINS) {
	const path = `${plugin}/src/scss/abstracts/_tokens.scss`;
	for (const [name, value] of breakpoints(path)) {
		const expected = themeBps.get(name);
		if (expected && expected !== value) {
			problems.push(
				`${path}: $bp-${name} is ${value}, theme has ${expected}.`
			);
		}
	}
}

// 3. theme.json font sizes must mirror the $fs map.
const fsBlock = source.match(/\$fs:\s*\(([\s\S]*?)\n\);/);
const scale = new Map(
	[...fsBlock[1].matchAll(/^\t([a-z-]+):\s*([\d.]+rem)/gm)].map((m) => [
		m[1],
		m[2],
	])
);
const presets = new Map(
	JSON.parse(read(`${THEME}/theme.json`)).settings.typography.fontSizes.map(
		(preset) => [preset.slug, preset.size]
	)
);
for (const [slug, size] of scale) {
	if (!presets.has(slug)) {
		problems.push(`theme.json has no font size preset for step "${slug}".`);
	} else if (presets.get(slug) !== size) {
		problems.push(
			`theme.json preset "${slug}" is ${presets.get(slug)}, the scale says ${size}.`
		);
	}
}
for (const slug of presets.keys()) {
	if (!scale.has(slug)) {
		problems.push(
			`theme.json preset "${slug}" is not a step in $fs — the editor would ` +
				'offer a size the stylesheets never use.'
		);
	}
}

// 4. The content column: `--content-max-width` in the tokens against
//    `settings.layout.contentSize` in theme.json. Core reads the latter to build
//    its constrained-layout rules and cannot read SCSS, so this one mirror is
//    unavoidable — the other three copies of the value were removed.
const themeJson = JSON.parse(read(`${THEME}/theme.json`));
const tokens = read(`${THEME}/src/scss/abstracts/_tokens.scss`);
const tokenWidth = tokens.match(/--content-max-width:\s*([\d.]+px)/)?.[1];
const layoutWidth = themeJson.settings.layout?.contentSize;
if (!tokenWidth) {
	problems.push('_tokens.scss no longer declares --content-max-width.');
} else if (tokenWidth !== layoutWidth) {
	problems.push(
		`--content-max-width is ${tokenWidth}, theme.json contentSize is ${layoutWidth}.`
	);
}

if (problems.length === 0) {
	console.log(
		`Mirrors in sync: ${MIRRORED.length} scale files identical in ` +
			`${PLUGINS.length + 1} packages, ` +
			`${scale.size} font sizes matching theme.json, breakpoints and content width agree.`
	);
	process.exit(0);
}

console.error('Mirrored tokens have drifted:\n');
for (const problem of problems) {
	console.error(`  ${problem}`);
}
process.exit(1);
