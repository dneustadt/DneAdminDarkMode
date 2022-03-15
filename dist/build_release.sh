#!/bin/bash
node node_modules/gulp/bin/gulp.js
cd ../../../../
./psh.phar administration:build
cd custom/plugins/DneAdminDarkMode/dist/
zip -r DneAdminDarkMode.zip ./../../DneAdminDarkMode -x '*dist*' -x '*.git*'
