const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const rename = require('gulp-rename');
const concat = require('gulp-concat');
const replace = require('gulp-replace');
const fs = require('fs');

// read a file line-by-line
const lineReader = require('line-reader');

gulp.task('default', (done) => {
    new Promise((resolve) => {
        const basePath = './../../../../platform/src/Administration/Resources/app/administration/src';

        gulp.src([
            basePath + '/app/assets/scss/variables.scss',
            './variables.scss',
            basePath + '/app/assets/scss/global.scss',
            basePath + '/app/assets/scss/mixins.scss',
            basePath + '/app/assets/scss/typography.scss',
            basePath + '/app/assets/scss/directives/**.scss',
            basePath + '/app/component/**/**.scss',
            basePath + '/module/**/**.scss',
        ])
        .pipe(concat('app.scss'))
        .pipe(replace('$sw-condition-and-container-background-odd: #f0f2f5', '$sw-condition-and-container-background-odd: $color-gray-100'))
        .pipe(replace('$sw-condition-or-container-background-odd: #f0f2f5', '$sw-condition-or-container-background-odd: $color-gray-100'))
        .pipe(replace('background-color: #f9fafb', 'background-color: $color-gray-50'))
        .pipe(replace('background-color: white', 'background-color: $color-white'))
        .pipe(replace('$sw-loader-color-overlay: rgba(255, 255, 255, 0.8)', '$sw-loader-color-overlay: rgba($color-white, 0.8)'))
        .pipe(gulp.dest('.'))
        .on('end', resolve);
    }).then(() => {
        let s = '';

        const excludeRegEx = new RegExp('^@import');
        const propertyRegEx = new RegExp('(.*?):(.*?);');
        const variableRegEx = new RegExp('^\\$|\\$color-(.*?)|\\$(.*?)-(color|background|dark|light)');

        const readStream = fs.createReadStream('app.scss');
        lineReader.eachLine(readStream, (line, last) => {
            if (line.match(propertyRegEx)) {
                if(line.match(variableRegEx)) {
                    s += line.replace(/lighten\(|darken\(/gi, (matched) => {
                        return matched === 'darken(' ? 'lighten(' : 'darken(';
                    }) + "\n";
                }
            } else if(!line.match(excludeRegEx)) {
                s += line + "\n";
            }

            if (last) {
                fs.writeFileSync('temp.scss', s);

                gulp.src('temp.scss')
                    // gulp-sass gets rid of the empty ruleset automatically!
                    .pipe(sass().on('error', sass.logError))
                    .pipe(replace(/^body/m, '&'))
                    .pipe(rename('./../src/Resources/app/administration/src/colors.scss'))
                    .pipe(gulp.dest('./'));

                done();
            }
        })
    });
});
