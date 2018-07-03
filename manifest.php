<?php

header('Content-Type: application/manifest+json; charset=utf-8');

include 'config.php';

echo '{
    "lang": "en",
    "name": "Feedler — personalized news reader",
    "short_name": "Feeder",
    "description": "Feedler is a personalized news reader, optimized for performance and accessibility.",
    "icons": [{
        "src": "' . $path . 'icon.png",
        "sizes": "192x192"
    }],
    "start_url": "' . $path . '",
    "display": "standalone",
    "theme_color": "#774cff",
    "background_color": "#f3f3f3"
}';
