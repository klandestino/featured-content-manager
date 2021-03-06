let gulp = require('gulp');
let uglify = require('gulp-uglify');
let rename = require("gulp-rename");
let watch = require('gulp-watch');
let imagemin = require('gulp-imagemin');
let babel = require('gulp-babel');

gulp.task('script', function() {
  gulp.src('assets/js/*.js')
    .pipe(babel({
        presets: ['env']
    }))
    .pipe(gulp.dest('dist/js'))
    .pipe(uglify())
    .pipe(rename({ suffix: '.min' }))
    .pipe(gulp.dest('dist/js'));
    gulp.src('node_modules/sortablejs/Sortable.min.js')
        .pipe(gulp.dest('dist/js'));
    return gulp.src('node_modules/whatwg-fetch/fetch.js')
        .pipe(gulp.dest('dist/js'));
});

gulp.task('images', function() {
    return gulp.src('assets/images/*')
        .pipe(imagemin())
        .pipe(gulp.dest('dist/images'));
});

gulp.task('watch', function() {
    gulp.watch('assets/js/*.js', gulp.series('script'));
    gulp.watch('assets/images/*', gulp.series('images'));
});

gulp.task('default', gulp.series(['script','images']));