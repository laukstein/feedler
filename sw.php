<?php

include 'config.php';

cache($_SERVER['SCRIPT_FILENAME']);
header('Content-Type: application/javascript');

echo '"use strict";var version="' . $_GET['ver'] . '";this.addEventListener("install",function(e){e.waitUntil(caches.open(version).then(function(e){return e.addAll(["' . $path . 'style.css","' . $path . 'achieve.css","' . $path . 'favicon.png","' . $path . 'achieve/about"])}))}),self.addEventListener("activate",function(e){e.waitUntil(caches.keys().then(function(e){return Promise.all(e.filter(function(e){return 0!==e.indexOf(version)}).map(function(e){return caches["delete"](e)}))}))}),self.addEventListener("fetch",function(e){var t=e.request;return"GET"!==t.method?void e.respondWith(fetch(t)):void 0});';
