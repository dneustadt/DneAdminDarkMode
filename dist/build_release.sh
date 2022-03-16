#!/bin/bash
node node_modules/gulp/bin/gulp.js
cd ../../../../
./psh.phar administration:build
cd custom/plugins/
zip -r DneAdminDarkMode.zip ./DneAdminDarkMode -x '*dist*' -x '*.git*'
cd DneAdminDarkMode/dist/
