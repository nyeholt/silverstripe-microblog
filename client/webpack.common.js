const path = require('path')
const webpack = require('webpack')

const CopyWebpackPlugin = require('copy-webpack-plugin')
const ExtractTextPlugin = require("extract-text-webpack-plugin")
const CleanWebpackPlugin = require('clean-webpack-plugin')

const projectConfig = require('./symbiote.config')
const HtmlWebpackPlugin = require('html-webpack-plugin');

const htmlLoader = require('html-loader');

module.exports = {
    entry: {
        main: [
            // NOTE(Jason): 2018-08-09
            // fetch polyfill is not in babel-polyfill
            // https://github.com/github/fetch#installation
            //
            // relative questions: 
            // https://stackoverflow.com/questions/38439590/what-features-does-the-babel-polyfill-support
            'whatwg-fetch',
            `${projectConfig.paths.src}/index.tsx`,
            `${projectConfig.paths.style}/main.scss`,
        ],
        // 3rd party libraries
        // vendor: [
        // ],
    },
    optimization: {
        splitChunks: {
            chunks: 'all',
            cacheGroups: {
                vendor: {
                    test: /[\\/]node_modules[\\/](react|react-dom)[\\/]/,
                    name: 'vendor',
                    chunks: 'all',
                }
            }
        }
        /* Default options 
        splitChunks: {
            chunks: 'async',
            minSize: 30000,
            maxSize: 0,
            minChunks: 1,
            maxAsyncRequests: 5,
            maxInitialRequests: 3,
            automaticNameDelimiter: '~',
            name: true,
            cacheGroups: {
                vendors: {
                    test: /[\\/]node_modules[\\/]/,
                    priority: -10
                },
                default: {
                    minChunks: 2,
                    priority: -20,
                    reuseExistingChunk: true
                }
            }
        } 
        */
    },
    output: {
        filename: '[name].bundle.js',
        chunkFilename: '[name].chunk.js',
        path: projectConfig.paths.output
    },
    externals: {

    },

    resolve: {
        alias: {
            'app': path.resolve(projectConfig.paths.app),
            'resources': path.resolve('../../resources'),
            'assets': path.resolve(projectConfig.paths.assets),
            //'style': path.resolve(projectConfig.paths.style),
            'src': path.resolve(projectConfig.paths.src),
        },
        extensions: ['.ts', '.tsx', '.js', '.json']
    },
    module: {
        rules: [
            {
                test: /\.(html)$/,
                use: {
                    loader: 'html-loader',
                    options: {
                        interpolate: true
                    }
                }
            },
            {
                test: /\.(png|svg|jpg|gif)$/,
                include: path.resolve(projectConfig.paths.assets),
                use: [{
                    loader: 'url-loader',
                    options: {
                        limit: 8000, // Convert images < 8kb to base64 strings
                        name: 'images/[name]-[hash].[ext]',
                    }
                }]
            },
            // Typescript
            {
                test: /\.tsx?$/,
                // disabled due to https://github.com/s-panferov/awesome-typescript-loader/issues/541
                // loader: 'awesome-typescript-loader'
                use: [
                    {
                        loader: 'ts-loader',
                    }
                ]
            },
            {
                test: /vendor(.+?)\.js/,
                exclude: /(node_modules|bower_compontents)/,
                use: [
                    'source-map-loader',
                    {
                        loader: 'babel-loader'
                    }
                ]
            },
            {
                test: /\.js$/,
                exclude: /(node_modules|bower_compontents)/,
                use: [
                    // All output '.js' files will have any sourcemaps re-processed by 'source-map-loader'.
                    'source-map-loader',
                    {
                        loader: 'babel-loader'
                    }
                ],
                enforce: 'pre'
            },
            // Fonts(including font-awesome)
            {
                test: /.(ttf|otf|eot|svg|woff(2)?)(\?v=\d+\.\d+\.\d+)?$/,
                exclude: path.resolve(projectConfig.paths.assets, 'images'),
                use: [
                    {
                        loader: 'file-loader',
                        options: {
                            name: '[name]-[hash].[ext]',
                            // NOTE(Jake): 2018-04-04, Need ".." to move up 1 folder level from 'public/build'
                            outputPath: 'assets/fonts/',
                        }
                    }
                ]
            },
            // CSS
            {
                test: /\.css$/,
                use: [
                    'style-loader',
                    'css-loader'
                ]
            },
            // SASS
            {
                test: /\.scss$/,
                use: ExtractTextPlugin.extract({
                    fallback: 'style-loader',
                    use: [
                        {
                            loader: 'css-loader',
                            options: {
                                importLoaders: 1,
                                alias: {
                                    "../fonts/bootstrap": "bootstrap-sass/assets/fonts/bootstrap",
                                },
                                // NOTE(Jake): 2018-03-22
                                //
                                // On Windows:
                                // minimize: false == 750ms
                                // minimize: true  == 3300ms
                                //
                                minimize: false
                            }
                        },
                        'postcss-loader',
                        'sass-loader'
                    ]
                })
            }
        ]
    },
    plugins: [
        new CleanWebpackPlugin(projectConfig.paths.public + '/*'),
        new CopyWebpackPlugin(
            [
                // NOTE(Marcus): 2018-07-21
                //
                // This has been re-enabled because .ss referenced items need
                // to be brought across as-is; it is still served by dev-server though!
                {
                    from: 'app/assets',
                    to: `${projectConfig.paths.public}`,
                }
            ],
            {
                // ignore pathe is relative to above 'from' path
                ignore: [
                    'images/icons/*',
                    'fonts/*',
                ],
                // By default, we only copy modified files during
                // a watch or webpack-dev-server build. Setting this
                // to `true` copies all files.
                copyUnmodified: true
            }
        )
        ,
        // CSS goes out into the same location as JS to support webpack-dev-server
        new ExtractTextPlugin({
            filename: '[name].css'
        }),
        // this is .tmpl.html because it can be directly interpolated by
        // html-loader without issue
        new HtmlWebpackPlugin({
            title: 'Webpack example project',
            template: 'app/assets/index.tmpl.html',
            filename: 'index.html',
        }),
        // this one we don't want interpolated by html-loader first
        new HtmlWebpackPlugin({
            title: 'Page example',
            template: 'app/assets/page.html.tmpl',
            filename: 'page.html',
        })
    ]
}
