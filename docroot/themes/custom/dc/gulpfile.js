// Defining requirements
var gulp = require('gulp');
var plumber = require('gulp-plumber');
var sass = require('gulp-sass');
var watch = require('gulp-watch');
var cssnano = require('gulp-cssnano');
var rename = require('gulp-rename');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify');
var merge2 = require('merge2');
var imagemin = require('gulp-imagemin');
var ignore = require('gulp-ignore');
var rimraf = require('gulp-rimraf');
var clone = require('gulp-clone');
var merge = require('gulp-merge');
var sourcemaps = require('gulp-sourcemaps');
var browserSync = require('browser-sync').create();
var del = require('del');
var cleanCSS = require('gulp-clean-css');
var gulpSequence = require('gulp-sequence');
var replace = require('gulp-replace');
var autoprefixer = require('gulp-autoprefixer');

var cfg = require('./gulpconfig.json');
var paths = cfg.paths;

gulp.task('imagemin-watch', ['imagemin'], function () {
	browserSync.reload();
});
gulp.task('imagemin', function () {
	gulp.src(paths.imgsrc + '/**')
		.pipe(imagemin())
		.pipe(gulp.dest(paths.img));
});

gulp.task('cssnano', function () {
	return gulp.src(paths.css + '/theme.css')
		.pipe(sourcemaps.init({
			loadMaps: true
		}))
		.pipe(plumber({
			errorHandler: function (err) {
				console.log(err);
				this.emit('end');
			}
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(cssnano({
			discardComments: {
				removeAll: true
			}
		}))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(paths.css));
});
gulp.task('cleancss', function () {
	return gulp.src(paths.css + '/*.min.css', {
			read: false
		})
		.pipe(ignore('theme.css'))
		.pipe(rimraf());
});
gulp.task('sass', function () {
	var stream = gulp.src(paths.sass + '/*.scss')
		.pipe(plumber({
			errorHandler: function (err) {
				console.log(err);
				this.emit('end');
			}
		}))
		.pipe(sourcemaps.init({
			loadMaps: true
		}))
		.pipe(sass({
			errLogToConsole: true
		}))
		.pipe(autoprefixer('last 2 versions'))
		.pipe(sourcemaps.write(undefined, {
			sourceRoot: null
		}))
		.pipe(gulp.dest(paths.css))
	return stream;
});
gulp.task('minifycss', function () {
	return gulp.src(paths.css + '/theme.css')
		.pipe(sourcemaps.init({
			loadMaps: true
		}))
		.pipe(cleanCSS({
			compatibility: '*'
		}))
		.pipe(plumber({
			errorHandler: function (err) {
				console.log(err);
				this.emit('end');
			}
		}))
		.pipe(rename({
			suffix: '.min'
		}))
		.pipe(sourcemaps.write('./'))
		.pipe(gulp.dest(paths.css));
});
gulp.task('styles', function (callback) {
	gulpSequence('sass', 'minifycss')(callback);
});

gulp.task('scripts', function () {
	var scripts = [
    // paths.dev + '/js/bootstrap4/bootstrap.js',
    paths.dev + '/js/swiper/swiper.js',
		paths.dev + '/js/theme.js'
	];
	gulp.src(scripts)
		.pipe(concat('theme.min.js'))
		.pipe(uglify())
		.pipe(gulp.dest(paths.js));

	gulp.src(scripts)
		.pipe(concat('theme.js'))
		.pipe(gulp.dest(paths.js));
});

gulp.task('watch', function () {
	gulp.watch(paths.sass + '/**/*.scss', ['styles']);
  gulp.watch([paths.dev + '/js/**/*.js', 'js/**/*.js', '!js/theme.js', '!js/theme.min.js'], ['scripts']);

	gulp.watch(paths.imgsrc + '/**', ['imagemin-watch']);
});
gulp.task('watch-scss', ['browser-sync'], function () {
	gulp.watch(paths.sass + '/**/*.scss', ['scss-for-dev']);
});
gulp.task('browser-sync', function () {
	browserSync.init(cfg.browserSyncWatchFiles, cfg.browserSyncOptions);
});
gulp.task('watch-bs', ['browser-sync', 'watch', 'scripts'], function () {});

gulp.task('clean-source', function () {
	return del(['src/**/*']);
});
gulp.task('clean-vendor-assets', function () {
	return del([paths.dev + '/js/swiper/swiper.js', paths.dev + '/js/bootstrap4/**', paths.js + '/**/popper.min.js', paths.js + '/**/popper.js', (paths.vendor !== '' ? (paths.js + paths.vendor + '/**') : '')]);
});
gulp.task('clean-dist', function () {
	return del([paths.dist + '/**']);
});
gulp.task('clean-dist-product', function () {
	return del([paths.distprod + '/**']);
});

gulp.task('copy-assets', function () {
	var stream = gulp.src(paths.node + 'bootstrap/dist/js/**/*.js')
		.pipe(gulp.dest(paths.dev + '/js/bootstrap4'));
	gulp.src(paths.node + 'bootstrap/scss/**/*.scss')
    .pipe(gulp.dest(paths.dev + '/sass/bootstrap4'));

	gulp.src(paths.node + 'popper.js/dist/umd/popper.min.js')
		.pipe(gulp.dest(paths.js + paths.vendor));
	gulp.src(paths.node + 'popper.js/dist/umd/popper.js')
    .pipe(gulp.dest(paths.js + paths.vendor));

	return stream;
});
gulp.task('dist', ['clean-dist'], function () {
	return gulp.src(['**/*', '!' + paths.bower, '!' + paths.bower + '/**', '!' + paths.node, '!' + paths.node + '/**', '!' + paths.dev, '!' + paths.dev + '/**', '!' + paths.dist, '!' + paths.dist + '/**', '!' + paths.distprod, '!' + paths.distprod + '/**', '!' + paths.sass, '!' + paths.sass + '/**', '!readme.txt', '!readme.md', '!package.json', '!package-lock.json', '!gulpfile.js', '!gulpconfig.json', '!CHANGELOG.md', '!.travis.yml', '!jshintignore', '!codesniffer.ruleset.xml', '*'], {
			'buffer': false
		})
		.pipe(replace('/js/jquery.slim.min.js', '/js' + paths.vendor + '/jquery.slim.min.js', {
			'skipBinary': true
		}))
		.pipe(replace('/js/popper.min.js', '/js' + paths.vendor + '/popper.min.js', {
			'skipBinary': true
		}))
		.pipe(gulp.dest(paths.dist));
});
gulp.task('dist-product', ['clean-dist-product'], function () {
	return gulp.src(['**/*', '!' + paths.bower, '!' + paths.bower + '/**', '!' + paths.node, '!' + paths.node + '/**', '!' + paths.dist, '!' + paths.dist + '/**', '!' + paths.distprod, '!' + paths.distprod + '/**', '*'])
		.pipe(gulp.dest(paths.distprod));
});
