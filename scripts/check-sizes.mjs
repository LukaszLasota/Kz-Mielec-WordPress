#!/usr/bin/env node

// Fails if a compiled stylesheet declares a font size that is not a step of the
// scale in `src/scss/abstracts/_type.scss`.
//
// Why this needs its own check: stylelint validates syntax, not vocabulary. A
// rule that writes `font-size: 1.3rem` is perfectly valid CSS and perfectly
// invisible — and that is how the sizes drifted onto 24 different values in the
// first place. The scale only holds if something counts.
//
// It also catches the subtler failure this project actually hit: a component
// adding its own `@media` step-down that overlapped the one `type()` emits, so a
// page title rendered 32px on a phone where the role said 24px. Both values are
// on the scale, so this check does not see that directly — but the sizes it
// prints per breakpoint make the conflict readable, and the parameter that
// allowed it no longer exists.

import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const THEME = 'wp-content/themes/kzmielec';
const OUTPUTS = [
	`${THEME}/assets/css`,
	'wp-content/plugins/custom-block-package/build',
	'wp-content/plugins/comparison-of-religions/build',
];

// The scale, read from the source of truth rather than restated here.
const typeScss = readFileSync(`${THEME}/src/scss/abstracts/_type.scss`, 'utf8');
const fsBlock = typeScss.match(/\$fs:\s*\(([\s\S]*?)\n\);/);
// The minifier drops a leading zero, so `0.75rem` reaches the build as `.75rem`;
// compare both forms.
const norm = (v) => v.trim().replace(/^\./, '0.');
const allowed = new Set(
	[...fsBlock[1].matchAll(/^\t[a-z-]+:\s*([\d.]+rem)/gm)].map((m) => norm(m[1]))
);
// Values that are not type sizes: inherited, relative to the element, or a
// deliberate one-off documented at its call site.
const IGNORED = /^(inherit|initial|unset|revert|100%|80%|75%|1em|2em|0)$/;
// Third-party stylesheets shipped inside a build: their sizes are not ours to set.
const VENDORED = /\/(leaflet|vendor|node_modules)\//;

function cssFiles(dir) {
	let found = [];
	let entries;
	try {
		entries = readdirSync(dir);
	} catch {
		return found;
	}
	for (const entry of entries) {
		const path = join(dir, entry);
		if (statSync(path).isDirectory()) {
			found = found.concat(cssFiles(path));
		} else if (entry.endsWith('.css') && !VENDORED.test(path)) {
			found.push(path);
		}
	}
	return found;
}

const offenders = [];
let checked = 0;
for (const output of OUTPUTS) {
	for (const file of cssFiles(output)) {
		const css = readFileSync(file, 'utf8');
		for (const match of css.matchAll(/font-size:\s*([^;}]+)/g)) {
			const raw = match[1].trim();
			// `var(--fs-x, 1.25rem)` carries its step in the fallback
			const value = raw.startsWith('var(')
				? (raw.match(/,\s*([^)]+)\)/)?.[1] ?? '').trim()
				: raw;
			checked++;
			if (!value || IGNORED.test(value) || allowed.has(norm(value))) continue;
			if (value.startsWith('clamp(') || value.startsWith('var(')) continue;
			offenders.push({ file, value });
		}
	}
}

if (offenders.length === 0) {
	console.log(
		`Sizes: ${checked} font-size declarations, every one a step of the ` +
			`${allowed.size}-step scale.`
	);
	process.exit(0);
}

console.error('Font sizes that are not on the scale:\n');
for (const { file, value } of offenders) {
	console.error(`  ${file}: ${value}`);
}
console.error(
	'\nUse a role (`@include type(h2)`) or a step (`fs(body-sm)`). If the value is ' +
		'genuinely one-off, add it to IGNORED in this script with a reason.'
);
process.exit(1);
