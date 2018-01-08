var gulp = require('gulp');
var minify = require('gulp-minify');
var sass  = require('gulp-sass');
var watch = require('gulp-watch');
var imagemin = require('gulp-imagemin');

gulp.task('script', function() {
  gulp.src('assets/js/*.js')
    .pipe(minify({
        ext:{
            src:'-debug.js',
            min:'.min.js'
        },
        exclude: ['tasks'],
        ignoreFiles: ['.combo.js', '-min.js']
    }))
    .pipe(gulp.dest('dist/js'));
    gulp.src('node_modules/nestedSortable/jquery.mjs.nestedSortable.js')
        .pipe(gulp.dest('dist/js'));
});

gulp.task('style', function() {
    return gulp.src('assets/scss/**/*.scss')
    .pipe(sass().on('error', sass.logError))
    .pipe(gulp.dest('dist/css'));
});

gulp.task('images', function(cb) {
    gulp.src('assets/images/*')
        .pipe(imagemin())
        .pipe(gulp.dest('dist/images'));
});

gulp.task('watch', function() {
    gulp.watch('assets/scss/**/*.scss', ['style']);
    gulp.watch('assets/js/*.js', ['script']);
    gulp.watch('assets/images/*', ['images']);
});