const path = require("path");
const merge = require('webpack-merge')
const common = require("../webpack.common");

module.exports = (baseConfig, env, config) => {
    delete common.optimization;
    return merge(common, config);
};
