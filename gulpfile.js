var gulp = require('gulp'),
	sass = require('gulp-ruby-sass'),
	autoprefixer = require('gulp-autoprefixer'),
	minify = require('gulp-minify-css');

gulp.task('watch', function()
{
	gulp.watch('scss/*.scss', ['styles']);
});

// Compile scss
gulp.task('styles', function()
{
	return sass('scss')
		.pipe(autoprefixer())
		.pipe(minify())
		.pipe(gulp.dest(''));
});