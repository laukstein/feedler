<?php


session_start();

if (isset($_POST['maxRange'])) {
    $_SESSION['imageShow'] = isset($_POST['imageShow']);
    $_SESSION['overCDN'] = isset($_POST['overCDN']);
    $_SESSION['maxRange'] = $_POST['maxRange'];
}

$cacheDir = '~cache';
$imageShow = isset($_SESSION['imageShow']) ? $_SESSION['imageShow'] : true;
$isLocalhost = preg_match('/^(127.0.0.1|10.0.0.\d{1,9})$/', $_SERVER['REMOTE_ADDR']);
$overCDN = isset($_SESSION['overCDN']) ? $_SESSION['overCDN'] : $isLocalhost;
$maxRange = isset($_SESSION['maxRange']) ? $_SESSION['maxRange'] : 3; // Feed time range limit


// IMPORTANT: Change $imagePrefix value to null if Cloudinary bandwidth is exceeded (is null on localhost)
$imagePrefix = $overCDN !== true ? null : '//res.cloudinary.com/laukstein/image/fetch/w_520,h_153,c_fill,g_face,f_auto/';
// $imagePrefix = null;



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
$swVersion = filemtime('sw.php');
$suggestions = [
    'http://www.cnet.com/rss/all/',
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
// http://www.cnet.com/rss/all/
// http://www.nytimes.com/
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

if (isset($_GET['url'])) {
    $file = preg_replace('/(index|)\\\.php$/', '', $file);
    $origin = preg_replace('/^\//', '', $path) . $file;
    $origin = preg_replace('/^' . str_replace('/', '\/', $origin) . '/', '', preg_replace('/^\//', '', $_GET['url']));
    $origin = preg_replace('/^\//', '', $origin);
}
if (empty($origin)) {
    $origin = '';
} else if ($avoidSpecialChars) {
    $origin = preg_replace('/^(https?)@/', '$1://', $origin);
}
if (isset($_POST['url'])) $url = $_POST['url'];
if (empty($url) && filter_var($origin, FILTER_VALIDATE_URL)) $url = $origin;
if (isset($_POST['remove']) && isset($_SESSION['listURL']) && isset($_SESSION['listURL'][$_POST['remove']])) unset($_SESSION['listURL'][$_POST['remove']]);

$showAll = empty($url);
$isPersonalized = isset($_SESSION['personalized']);
$maxCount = $isPersonalized ? false : 4; // limited items count

function minify_output($buffer) {
    return preg_replace(['/ {2,}/', '/<!--(?!\[if).*?-->|\t|<\/(option|li|dt|dd|tr|th|td)>|(?:\r?\n[ \t]*)+/s'], [' ', ''], $buffer);
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
    global $session, $isPersonalized, $listURL, $defaultURL, $url;

    $session = isset($_SESSION['listURL']) ? array_reverse($_SESSION['listURL']) : [];
    $isPersonalized = isset($_SESSION['personalized']);
    $listURL = isset($url) && strlen($url) ? [$url] : ($isPersonalized ? array_filter(array_keys($session)) : $defaultURL);
}

setSession();
