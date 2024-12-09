var gulp = require('gulp');
var gulpless = require('gulp-less');

var paths = {
  srcfile: './bcbsma_plans/styles/*/*.less',
  dest: './bcbsma_plans/styles',
}

gulp.task('styles', function () {
  return gulp
    .src(paths.srcfile)
    .pipe(gulpless())
    .pipe(gulp.dest(paths.dest));
});

gulp.task('watch', function () {
  gulp.watch(paths.srcfile, gulp.series('styles'));
});
gulp.task('default', gulp.series('styles'));
