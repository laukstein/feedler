<?php

include 'config.php';

ob_start($toMinify ? 'minify_output' : 'ob_gzhandler');

header('Cache-Control: no-cache');

if ($scheme === 'https') header('Content-Security-Policy: upgrade-insecure-requests; referrer no-referrer');

$result = $navigation = $suggestionsForm = '';
$navigation = '';
$suggestionsForm = '';
$page = [
    'title' => $isPersonalized ? (empty($link) && empty($origin) ? 'All feeds' : null) : 'Feedler delivers you the latest news'
];

if (!$isPersonalized && $origin !== 'about') {
    $page['description'] = [
        'Personalize your news list by entering feed address in search field.<br>Try for example <button class=highlight name=url value="http://www.cnet.com/rss/all/">http://www.cnet.com/rss/all/</button> <a href=' . $path . 'feedler.png title="Feedler with feeds" target=_blank>see Preview</a>.',
        'Your list stays private. Read more <button name=page value=about>about Feedler</button>.'
    ];
    $page['form'] = true;
}

function info($obj) {
    return $obj['title'] ? $obj['title'] : null;
}
function item($obj) {
    global $imageShow;

    return '<a href="' . $obj['link'] . '" target=_blank rel="nofollow noopener" tabindex=0>' .
        (isset($obj['title']) ? '<h2 dir=auto>' . $obj['title'] . '</h2>' : null) .
        (isset($obj['image']) && $imageShow ? '<div class=image style="background-image:url(\'' . imageOptimized($obj['image']) . '\')"></div>' : null) .
        (isset($obj['description']) ? '<p dir=auto>' . $obj['description'] . '</p>' : null) .
        '<time datetime="' . $obj['datetime'] . '">' . $obj['pubDate'] . (isset($obj['source']) ? ' — <span class=author dir=auto>' . $obj['source'] . '</span>' : null) . '</time></a>';
}
function linkParams($link = null, $title = null) {
    global $url, $origin, $page, $json;

    $isActive = empty($link) && empty($origin) || $link === $url;

    if ($isActive && isset($title)) {
        $page['title'] = $title;

        if (isset($link) && isset($json) && isset($json['info']) && isset($json['info'][$link])) {
            $page = $json['info'][$link];
        }
    }

    return ' name=url value="' . $link . '"' . ($isActive ? ' class=active' : null);
}
function pageHeader() {
    global $url, $json, $page, $canonical, $isPersonalized, $suggestionsForm;

    $obj = $page;
    $result = '';

    if (isset($obj['title'])) $result .= '<h1' . ($isPersonalized ? null : ' class=intro') . '>' . (isset($obj['link']) ? '<a href="' . $obj['link'] . '" target=_blank rel="nofollow noopener" tabindex=0>' . $obj['title'] . '</a>' : $obj['title']) . '</h1>';
    if (isset($obj['content'])) $result .= $obj['content'];
    if (isset($obj['description'])) {
        if (isset($page['form']))  $result .= "<form action=$canonical target=_top method=post>";

        $result .= '<p>' . (is_array($obj['description']) ? implode("</p>\n<p>", $obj['description']) : $obj['description']) . '</p>';

        if (isset($page['form']))  $result .= "</form>";
    }
    if (isset($json['status'])) $result .= '<p>' . (is_array($json['status']) ? implode("</p>\n<p>", $json['status']) : $json['status']) . '</p>';
    if (!(empty($link) && empty($origin)) && isset($json['status'])) $result .= '<p>' . implode("</p><p>", $json['status']) . '</p>';
    if (isset($obj['type'])) $result .= '<dfn>' .
        (isset($obj['feed']) ? '<a href="' . $obj['feed'] . '" title="The feed address" target=_blank rel="nofollow noopener" tabindex=0>' : null) . $obj['type'] .
        (isset($obj['feed']) ? '</a>' : null) . '</dfn> ';
    if (isset($obj['lastBuildDate'])) $result .= '<time itemprop=dateModified datetime="' . $obj['lastBuildDate'] . '">Last update ' . $obj['lastBuildDate'] . '</time>';
    if (isset($obj['search'])) $result .= "<form action=$canonical target=_top method=post><button name=remove value=\"" . $obj['search'] . '">Remove this feed</button></form>';
    if (isset($obj['suggestionsForm'])) $result .= $suggestionsForm;

    return $result;
}

