<?php

$cacheDir = '~cache';

session_save_path($cacheDir);
session_start();

if (isset($_POST['maxRange'])) {
    $_SESSION['imageShow'] = isset($_POST['imageShow']);
    $_SESSION['imageFast'] = isset($_POST['imageFast']);
    $_SESSION['maxRange'] = $_POST['maxRange'];
}

$maxRange = isset($_SESSION['maxRange']) ? $_SESSION['maxRange'] : 3; // Feed time range limit
$imageShow = isset($_SESSION['imageShow']) ? $_SESSION['imageShow'] : true;
$imageFast = isset($_SESSION['imageFast']) ? $_SESSION['imageFast'] : false;
$metadata = $imageFast ? "\n<meta http-equiv=Accept-CH content=DPR>" : null;

function imageOptimized($url = '') {
    global $imageFast;

    if (strlen($url)) {
        if ($imageFast === true) {
            // Cloudinary CDN for better optimization (notice, may exceed the bandwidth)
            return '//res.cloudinary.com/laukstein/image/fetch/w_520,h_153,c_fill,g_face,f_auto,dpr_auto/' . $url;
        } else {
            // Optional image crop proxies
            // https://images.weserv.nl/?url=domain.com/image.jpg&w=520&h=153&t=square&q=58&il
            // This all returns error 403 for image https://assets.materialup.com/uploads/bfc4fff8-520c-4746-808e-41f8f7ffb8b5/preview.png
            // https://next-geebee.ft.com/image/v1/images/raw/http://domain.com/image.jpg?source=.&width=520&height=153&quality=low
            // https://image.webservices.ft.com/v1/images/raw/http://domain.com/image.jpg?source=.&width=520&height=153&qualitylow
            // https://visuals.feedly.com/v1/resize?url=http://domain.com/image.jpg&sizes=520x153
            return 'https://images.weserv.nl/?url=' . preg_replace('/^https?\:\/\//', '', $url) . '&w=520&h=153&t=square&q=58&il';
        }
    } else {
        return $url;
    }
}

$maxRangeList = [
    1 => 'Last 24 hours',
    3 => 'Last 3 days',
    7 => 'Last 1 week',
    30 => 'Last 1 moth',
    'nolimit' => 'Show all'
];
$summarizeLenght = 100;
$cacheAge = 60 * 5; // Store/use cache for 5 min
$toMinify = true;
$suggestions = [
    // 'cnet.com',
    'http://www.cnet.com/rss/all/',
    // 'nytimes.com',
    'http://www.nytimes.com/',
    // https://www.youtube.com/playlist?list=PLrEnWoR732-BHrPp_Pm8_VleD68f9s14- must be transfered to this
    'https://www.youtube.com/feeds/videos.xml?playlist_id=PLrEnWoR732-BHrPp_Pm8_VleD68f9s14-',
    'http://blogs.windows.com/feed/',
    'https://www.smashingmagazine.com/feed/',
    'http://feeds.feedburner.com/CssTricks',
    'https://material.uplabs.com',
    'http://rss.walla.co.il/?w=/1/0/12/@rss.e'
];

// RSS tested
// https://sarasoueidan.com/rss.xml    => Azure returns "HTTP status code 0"
// cnet.com -> www.cnet.com -> http://www.cnet.com/rss/all/
// nytimes.com -> www.nytimes.com -> www.nytimes.com/services/xml/rss/nyt/HomePage.xml -> http://rss.nytimes.com/services/xml/rss/nyt/HomePage.xml
// http://nocamels.com/feed/
// http://blogs.windows.com/feed/
// https://sarasoueidan.com/rss.xml
// https://www.smashingmagazine.com/feed/
// https://www.youtube.com/feeds/videos.xml?playlist_id=PLrEnWoR732-BHrPp_Pm8_VleD68f9s14-
// http://youtube.com/apple
// https://www.softel.co.jp/blogs/tech/feed
// https://www.softel.co.jp/blogs/tech/feed/rdf
// https://www.softel.co.jp/blogs/tech/feed/atom
// http://feeds.feedburner.com/kizuruen
// http://alistapart.com/main/feed
// https://material.uplabs.com
// https://stripe.com/blog/feed.rss
// http://rss.walla.co.il/?w=/1/0/12/@rss.e
// http://feeds2.feedburner.com/joostdevalk
// http://yoast.com/feed/
// http://www.flickr.com/photos/tags/bristol/
// http://blogs.sitepoint.com/feed/
// https://www.wired.com/feed/

