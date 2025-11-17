const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const { WebpackManifestPlugin } = require('webpack-manifest-plugin');
const RemoveEmptyScriptsPlugin = require('webpack-remove-empty-scripts');

const mode = process.env.NODE_ENV === 'production' ? 'production' : 'development';

module.exports = {
  mode,
  entry: {
    main: path.resolve(__dirname, '../assets/scss/main.scss'),
  },
  output: {
    filename: 'js/[name].[contenthash].js',
    path: path.resolve(__dirname, '../dist'),
    clean: true,
  },
  module: {
    rules: [
      {
        test: /\.s?css$/i,
        use: [
          MiniCssExtractPlugin.loader,
          {
            loader: 'css-loader',
            options: {
              importLoaders: 1,
              url: false,
              sourceMap: mode !== 'production',
            },
          },
          {
            loader: 'postcss-loader',
            options: {
              sourceMap: mode !== 'production',
              postcssOptions: {
                config: path.resolve(__dirname, 'postcss.config.cjs'),
              },
            },
          },
          {
            loader: 'sass-loader',
            options: {
              sourceMap: mode !== 'production',
            },
          },
        ],
      },
    ],
  },
  plugins: [
    new RemoveEmptyScriptsPlugin(),
    new MiniCssExtractPlugin({
      filename: '[name].[contenthash].css',
    }),
    new WebpackManifestPlugin({
      fileName: 'manifest.json',
      publicPath: '',
      filter: (file) => file.isInitial && file.name.endsWith('.css'),
    }),
  ],
  devtool: mode === 'development' ? 'source-map' : false,
  stats: 'minimal',
  watchOptions: {
    ignored: /node_modules/,
  },
};
