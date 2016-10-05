<?php

include 'config.php';

header('Cache-Control: no-cache');

$url = isset($_POST['url']) ? htmlspecialchars($_POST['url'], ENT_COMPAT) : null;
$openPage = isset($_POST['page']) && $_POST['page'] === 'about' ? true : false;
$targetURL = $openPage ? '/about' : (strlen($url) ? '/' . $url : null);
$currentURL = isset($_GET['i']) ? preg_replace('/^\//', '', $_GET['i']) : null; // preg_replace() used for IIS compatibility

if ($targetURL) {
    header("Location: {$path}i$targetURL");
    exit;
} else if (!$url) {
    $inRoot = preg_match('/^\/?' . str_replace('/', '\/', $currentURL) . '\/?$/', $path);

    if (($url === '' || $inRoot) && $canonical !== $scheme . '://' . $_SERVER['SERVER_NAME'] . '/' . $currentURL) {
        header("Location: $canonical", true, 301);
        exit;
    } else {
        $url = $inRoot ? null : preg_replace('/^' . str_replace('/', '\/', preg_replace('/^\//', '', $path)) . 'i\//', '', $currentURL);
        $openPage = $url === 'about';
        $targetURL = $url ?  '/' . $url : null;
    }
}

if ($scheme === 'https') header('Content-Security-Policy: upgrade-insecure-requests; referrer no-referrer');

ob_start($toMinify ? 'minify_output' : 'ob_gzhandler');

$verCSS = filemtime('style.min.css');

echo "<!doctype html>
<html lang=en>
<meta charset=utf-8>
<title>Feedler</title>
<meta name=viewport content=\"width=device-width,initial-scale=1\">
<link rel=canonical href=$canonical>
<link rel=manifest href={$path}manifest>
<link rel=stylesheet href={$path}style-$verCSS.css>
<link rel=license href=//creativecommons.org/licenses/by-nc-nd/4.0/>";

if (!empty($root)) echo "\n<link rel=\"shortcut icon\" href={$path}favicon.png>";

echo "\n<header>
    <div>
        <form method=post>
            <input list=feeds name=url type=url placeholder=\"Add feed address\" spellcheck=false aria-label=\"Enter feed address\" aria-autocomplete=list aria-controls=feeds required" . (strlen($url) || $openPage ? null : ' autofocus') . '><button aria-label=Achieve><svg viewBox="0 0 16 16" aria-hidden=true><path d="M11.7 10.3C12.4 9.6 13 8 13 6.5 13 3 10 0 6.5 0S0 3 0 6.5 3 13 6.5 13c1.6 0 3-.5 3.8-1.3l4.2 4.3 1.5-1.5-4.3-4.2zm-5.2.7C4 11 2 9 2 6.5S4 2 6.5 2 11 4 11 6.5 9 11 6.5 11z"/></svg></button>
            <button id=about name=page value=about formnovalidate hidden>About</button>
            <button id=home name=url value="" formnovalidate hidden>Home</button>
            <datalist id=feeds>';

foreach ($suggestions as $item) echo "\n                <option value=\"$item\">";

echo "\n            </datalist>
        </form>
        <div class=flow>
            <label for=about tabindex=0>About</label>
            <label for=home tabindex=0>Home</label>
        </div>
    </div>
</header>
<iframe name=content class=content src=\"{$path}achieve$targetURL\" width=640 height=960>Loading iframe data...</iframe>
<div class=\"content progress\">
    <div class=logo><span></span></div>
    <h1><span>Feedler</span><small>Specialy for 10K Apart</small></h1>";

if (!$openPage) {
    echo '    <p><span class=spin><span></span></span>Achieve the latest news</p>
    <ul class=list>';

    if (strlen($url)) {
        echo "        \n<li><div>$url</div>";
    } else {
        foreach ($listURL as $item) echo "        \n<li><div>$item</div>";
    }

    echo "\n</ul>";
}

echo '
</div>
<script>("localhost"===location.host||"https"===location.protocol)&&"serviceWorker"in navigator&&navigator.serviceWorker.register("sw-'. $swVersion .'")</script>';
