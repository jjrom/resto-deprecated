#!/bin/bash
#
# RESTo framework (http:/github.com/jjrom/resto)
#
# Compress javascript dependencies files to dependencies.js single file
#
# Author : Jerome.Gasperi@gmail.com
# Date   : 2014.05.24
# Version: 1.0
#

echo ' ==> Copy modernizr.js to ../js'
cp js/modernizr/modernizr.min.js ../js

echo ' ==> Copy mapshup theme and i18n to ../js'
mkdir ../js/css
cp -R js/mapshup/i18n ../js
cp -R js/mapshup/theme/default/img ../js/css

echo ' ==> Pack javascript dependencies files to dependencies.js single file'
cat js/mjquery/mjquery.js > ../js/dependencies.js
cat js/mjquery/mjquery.ui.js >> ../js/dependencies.js
#cat js/swipebox/js/jquery.swipebox.min.js >> ../js/dependencies.js
cat js/history/jquery.history.js >> ../js/dependencies.js
cat js/visible/jquery.visible.min.js >> ../js/dependencies.js
cat js/mol/OpenLayers.js >> ../js/dependencies.js
cat js/mapshup/mapshup.js >> ../js/dependencies.js
cat js/mapshup/config/default.js >> ../js/dependencies.js

echo ' ==> Compress dependencies.js file with google closure compressor'
java -jar _compressors/js_compressor.jar ../js/dependencies.js > ../js/dependencies.min.js
rm -Rf ../js/dependencies.js

echo ' ==> Copy fontawesome to ../js'
cp -R js/fontawesome/fonts ../js

echo '==> Pack CSS files to dependencies.css single file'
cat js/mol/theme/default/style.css > ../js/css/dependencies.css
cat js/mjquery/mjquery.css >> ../js/css/dependencies.css
cat js/mapshup/theme/default/mapshup.css >> ../js/css/dependencies.css
cat js/foundation/foundation.min.css >> ../js/css/dependencies.css
#cat js/swipebox/css/swipebox.min.css >> ../js/css/dependencies.css
cat js/fontawesome/css/font-awesome.min.css >> ../js/css/dependencies.css

echo ' ==> Compress dependencies.css file with Yahoo! CSS compressor'
java -jar _compressors/css_compressor.jar ../js/css/dependencies.css > ../js/css/dependencies.min.css
rm -Rf ../js/css/dependencies.css

#echo ' ==> Copy images to ../js/img/'
#cp -R js/swipebox/img/* ../js/css/img


