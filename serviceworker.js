// Offline Cookbook https://developers.google.com/web/fundamentals/instant-and-offline/offline-cookbook/
// https://developers.google.com/web/fundamentals/instant-and-offline/service-worker/lifecycle

"use strict";

var version = "v1";

this.addEventListener("install", function (event) {
    // this.skipWaiting();

    event.waitUntil(caches.open(version).then(function (cache) {
        return cache.addAll(["/serviceworker", "/manifest", "/icon.png", "/favicon.png", "/favicon.ico", "/achieve/about", "/style.css", "/achieve.css"]);
    }));
});
self.addEventListener("activate", function (event) {
    event.waitUntil(caches.keys().then(function (cache) {
        return Promise.all(cache.filter(function (key) {
            return !key.startsWith(version);
        }).map(function (key) {
            return caches.delete(key);
        }));
    }));
});
self.addEventListener("fetch", function (event) {
    var request = event.request;

    if (new URL(request.url).origin === location.origin && request.method === "GET") {
        event.respondWith(caches.match(request).then(function (response) {
            return response || fetch(request);
        }).catch(function () {
            if (!navigator.onLine) {
                return caches.match("/achieve/about");
            }
        }));
    }
});
