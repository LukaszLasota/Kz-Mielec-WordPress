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

/**
 * Add `silenceDeprecations` to every sass-loader entry in a rules array.
 *
 * Walks the rules defensively: anything that isn't a sass-loader use entry is
 * returned untouched, so an upstream change in wp-scripts' rule shape degrades
 * to a no-op instead of breaking the build.
 *
 * @param {Array} rules webpack module rules
 * @return {Array} rules with sass deprecations silenced
 */
function silenceSassDeprecations( rules ) {
	const silence = [ 'legacy-js-api', 'import' ];

	return rules.map( ( rule ) => {
		if ( ! rule || ! Array.isArray( rule.use ) ) {
			return rule;
		}

		return {
			...rule,
			use: rule.use.map( ( entry ) => {
				const normalised =
					typeof entry === 'string' ? { loader: entry } : entry;

				if (
					! normalised.loader ||
					! normalised.loader.includes( 'sass-loader' )
				) {
					return entry;
				}

				const options = normalised.options || {};

				return {
					...normalised,
					options: {
						...options,
						sassOptions: {
							...( options.sassOptions || {} ),
							silenceDeprecations: silence,
						},
					},
				};
			} ),
		};
	} );
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
	plugins: defaultConfig.plugins
		// The wp-scripts default config includes two plugins that don't fit a
		// classic theme build and would corrupt output if left in place:
		//
		// - CleanWebpackPlugin: cleanOnceBeforeBuildPatterns defaults to
		//   ['**/*'], which would wipe the entire assets/ output directory
		//   (including static, non-build files such as assets/js/logo.js,
		//   assets/js/admin/belief-settings.js, and legacy content images
		//   under assets/media/) before the first build. Drop it entirely to
		//   match the old build's behavior, which never cleaned the output
		//   directory.
		// - RtlCssPlugin: emits '[name]-rtl.css' resolved directly against
		//   output.path, ignoring our MiniCssExtractPlugin 'css/[name].css'
		//   filename override, so RTL variants land outside assets/css/ (e.g.
		//   assets/frontend-rtl.css, assets/patterns/style-<slug>-style-rtl.css).
		//   The site is Polish/LTR only and the old build never generated RTL
		//   CSS, so these would just be misplaced orphan files. Drop it.
		.filter(
			(plugin) =>
				!(
					plugin.constructor &&
					(plugin.constructor.name === 'CleanWebpackPlugin' ||
						plugin.constructor.name === 'RtlCssPlugin')
				)
		)
		.map((plugin) =>
			plugin.constructor && plugin.constructor.name === 'MiniCssExtractPlugin'
				? new MiniCssExtractPlugin({ filename: 'css/[name].css' })
				: plugin
		),
	module: {
		...defaultConfig.module,
		rules: [
			// Silence Dart Sass deprecation noise coming from wp-scripts' own
			// toolchain: sass-loader 12 (the version @wordpress/scripts 27 pins)
			// calls Dart Sass through its legacy JS API, which warns on every
			// entry. Nothing in this theme can fix that — it goes away when
			// wp-scripts ships a newer sass-loader — so the warning is silenced
			// rather than printed 9 times per build. If the loader shape ever
			// changes, this map simply becomes a no-op.
			...silenceSassDeprecations( defaultConfig.module.rules ),
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
	optimization: {
		...defaultConfig.optimization,
		splitChunks: {
			...defaultConfig.optimization.splitChunks,
			// The default 'style' cacheGroup matches any source module whose
			// path ends in 'style(.module).scss' (the WP block-style-index.css
			// convention) and re-homes its CSS into a 'style-<name>' chunk via
			// MiniCssExtractPlugin. Our pattern entries are literally named
			// src/patterns/<slug>/style.scss, so this hijacked their CSS into
			// assets/css/patterns/style-<slug>-style.css and left the
			// canonical assets/css/patterns/<slug>-style.css stale/empty.
			// Disable that cache group so each entry's CSS stays in its own
			// single [name].css output.
			cacheGroups: { default: false },
		},
	},
	// Poll for file changes in watch mode. Native inotify events do not cross
	// the WSL2/Docker host bind-mount, so `npm run start` would compile once
	// but never rebuild on host-side edits. Polling makes `ddev theme:watch`
	// actually pick up saves. Affects watch only — not the one-shot build.
	watchOptions: {
		...( defaultConfig.watchOptions || {} ),
		poll: 1000,
		aggregateTimeout: 300,
		ignored: /node_modules/,
	},
};
