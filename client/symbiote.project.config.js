const path = require('path')

module.exports = {
    baseUrl: 'http://js.symlocal',
    hostname: '0.0.0.0',
    port: 4200,
    browsersSupported: [
        "last 2 versions",
        "safari >= 8",
        "ie >= 10",
    ],
    paths: {
        output: path.resolve(__dirname, 'www'),
        app: path.resolve(__dirname, 'app'),
        src: path.resolve(__dirname, 'app/src'),
        style: path.resolve(__dirname, 'app/style'),
        assets: path.resolve(__dirname, 'app/assets'),
        public: path.resolve(__dirname, 'www'),
        // cordova: __dirname + '/cordova/www',
        projectResources: path.resolve('../..', 'resources'),
    }
};
