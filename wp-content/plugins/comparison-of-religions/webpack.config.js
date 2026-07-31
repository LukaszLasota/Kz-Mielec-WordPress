const defaultConfig = require('@wordpress/scripts/config/webpack.config');

/**
 * Add `silenceDeprecations` to every sass-loader entry in a rules array.
 *
 * Returns new objects rather than mutating `defaultConfig`, which is
 * module-cached by `require` and shared with every other consumer in the
 * process. Entries that are not sass-loader are passed through untouched, so a
 * change in the wp-scripts rule shape degrades to a no-op.
 *
 * The warning being silenced comes from wp-scripts' own toolchain: the pinned
 * sass-loader 12 still calls Dart Sass through its legacy JS API and warns once
 * per entry. Nothing in this plugin can change that.
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

  module: {
    ...defaultConfig.module,
    rules: silenceSassDeprecations(defaultConfig.module.rules),
  },

  plugins: [
    // RtlCssPlugin emits a '[name]-rtl.css' beside every stylesheet. The site is
    // Polish (lang="pl-PL", LTR) and nothing enqueues those files, so they were
    // dead build output. The theme and custom-block-package already drop this
    // plugin; this brings the third package in line.
    //
    // The `plugin.constructor` guard matters: a plugin supplied as a plain
    // object has no constructor and reading `.name` off it would throw.
    ...defaultConfig.plugins.filter(
      (plugin) =>
        !(plugin.constructor && plugin.constructor.name === 'RtlCssPlugin')
    ),
  ],
};
