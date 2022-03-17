const gulp = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const rename = require('gulp-rename');
const concat = require('gulp-concat');
const replace = require('gulp-replace');
const fs = require('fs');
const lineReader = require('line-reader');
const stripCssComments = require('gulp-strip-css-comments');

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
            basePath + '/app/component/**/!(sw-admin-menu).scss',
            basePath + '/module/**/**.scss',
        ])
        .pipe(concat('app.scss'))
        .pipe(stripCssComments())
        .pipe(gulp.dest('.'))
        .on('end', resolve);
    }).then(() => {
        let s = '';

        const excludeRegEx = new RegExp('^@import|@include');
        const propertyRegEx = new RegExp(':(.*?);');
        const variableRegEx = new RegExp('^\\$|\\$color-(.*?)|\\$(.*?)-(color|background|dark|light)');
        const literalColorRegEx = new RegExp('#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})|(white|black|255, 255, 255)');
        const colorMappings = JSON.parse(fs.readFileSync('hex-mappings.json'));
        let replaceColorsLiteral = false;

        const readStream = fs.createReadStream('app.scss');
        lineReader.eachLine(readStream, (line, last) => {
            replaceColorsLiteral = replaceColorsLiteral || line === '// start replace literal colors';

            if (line.match(excludeRegEx)) {
                return;
            }

            const isProperty = line.match(propertyRegEx);

            if (!isProperty) {
                s += line + "\n";
            }

            if (last) {
                fs.writeFileSync('temp.scss', s);

                gulp.src('temp.scss')
                    .pipe(sass().on('error', sass.logError))
                    .pipe(replace(/^body/m, '&'))
                    .pipe(rename('./../src/Resources/app/administration/src/colors.scss'))
                    .pipe(gulp.dest('./'))
                    .on('end', done);
            }

            if (!isProperty) {
                return;
            }

            line = replaceColorsLiteral ? line.replace(propertyRegEx, (propertyValue) => {
                if (propertyValue.match(variableRegEx)) {
                    return propertyValue;
                }

                return propertyValue.replace(literalColorRegEx, (matched) => {
                    if (!colorMappings.hasOwnProperty(matched)) {
                        throw `Found literal hex code ${matched} at line ${line}`;
                    }

                    return colorMappings[matched];
                });
            }) : line;

            if(line.match(variableRegEx)) {
                s += line.replace(/lighten\(|darken\(/gi, (matched) => {
                    return matched === 'darken(' ? 'lighten(' : 'darken(';
                }) + "\n";
            }
        });
    });
});
