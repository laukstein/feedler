<?php

include 'config.php';

cache($_SERVER['SCRIPT_FILENAME']);
header('Content-Type: application/manifest+json');

echo '{
    "lang": "en",
    "name": "Feedler — personalized news reader",
    "short_name": "Feeder",
    "version": "1",
    "description": "Feedler is a personalized news reader, made specialy for 10K Apart contest.",
    "icons": [{
        "src": "' . $path . 'icon.png",
        "sizes": "196x196"
    }],
    "start_url": "' . $path . '",
    "display": "standalone",
    "theme_color": "#774cff",
    "background_color": "#f3f3f3"
}';
