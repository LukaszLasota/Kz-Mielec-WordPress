#!/usr/bin/env node

// Fails if a compiled stylesheet still contains a call to the type-scale helpers
// (`fs()`, `lh()`, `tracking()` from _type.scss; `space()`, `radius()`,
// `palette()` from _design.scss).
//
// Why this needs its own check: Sass does not error on an unknown function. It
// treats `fs(body)` as a plain CSS function and passes it straight through, so
// two mistakes compile cleanly and ship broken CSS —
//
//   1. calling the helpers in a file that never imported the module, and
//   2. calling them inside a custom property value, which Sass does not parse
//      at all (`--x: fs(body)` needs `--x: #{fs(body)}`).
//
// stylelint cannot catch either: it lints the SCSS source, where the call is
// legitimate. The only place the mistake is visible is the build output.

import { readdirSync, readFileSync, statSync } from 'node:fs';
import { join } from 'node:path';

const OUTPUTS = [
	'wp-content/themes/kzmielec/assets/css',
	'wp-content/plugins/custom-block-package/build',
	'wp-content/plugins/comparison-of-religions/build',
];

const CALL = /(?:^|[\s:,(])((?:fs|lh|tracking|space|radius|palette)\([a-z0-9-]+\))/g;

function cssFiles(dir) {
	let found = [];
	let entries;
	try {
		entries = readdirSync(dir);
	} catch {
		return found; // package not built yet — nothing to check
	}
	for (const entry of entries) {
		const path = join(dir, entry);
		if (statSync(path).isDirectory()) {
			found = found.concat(cssFiles(path));
		} else if (entry.endsWith('.css')) {
			found.push(path);
		}
	}
	return found;
}

const leaks = [];
for (const output of OUTPUTS) {
	for (const file of cssFiles(output)) {
		const css = readFileSync(file, 'utf8');
		for (const match of css.matchAll(CALL)) {
			leaks.push({ file, call: match[1] });
		}
	}
}

if (leaks.length === 0) {
	console.log('Design scale: no unresolved fs()/lh()/tracking()/space()/radius()/palette() in built CSS.');
	process.exit(0);
}

console.error('Unresolved design-scale calls in built CSS:\n');
for (const { file, call } of leaks) {
	console.error(`  ${file}: ${call}`);
}
console.error(
	'\nEither the file does not import the tokens module, or the call sits in a ' +
		'custom property value and needs interpolation: --x: #{fs(body)}.'
);
process.exit(1);
