const merge = require('webpack-merge')
const common = require('./webpack.common')
const path = require('path')
const LiveReloadPlugin = require('webpack-livereload-plugin')

const projectConfig = require('./symbiote.config')

module.exports =  merge(common, {
  mode: 'development',
  watch: true,
  devtool: 'inline-source-map',
  watchOptions: {
    ignored: [
        /www/,
    ]
  },
  plugins: [
      new LiveReloadPlugin({
         host: projectConfig.hostname,
         port: 35729,
      })
  ]
})