$defaultURL = [
    'http://www.nytimes.com/',
    'https://www.youtube.com/feeds/videos.xml?playlist_id=PLrEnWoR732-BHrPp_Pm8_VleD68f9s14-',
    'https://material.uplabs.com',
];
$status = [
    'range' => 'No news' . ($maxRange > 0 ? " in last $maxRange day" . ($maxRange > 1 ? 's' : null) : null) . '.',
    'feed' => '%s does\'t contain any feed. Try another address.',
    'access' => 'Can\'t access %s',
    'empty' => 'Add a feed address and try agin.',
    'url' => 'Invalid URL',
    'exist' => 'Non-existing Website',
    'found' => 'Found feed ',
    'error' => 'Website error (HTTP status: %d)'
];
$json = $session = $listURL = [];
$file = str_replace('.', '\.', basename($_SERVER['PHP_SELF']));
$path = preg_replace('/' . $file . '$/', '', $_SERVER['PHP_SELF']);
$root = str_replace($_SERVER['DOCUMENT_ROOT'], '', str_replace('\\', '/', getcwd()));
$scheme = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ||
          isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https' ? 'https' : 'http';
$pathNoSlash = getenv('DIR_SLASH') === 'off'; // Compatibility for "DirectorySlash Off" and "RewriteOptions AllowNoSlash"
$canonical = $scheme . '://' . $_SERVER['SERVER_NAME'] . '/' . preg_replace('/^\//', '', ($pathNoSlash ? preg_replace('/\/$/', '', $path) : $path));
$avoidSpecialChars = strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'microsoft-iis') === 0;
$url = null;

// URL regex http://regexr.com/3eepg
$urlRegex = '(https?:\/\/[^\s\:@.,]+\.+[a-z]|www\.[^\:\s\\,]+)[^\s\\\!,]{0,}[^\s\\\!|^\.,\?$]{1,}|[^\s\:,\/\/\@\-0=]{1,}[a-z0-9\-\.]\.[a-z]{2,}(\/*[^\s\\\!]{1,}[^\s\\\!|^\.,$]|)';

function validURL($url = '') {
    global $urlRegex;
    return isset($url) && preg_match("/$urlRegex/", $url);
}

if (isset($_GET['url'])) {
    $file = preg_replace('/(index|)\\\.php$/', '', $file);
    $origin = preg_replace('/^\//', '', $path) . $file;
    $origin = preg_replace('/^' . str_replace('/', '\/', $origin) . '/', '', preg_replace('/^\//', '', $_GET['url']));
    $origin = preg_replace('/^\//', '', $origin);
}
if (empty($origin)) {
    $origin = '';
} else {
    $origin = preg_replace('/^(https?)@/', '$1://', $origin);
}
if (isset($_POST['url']) && validURL($_POST['url'])) $url = $_POST['url'];
if (empty($url) && validURL($origin)) $url = $origin;
if (isset($_POST['remove']) && isset($_SESSION['listURL']) && isset($_SESSION['listURL'][$_POST['remove']])) unset($_SESSION['listURL'][$_POST['remove']]);

$showAll = empty($url) && !strlen($origin);
$isPersonalized = isset($_SESSION['personalized']);
$maxCount = $isPersonalized ? false : 4; // limited items count

function minify_output($buffer) {
    $search = ['/ {2,}/', '/<!--(?!\[if).*?-->|\t|<\/(option|li|dt|dd|tr|th|td)>|(?:\r?\n[ \t]*)+/s'];
    $blocks = preg_split('/(<\/?pre[^>]*>)/', $buffer, null, PREG_SPLIT_DELIM_CAPTURE);
    $replace = [' ', ''];
    $buffer = '';

    foreach ($blocks as $i => $block) $buffer .= $i % 4 === 2 ? $block : preg_replace($search, $replace, $block);

    return $buffer;
}
function cache($file) {
    $date = filemtime($file);

    if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && strtotime($_SERVER['HTTP_IF_MODIFIED_SINCE']) === $date) {
        http_response_code(304);
        ob_end_clean();
        exit;
    }

    header('Last-Modified: '. date('D, d M Y H:i:s', $date) . ' GMT');
}
function setSession() {
    global $session, $isPersonalized, $listURL, $defaultURL, $url, $showAll;

    $session = isset($_SESSION['listURL']) ? array_reverse($_SESSION['listURL']) : [];
    $isPersonalized = isset($_SESSION['personalized']);
    $listURL = isset($url) && strlen($url) ? [$url] : ($showAll ? ($isPersonalized ? array_filter(array_keys($session)) : $defaultURL) : []);
}

setSession();

if (!count($listURL) && $origin) {
    $json['status'] = [$status['url'] . ' ' . htmlspecialchars($origin)];
}

