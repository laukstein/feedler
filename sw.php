<?php

include 'config.php';

cache($_SERVER['SCRIPT_FILENAME']);
header('Content-Type: application/javascript');

echo '"use strict";var version="v' . $_GET['ver'] . '";this.addEventListener("install",function(e){e.waitUntil(caches.open(version).then(function(e){return e.addAll(["' . $path . 'i/about", "' . $path . 'achieve/about", "' . $path . 'sw-' . $swVersion . '", "' . $path . 'style-' . filemtime('style.min.css') . '.css","' . $path . 'achieve-' . filemtime('achieve.min.css') . '.css","' . $path . 'favicon.png","' . $path . 'favicon.ico"])}))}),self.addEventListener("activate",function(e){e.waitUntil(caches.keys().then(function(e){return Promise.all(e.filter(function(e){return 0!==e.indexOf(version)}).map(function(e){return caches["delete"](e)}))}))}),self.addEventListener("fetch",function(e){var t=e.request;"GET"===t.method&&e.respondWith(caches.match(e.request).then(function(t){return t||fetch(e.request)["catch"](function(){return caches.match("achieve/about")})}))});';
