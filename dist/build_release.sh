#!/bin/bash
node node_modules/gulp/bin/gulp.js
cd ../../../../
if [ -f "psh.phar" ]; then
    ./psh.phar administration:build composer
else
    composer run build:js:admin
fi
cd custom/plugins/
zip -r DneAdminDarkMode.zip ./DneAdminDarkMode -x '*dist*' -x '*.git*'
cd DneAdminDarkMode/dist/
