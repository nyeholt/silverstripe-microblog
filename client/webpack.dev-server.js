const merge = require('webpack-merge')
const common = require('./webpack.common')
const path = require('path')
//const LiveReloadPlugin = require('webpack-livereload-plugin')
const CleanWebpackPlugin = require('clean-webpack-plugin')

const projectConfig = require('./symbiote.config')

module.exports =  merge(common, {
  mode: 'development',
  watch: true,
  devtool: 'inline-source-map',
  plugins: [
  ],
  devServer: {
    contentBase: projectConfig.paths.public,
    host: projectConfig.hostname,
    port: projectConfig.port,
    publicPath: '/',
    allowedHosts: [
        '.symlocal'
    ],
  }
})
