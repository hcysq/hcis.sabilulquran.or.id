const isProduction = process.env.NODE_ENV === 'production';

module.exports = {
  plugins: [
    require('autoprefixer')(),
    isProduction
      ? require('cssnano')({
          preset: 'default',
        })
      : false,
  ].filter(Boolean),
};
