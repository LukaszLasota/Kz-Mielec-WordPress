
const path = require('path');
const common = require('./webpack.common.js');
const { merge } = require('webpack-merge');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CssMinimizerPlugin = require('css-minimizer-webpack-plugin');
const TerserPlugin = require('terser-webpack-plugin');

module.exports = merge(common, {
	mode: 'production',
	output: {
		path: path.resolve(__dirname, '../assets'),
		publicPath: 'auto',
		filename: 'js/[name].min.js?[chunkhash]',
		chunkFilename: 'js/[name].min.js?[chunkhash]',
		assetModuleFilename: '[name][ext][query]'
	},
	plugins: [
		new MiniCssExtractPlugin({ filename: 'css/[name].min.css' })
	],
	optimization: {
		usedExports: true,
		minimize: true,
		minimizer: [
			new CssMinimizerPlugin({
				minimizerOptions: {
					preset: [
						'default',
						{
							discardComments: { removeAll: true },
						},
					],
				},
			}),
			new TerserPlugin({ extractComments: false }),
		],
	},
});