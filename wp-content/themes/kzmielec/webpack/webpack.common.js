const webpack = require('webpack');
const path = require('path');
const fs = require('fs');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

/**
 * Auto-discover pattern entries from webpack/src/patterns/ directory.
 * Each pattern folder can contain:
 * - style.scss → compiles to assets/patterns/[pattern-name]-style.css
 * - script.ts → compiles to assets/patterns/[pattern-name]-script.js
 *
 * @returns {Object} Pattern entry points
 */
function getPatternEntries() {
	const patternDir = path.resolve(__dirname, 'src/patterns');
	const entries = {};

	if (!fs.existsSync(patternDir)) {
		return entries;
	}

	fs.readdirSync(patternDir).forEach(folder => {
		const folderPath = path.join(patternDir, folder);

		if (!fs.statSync(folderPath).isDirectory()) {
			return;
		}

		const scssFile = path.join(folderPath, 'style.scss');
		const tsFile = path.join(folderPath, 'script.ts');

		if (fs.existsSync(scssFile)) {
			entries[`patterns/${folder}-style`] = scssFile;
		}

		if (fs.existsSync(tsFile)) {
			entries[`patterns/${folder}-script`] = tsFile;
		}
	});

	return entries;
}

module.exports = {
	entry: {
		'frontend': path.resolve(__dirname, './src/frontend.ts'),
		'backend': path.resolve(__dirname, './src/backend.ts'),
		'editor': path.resolve(__dirname, './src/editor.ts'),
		'print': path.resolve(__dirname, './src/print.ts'),
		...getPatternEntries(),
	},
	plugins: [],
	resolve: {
		extensions: ['.ts', '.js']
	},
	module: {
		rules: [
			{
				test: /\.ts$/,
				use: 'ts-loader',
				exclude: /node_modules/
			},
			{
				test: /\.js$/,
				loader: 'babel-loader',
				exclude: /node_modules/
			},
			{
				test: /\.(scss|css)$/,
				use: [MiniCssExtractPlugin.loader, 'css-loader', 'sass-loader']
			},
			{
				test: /\.(jpe?g|png|gif|svg)$/,
				type: "asset/resource",
				generator: {
					filename: 'media/[name][ext]',
				},
			},
			{
				test: /\.(woff|woff2|eot|ttf)$/,
				type: "asset/resource",
				generator: {
					filename: 'webfont/[name][ext]',
				},
			}
		]
	},
};
