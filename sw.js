"use strict";

var version = "v1";

this.addEventListener("install", function (event) {
    event.waitUntil(caches.open(version).then(function(cache) {
        return cache.addAll(["style.css", "achieve.css", "favicon.png", "achieve/about"]);
    }));
});
self.addEventListener("activate", function (event) {
    event.waitUntil(caches.keys().then(function (cache) {
        return Promise.all(cache.filter(function (key) {
            return key.indexOf(version) !== 0;
        }).map(function (key) {
            return caches["delete"](key);
        }))
    }))
});
self.addEventListener("fetch", function (event) {
    var request = event.request;

    if (request.method !== "GET") {
        event.respondWith(fetch(request)); return;
    }
});
