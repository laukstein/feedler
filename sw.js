"use strict";

var version = "v1";

this.addEventListener("install", function (event) {
    event.waitUntil(caches.open(version).then(function (cache) {
        return cache.addAll(["i/about", "achieve/about", "sw.js", "style.css", "achieve.css", "favicon.png", "favicon.ico"]);
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

    if (request.method === "GET") {
        event.respondWith(caches.match(event.request).then(function (response) {
            return response || fetch(event.request).catch(function (error) {
                return caches.match("achieve/about");
            });
        }));
    }
});
