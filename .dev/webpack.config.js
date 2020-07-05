const path = require( 'path' );
const defaultConfig = require( '@wordpress/scripts/config/webpack.config' );

module.exports = {
	...defaultConfig,

	entry: {
		'sidebar': path.resolve( process.cwd(), 'src', 'sidebar.js' ),
		'block': path.resolve( process.cwd(), 'src', 'block.js' ),
	},
};
