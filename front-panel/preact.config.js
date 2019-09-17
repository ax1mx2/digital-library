import svgLoader from 'preact-cli-svg-loader';

export default (config, env, helpers) => {
  delete config.entry.polyfills;
  config.output.filename = '[name].js';

  const {plugin} = helpers.getPluginsByName(config, 'ExtractTextPlugin')[0];
  plugin.options.disable = true;

  svgLoader(config, helpers);

  if (env.production) {
    config.output.libraryTarget = 'umd';
  }
};
