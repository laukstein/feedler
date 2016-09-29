<?php

include 'config.php';

ob_start($toMinify ? 'minify_output' : 'ob_gzhandler');

header('Cache-Control: no-cache');

if ($scheme === 'https') header('Content-Security-Policy: upgrade-insecure-requests; referrer no-referrer');

$result = $navigation = $suggestionsForm = '';
$navigation = '';
$suggestionsForm = '';
$page = [
    'title' => empty($link) && empty($origin) ?
        ($isPersonalized ? 'All feeds' : 'Feedly delivers you the latest news') : null
];

if (!$isPersonalized && $origin !== 'about') {
    $page['description'] = [
        'Personalize your news list by entering feed address in search field.<br>Try for example <button class=highlight name=url value="http://www.cnet.com/rss/all/">http://www.cnet.com/rss/all/</button>',
        'Your list stays private. Read more <button name=page value=about>about Feedly</button>.'
    ];
    $page['form'] = true;
}

function info($obj) {
    return $obj['title'] ? $obj['title'] : null;
}
function item($obj) {
    global $imageShow, $imageFrefix;

    return '<a href="' . $obj['link'] . '" target=_blank rel="nofollow noopener" tabindex=0>' .
        (isset($obj['title']) ? '<h2 dir=auto>' . $obj['title'] . '</h2>' : null) .
        (isset($obj['image']) ? '<div class=image ' . ($imageShow ? null : 'data-') . 'style="background-image:url(\'' . $imageFrefix . $obj['image'] . '\')"></div>' : null) .
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
    if (!(empty($link) && empty($origin)) && isset($json['status'])) $result .= '<p>' . implode("</p><p>", $json['status']) . '</p>';
    if (isset($obj['type'])) $result .= '<dfn><a href="' . $url . '" title="The feed address" target=_blank rel="nofollow noopener" tabindex=0>' . $obj['type'] . '</a></dfn> ';
    if (isset($obj['lastBuildDate'])) $result .= '<time itemprop=dateModified datetime="' . $obj['lastBuildDate'] . '">Last update ' . $obj['lastBuildDate'] . '</time>';
    if (isset($obj['search'])) $result .= "<form action=$canonical target=_top method=post><button name=remove value=\"" . $obj['search'] . '">Remove this feed</button></form>';
    if (isset($obj['suggestionsForm'])) $result .= $suggestionsForm;

    return $result;
}

if ($origin === 'about') {
    $page['title'] = 'About Feedler';
    $page['content'] ='<p>Feedler is a personalized news reader, made specialy for <a href=https://a-k-apart.com target=_blank rel=noopener tabindex=0>10K Apart</a> contest. Open-source code in <a href=https://github.com/laukstein/feedler target=_blank rel=noopener tabindex=0>GitHub</a>.</p>
<h2>How to use</h2>
<p><b>Type a feed address and click Enter.</b><br>Supports RSS2.0, RSS1.0 and ATOM feed formats, LTR/RTL articles.<br>By default Feedler returns the last 3 days news, is customizable in UI.<br>After added the first feed, it will display also imeges for article is has, is customizable in UI.</p>
<h2>Benifits</h2>
<ul>
    <li>Optimized for 10kB inital page load (till user adds feeds)
    <li>Accessiable without JavaScript
    <li>Cached with ServiceWorker
    <li>Used HTML5 features
    <li>Cloudinary CDN
</ul>
<h2>Minimum server requirements</h2>
<p>Apache 2.4 + <var>headers_module</var> and <var>rewrite_module</var>, PHP 5.4 + <var>dom</var>, <var>curl</var> and <var>SimpleXML</var>.<br>Directory <var>~cache</var> must be writable, run <code>chmod -R 777 ~cache</code></p>
<p><var>config.php</var> contains configuration flags. Optimized images delivered trough Cloudinary CDN, if whenever exceeded CDN bandwidth, set <var>$imageFrefix</var> value to <b>null</b>.
<h2>Storage</h2>
<ul>
    <li>Web assets like CSS are stored in Web Cache Storage
    <li>Served feeds are stored for 5 minutes in <var>~cache</var>
    <li>The user session is stored in <var>session.save_path</var> till PHP <var>session.gc_maxlifetime</var> expired
</ul>
<h2>License</h2>
<p>Released under the CC BY-NC-ND 4.0 License.</p>';
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
        <li><label id=label-select hidden>News time range</label><select class=filter name=maxRange aria-labelledby=label-select onchange=this.form.submit()>";

        foreach ($maxRangeList as $key => $item) $navigation .= "<option value=$key" . ((string) $key === (string) $maxRange ? ' selected' : null) . ">$item";

        $navigation .= '</select>
        <li class=filter><label><input name=imageShow type=checkbox data-onchange=this.form.submit()' . ($imageShow ? ' checked' : null) . '> Show images</label><input type=submit value=Update>
        <li><button' . linkParams() .'>All <span>feeds</span></button>';

        foreach ($session as $item) {
            $navigation .= "\n        <li><button" . linkParams($item['url'], $item['title']) . ' data-count=' . $item['count'] . '>' .
                ($imageShow ? '<img src="https://www.google.com/s2/favicons?domain=' . parse_url($item['link'], PHP_URL_HOST) . '" width=16 height=16 alt="' . parse_url($item['url'], PHP_URL_HOST) . '">' : null) .
                '<span>' . $item['title'] . '</span></button>';
        }

        $navigation .= "\n    </ol>
<details>
    <summary>Add more</summary>\n$suggestionsForm
</details>
</form>";

        if (!$isPersonalized && isset($page['title'])) $result .= "\n    <div class=note role=article>" . pageHeader() . '</div>';

        $result .= ($isPersonalized ? $navigation : null) . "\n<div class=articles>";

        if (empty($page['title'])) $page['title'] = 'Doesn\'t contain any feed';
        if ($isPersonalized && (isset($page['title']) || isset($json['status']))) $result .= "\n    <header>" . pageHeader() . '</header>';
        if (isset($json['item'])) {
            foreach ($json['item'] as $item) $result .= "\n    <article>" . item($item) . '</article>';
        } else {
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

echo '<!doctype html>
<html lang=en>
<meta charset=utf-8>
<title>' . $page['title'] . " — Feedler</title>
<meta name=robots content=noindex>
<meta name=viewport content=\"width=device-width,initial-scale=1\">
<link rel=license href=//creativecommons.org/licenses/by-nc-nd/4.0/>
<link rel=stylesheet href={$path}achieve.min.css>
$result";
