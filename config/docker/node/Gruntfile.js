/*global module:false*/
'use strict';
module.exports = function(grunt) {
  grunt.initConfig({
    pkg: grunt.file.readJSON('package.json'),
    concat: {
      moviemasher: {
        options: {
          banner: '/*! <%= pkg.name %> - v<%= pkg.version %> - '+
            '<%= grunt.template.today("yyyy-mm-dd") %>\n'+
            '<%= pkg.homepage ? "* " + pkg.homepage + "\\n" : "" %>' +
            '* Copyright (c) <%= grunt.template.today("yyyy") %> <%= pkg.author.name %>;' +
            ' Licensed <%= _.map(pkg.licenses, "type").join(", ") %> */\n',
          footer: '\n'
        },
        src: ['src/*.js'],
        dest: 'dist/<%= pkg.name %>.js'
      }
    },
    jshint: {
      options: {
        "-W086": true, /* allow fall through in switch statements */
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
        src: 'src/**/*.js'
      },
      modules: {
        src: 'app/module/**/*.json'
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
  grunt.loadNpmTasks('grunt-contrib-jshint');
  grunt.loadNpmTasks('grunt-contrib-uglify');
  grunt.registerTask('default', ['jshint', 'concat', 'uglify']);
};