if ($origin === 'about') {
    $page['title'] = 'About Feedler';
    $page['content'] ='<p>Feedler is a personalized news reader, optimized for performance and accessibility.</p>
<h2>History</h2>
<p>Feedler began with participating to <a href=https://a-k-apart.com target=_blank rel=noopener tabindex=0>10K Apart</a> with <a href=https://github.com/laukstein/feedler/releases/tag/v1.0 target=_blank rel=noopener tabindex=0>Version 1</a>, later continued improved. Hosted as open-source under <a href=https://github.com/laukstein/feedler target=_blank rel=noopener tabindex=0>GitHub</a>.</p>
<h2>How to use</h2>
<p><b>Type a feed address and click Enter.</b><br>Supports RSS2.0, RSS1.0 and ATOM feed formats, LTR/RTL articles.<br>By default Feedler returns the last 3 days news, is customizable in UI.<br>After added the first feed, it will display also images for article is has, is customizable in UI.</p>
<h2>Benifits</h2>
<ul>
    <li>Optimized for 10kB inital page load (till user adds feeds)
    <li>Accessiable without JavaScript
    <li>Simple offline with Service Worker
    <li>Optimized images over CDN
    <li>HTML5 native features
</ul>
<h2>Minimum server requirements</h2>
<p>Apache 2.4 + <var>rewrite_module</var> or IIS <var>web.config</var>, PHP 5.4 + <var>dom</var>, <var>curl</var> and <var>SimpleXML</var>.<br>Directory <var>~cache</var> must be writable: <pre><code>chmod -R 777 ~cache
chcon -Rt httpd_sys_content_rw_t ~cache/</code></pre></p>
<p><var>config.php</var> contains configuration flags.</p>
<p><b>Faster delivery</b> applies Cloudinary CDN for better image optimization (notice, may exceed the bandwidth).</p>
<h2>Storage</h2>
<ul>
    <li>CSS assets stored in Web Cache Storage
    <li>Served feeds are stored for 5 minutes in <var>~cache</var>
    <li>The user session is stored in <var>session.save_path</var> till PHP <var>session.gc_maxlifetime</var> expired
</ul>
<h2>License</h2>
<p>Released under the <a href=' . $path . 'LICENSE target=_blank tabindex=0>CC BY-NC-ND 4.0 License</a>.</p>';
    $page['suggestionsForm'] = false;
    $result .= "\n    <div class=note role=article>" . pageHeader() . '</div>';
} else {
    include 'api.php';

    $result .= '<div class="container' . ($isPersonalized ? ' personalized' : null) . '">';

    if ($isPersonalized) {
        $suggestionsForm .= "<form action=$canonical target=_top method=post>\n    <ol>";

        foreach ($suggestions as $item) $suggestionsForm .=  "\n        <li><button name=url value=\"$item\">$item</button>";

        $suggestionsForm .= "\n    </ol>\n</form>";
    }
    if (count($json)) {
        $navigation .= "\n<form class=subsc action=$canonical target=_top method=post>
    <ol>
        <li title=\"Change news time range\"><select class=filter name=maxRange aria-label=\"News time range\" onchange=this.form.submit()>";

        foreach ($maxRangeList as $key => $item) $navigation .= "<option value=$key" . ((string) $key === (string) $maxRange ? ' selected' : null) . ">$item";

        $navigation .= '</select>
        <li class=filter><label title="Display article images"><input name=imageShow type=checkbox onclick=this.form.submit()' . ($imageShow ? ' checked' : null) . '> Show images</label><label' .
            ($imageShow ? ' title="Use Cloudinary CDN for optimized image delivery (turn off when bandwidth exceeded)"' : ' class=disabled title="Requires &quot;Show images&quot; enabled"') .
            '><input name=imageFast type=checkbox' . ($imageShow ? ' onclick=this.form.submit()' . ($imageFast ? ' checked' : null) : ' disabled') . '> Faster delivery</label><input name=url value="' . $url . '" hidden><input id=x type=submit value=Update><script>document.getElementById("x").remove();</script>
        <li><button' . linkParams() .'>All <span>feeds</span></button>';

        foreach ($session as $item) {
            $navigation .= "\n        <li><button" . linkParams($item['url'], $item['title']) . ' data-count=' . $item['count'] . '>' .
                ($imageShow ? '<img src="https://www.google.com/s2/favicons?domain=' . parse_url($item['link'], PHP_URL_HOST) . '" width=16 height=16 alt="' . parse_url($item['url'], PHP_URL_HOST) . '">' : null) .
                '<span>' . $item['title'] . '</span></button>';
        }

        $navigation .= "\n    </ol>
<details>
    <summary title=\"Optional feeds\">Add more</summary>\n$suggestionsForm
</details>
</form>";

        if (!$isPersonalized && isset($page['title'])) $result .= "\n    <div class=note role=article>" . pageHeader() . '</div>';

        $result .= ($isPersonalized ? $navigation : null) . "\n<div class=articles>";

        $page['title'] = isset($page['title']) ? $page['title'] : 'Error';
        if ($isPersonalized && (isset($page['title']) || isset($json['status']))) $result .= "\n    <header>" . pageHeader() . '</header>';
        if (isset($json['item'])) {
            foreach ($json['item'] as $item) $result .= "\n    <article>" . item($item) . '</article>';
        } else if (empty($json['status'])) {
            $result .= "\n    <div class=note role=article>Doesn't contain any feed" . ($maxRange === 'nolimit' ? null : ' witin ' . lcfirst($maxRangeList[$maxRange])) . '.</div>';
        }

        $result .= "\n</div>\n</div>";
    } else {
        $page['title'] = 'No feeds';
        $page['description'] = 'Add feed address in above or try the suggested links:';
        $page['suggestionsForm'] = true;
        $result = "\n    <div class=note role=article>" . pageHeader() . '</div>';
    }
}
if (!empty($root)) $result .= "\n<link rel=\"shortcut icon\" href={$path}favicon.png>";

$verCSS = filemtime('achieve.min.css');

echo '<!doctype html>
<html lang=en>
<meta charset=utf-8>
<title>' . $page['title'] . " — Feedler</title>
<meta name=robots content=noindex>
<meta name=viewport content=\"width=device-width,initial-scale=1\">$metadata
<link rel=stylesheet href={$path}achieve-$verCSS.css>
<link rel=license href=//creativecommons.org/licenses/by-nc-nd/4.0/>
$result";
