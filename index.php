<?php

include 'config.php';

if ($canonical !== $scheme . '://' . $_SERVER['SERVER_NAME'] . '/' . (isset($_GET['url']) ? $_GET['url'] : null)) {
    header("Location: $canonical", true, 301);
    exit;
}

ob_start($toMinify ? 'minify_output' : 'ob_gzhandler');

header('Cache-Control: no-cache');

if ($scheme === 'https') header('Content-Security-Policy: upgrade-insecure-requests; referrer no-referrer');

$url = isset($_POST['url']) ? htmlspecialchars($_POST['url'], ENT_COMPAT) : null;
$openPage = isset($_POST['page']) && $_POST['page'] === 'about' ? true : false;

echo "<!doctype html>
<html lang=en>
<meta charset=utf-8>
<title>Feedler</title>
<meta name=viewport content=\"width=device-width,initial-scale=1\">
<link rel=canonical href=$canonical>
<link rel=manifest href={$path}manifest>
<link rel=stylesheet href={$path}style.min.css>
<link rel=license href=//creativecommons.org/licenses/by-nc-nd/4.0/>";

if (!empty($root)) echo "\n<link rel=\"shortcut icon\" href={$path}favicon.png>";

echo "\n<header>
    <div>
        <form method=post>
            <div id=label-input hidden>Enter feed address</div>
            <div id=label-button hidden>Achieve</div>
            <input list=feeds name=url type=url placeholder=\"Add feed address\" spellcheck=false aria-labelledby=label-input aria-autocomplete=list aria-controls=feeds required" . (strlen($url) || $openPage ? null : ' autofocus') . '><button aria-labelledby=label-button><svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16"><path d="M11.7 10.3C12.4 9.6 13 8 13 6.5 13 3 10 0 6.5 0S0 3 0 6.5 3 13 6.5 13c1.6 0 3-.5 3.8-1.3l4.2 4.3 1.5-1.5-4.3-4.2zm-5.2.7C4 11 2 9 2 6.5S4 2 6.5 2 11 4 11 6.5 9 11 6.5 11z"/></svg></button>
            <button id=about name=page value=about aria-labelledby=label-about formnovalidate hidden>About</button>
            <button id=home name=url value="" aria-labelledby=label-home formnovalidate hidden>Home</button>
            <datalist id=feeds>';

foreach ($suggestions as $item) echo "\n                <option value=\"$item\">";

echo "\n            </datalist>
        </form>
        <div class=flow>
            <label id=label-about for=about tabindex=0>About</label>
            <label id=label-home for=home tabindex=0>Home</label>
        </div>
    </div>
</header>
<iframe name=content class=content src=\"{$path}achieve" . ($openPage ? '/about' : (strlen($url) ? '/' . $url : null)) . "\" width=640 height=960>Loading iframe data...</iframe>
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
