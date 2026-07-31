/**
 * Read a numeric value from a CSS custom property on :root.
 *
 * Lets scripts share the design tokens defined in
 * `src/scss/abstracts/_tokens.scss` instead of keeping their own copies of the
 * same numbers (breakpoints, masonry columns, gaps). Values may carry a unit —
 * `800px` reads back as `800`.
 *
 * @param name     Custom property name, including the leading `--`.
 * @param fallback Used when the property is missing or not numeric, so scripts
 *                 still work if the stylesheet has not loaded.
 * @return The parsed number, or `fallback`.
 */
export function cssVarPx( name: string, fallback: number ): number {
	const raw = getComputedStyle( document.documentElement ).getPropertyValue(
		name
	);
	const parsed = parseFloat( raw );

	return Number.isNaN( parsed ) ? fallback : parsed;
}
