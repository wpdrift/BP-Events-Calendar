/* jshint node:true */
module.exports = function( grunt ) {
	'use strict';

	grunt.initConfig({

		// Generate POT files.
		makepot: {
			bpec: {
				options: {
					domainPath: '/languages',
					exclude: [
						'node_modules'
					],
					mainFile:    'bp-events-calendar.php',
					potFilename: 'bp-events-calendar.pot'
				}
			}
		}

	});

	// Load NPM tasks to be used here
	grunt.loadNpmTasks( 'grunt-wp-i18n' );

	// Just an alias for pot file generation
	grunt.registerTask( 'pot', [
		'makepot'
	]);

};
