<?php

include 'config.php';

cache($_SERVER['SCRIPT_FILENAME']);
header('Content-Type: application/manifest+json');

echo '{
    "lang": "en",
    "name": "Feeder",
    "short_name": "Feeder",
    "version": "1",
    "description": "Feedler — personalized feed reader",
    "icons": [{
        "src": "' . $path . 'icon.png",
        "sizes": "196x196"
    }],
    "start_url": "' . $path . '",
    "display": "standalone",
    "theme_color": "#774cff",
    "background_color": "#f3f3f3"
}';
