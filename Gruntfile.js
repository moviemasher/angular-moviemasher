/*global module:false*/
module.exports = function(grunt) {
	'use strict';
	grunt.initConfig({
		pkg: grunt.file.readJSON('package.json'),
		concat: {
			options: {
				banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - '+
					'<%= grunt.template.today("yyyy-mm-dd") %>\n'+
					'<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
					'* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' +
					' Licensed <%= _.pluck(pkg.licenses, "type").join(", ") %> */\n',
				footer: '\n'
			},
			moviemasher: {
				src: ['src/*.js'],
				dest: 'dist/<%= pkg.name %>.js'
			}
		},
		copy: {
			moviemasher:{
				src: '<%= uglify.moviemasher.dest %>',
				dest: 'app/components/<%= pkg.name %>.min.js'
			}
		},
		jshint: {
			options: {
				"-W086": true, /* allow fall through in switches */
				curly: false,
				eqeqeq: true,
				immed: true,
				latedef: true,
				newcap: true,
				noarg: true,
				sub: true,
				undef: true,
				unused: true,
				boss: false,
				eqnull: true,
				evil: true,
				browser: true,
				devel: true,
				globalstrict: true,
			},
			gruntfile: {
				src: 'Gruntfile.js'
			},
			moviemasher: {
				src: '<%= concat.moviemasher.dest %>'
			}
		},
		uglify: {
			options: {},
			moviemasher: {
				src: '<%= concat.moviemasher.dest %>',
				dest: 'dist/<%= pkg.name %>.min.js'
			}
		}
	});
	grunt.loadNpmTasks('grunt-contrib-concat');
	grunt.loadNpmTasks('grunt-contrib-copy');
	grunt.loadNpmTasks('grunt-contrib-jshint');
	grunt.loadNpmTasks('grunt-contrib-uglify');
	grunt.registerTask('default', ['concat', 'jshint', 'uglify', 'copy']);
};
