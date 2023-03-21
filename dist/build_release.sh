#!/bin/bash
cd custom/plugins/
zip -r DneAdminDarkMode.zip ./DneAdminDarkMode -x '*dist*' -x '*.git*'
cd DneAdminDarkMode/dist/
