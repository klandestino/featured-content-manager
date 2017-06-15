var gulp = require('gulp');
var minify = require('gulp-minify');

gulp.task('script', function() {
  gulp.src('assets/js/*.js')
    .pipe(minify({
        ext:{
            src:'-debug.js',
            min:'.js'
        },
        exclude: ['tasks'],
        ignoreFiles: ['.combo.js', '-min.js']
    }))
    .pipe(gulp.dest('dist/js'));
    gulp.src('node_modules/nestedSortable/jquery.mjs.nestedSortable.js')
        .pipe(gulp.dest('dist/js'));
});