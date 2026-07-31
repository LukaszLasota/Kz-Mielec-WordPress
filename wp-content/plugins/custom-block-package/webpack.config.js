const defaultConfig = require('@wordpress/scripts/config/webpack.config');
const path = require('path');
const CopyWebpackPlugin = require('copy-webpack-plugin');

/**
 * Add `silenceDeprecations` to every sass-loader entry in a rules array.
 *
 * Returns new objects instead of mutating `defaultConfig` in place: that object
 * comes from `require`, so Node caches it and a mutation would leak into every
 * other consumer in the same process. Anything that is not a sass-loader use
 * entry is passed through untouched, so a change in the wp-scripts rule shape
 * degrades to a no-op rather than breaking the build.
 *
 * `api: 'modern'` is carried over from the previous configuration on purpose —
 * sass-loader 12 still defaults to the legacy Dart Sass JS API, and flipping
 * that here would change importer resolution semantics for no gain.
 *
 * @param {Array} rules webpack module rules
 * @return {Array} rules with sass deprecation warnings silenced
 */
function silenceSassDeprecations(rules) {
  return rules.map((rule) => {
    if (!rule || !Array.isArray(rule.use)) {
      return rule;
    }

    return {
      ...rule,
      use: rule.use.map((entry) => {
        const normalised = typeof entry === 'string' ? { loader: entry } : entry;

        if (!normalised.loader || !normalised.loader.includes('sass-loader')) {
          return entry;
        }

        const options = normalised.options || {};

        return {
          ...normalised,
          options: {
            ...options,
            api: 'modern',
            sassOptions: {
              ...(options.sassOptions || {}),
              silenceDeprecations: ['legacy-js-api'],
            },
          },
        };
      }),
    };
  });
}

module.exports = {
  ...defaultConfig,

  entry: {
    ...defaultConfig.entry(),

    // Glide carousel, bundled with the plugin rather than loaded from a CDN so
    // the block works without third-party requests.
    'glide-package/index': path.resolve(process.cwd(), 'src/js/glide-init.js'),
    'glide-package/glide.core': path.resolve(
      process.cwd(),
      'node_modules/@glidejs/glide/dist/css/glide.core.min.css'
    ),
    'glide-package/glide.min': path.resolve(
      process.cwd(),
      'node_modules/@glidejs/glide/dist/glide.min.js'
    ),
  },

  module: {
    ...defaultConfig.module,
    rules: silenceSassDeprecations(defaultConfig.module.rules),
  },

  plugins: [
    // RtlCssPlugin emits a '[name]-rtl.css' next to every stylesheet. The site
    // is Polish/LTR only, so those are dead files. The `plugin.constructor`
    // guard matters: a plugin supplied as a plain object has no constructor and
    // reading `.name` off it would throw.
    ...defaultConfig.plugins.filter(
      (plugin) =>
        !(plugin.constructor && plugin.constructor.name === 'RtlCssPlugin')
    ),

    new CopyWebpackPlugin({
      patterns: [
        {
          from: path.resolve(__dirname, 'src/blocks/map-block/images'),
          to: path.resolve(__dirname, 'build/blocks/map-block/images'),
        },
        {
          from: path.resolve(__dirname, 'src/blocks/scroll-arrow/images'),
          to: path.resolve(__dirname, 'build/blocks/scroll-arrow/images'),
        },
        {
          from: path.resolve(__dirname, 'node_modules/leaflet/dist/leaflet.css'),
          to: path.resolve(__dirname, 'build/leaflet/leaflet.css'),
        },
      ],
    }),
  ],
};
