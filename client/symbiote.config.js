const merge = require('webpack-merge');
let config = require('./symbiote.project.config')
let localConfig = null
try {
    localConfig = require('./symbiote.local.config')
} catch (e) {
    console.warn("Cannot find optional config override: symbiote.local.config.js")
}
// Detect when you're using incorrect keys in your local config
if (localConfig !== null) {
	for (let key in localConfig) {
		let value = config[key]
		if (typeof value === 'undefined') {
			console.error('symbiote.local.config.js: Cannot use key: "'+key+'" as it does not exist in symbiote.project.config.js.')
			process.exit(1);
		}
	}
	config = merge(config, localConfig)
}
module.exports = config
return
