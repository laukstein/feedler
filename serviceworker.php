<?php

include 'config.php';

header('Cache-Control: no-cache');
header('Content-Type: application/javascript');

function updateTime() {
    $directory = new RecursiveDirectoryIterator('.', FilesystemIterator::SKIP_DOTS);
    $filter = new RecursiveCallbackFilterIterator($directory, function ($current, $key, $iterator) {
        if (in_array($current->getPath(), ['.', '.\~cache'])) return substr($current->getFilename(), 0, 1) !== '.';
    });
    $iterator = new RecursiveIteratorIterator($filter, RecursiveIteratorIterator::SELF_FIRST);
    $iterator->setMaxDepth(1);
    $regex = new RegexIterator($iterator, '/.+\./');
    $date = [];

    foreach($regex as $fileInfo) array_push($date, $fileInfo->getMTime());

    return max($date);
}

echo '"use strict";var version="v' . updateTime() . '";this.addEventListener("install",function(e){e.waitUntil(caches.open(version).then(function(e){return e.addAll(["' . $path . 'serviceworker","' . $path . 'manifest","' . $path . 'icon.png","' . $path . 'favicon.png","' . $path . 'favicon.ico","' . $path . 'achieve/about","' . $path . 'style-' . filemtime('style.min.css') . '.css","' . $path . 'achieve-' . filemtime('achieve.min.css') . '.css"])}))}),self.addEventListener("activate",function(e){e.waitUntil(caches.keys().then(function(e){return Promise.all(e.filter(function(e){return!e.startsWith(version)}).map(function(e){return caches["delete"](e)}))}))}),self.addEventListener("fetch",function(n){var t=n.request;new URL(t.url).origin===location.origin&&"GET"===t.method&&n.respondWith(caches.match(t).then(function(n){return n||fetch(t)})["catch"](function(){return navigator.onLine?void 0:caches.match("' . $path . 'achieve/about")}))});';
