const path = require('path');
const common = require('./webpack.common.js');
const { merge } = require('webpack-merge');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = merge(common, {
	mode: 'development',
	output: {
		path: path.resolve(__dirname, '../assets'),
		publicPath: 'auto',
		filename: 'js/[name].js?[chunkhash]',
		chunkFilename: 'js/[name].js?[chunkhash]',
		assetModuleFilename: '[name][ext][query]'
	},
	plugins: [
		new MiniCssExtractPlugin({ filename: 'css/[name].css' })
	],
	devtool: 'source-map'
});
