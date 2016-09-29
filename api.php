<?php

function feedFeach($listURL) {
    global $base, $showAll, $json;

    $base = null;
    $json = [
        'info' => [],
        'item' => [],
        'status' => [],
        'loaded' => [],
        'session' => $listURL
    ];

    function absURL($url) {
        global $base;

        if (!$url) return $url;

        $url = (string) $url;

        if (!$url) return $base;
        if (parse_url($url, PHP_URL_SCHEME) || strpos($url, '//') === 0) return $url;
        if ($url[0] === '/') return $base . $url;
        if (strpos($base, '/', 9) === false) $base .= '/';

        return substr($base, 0, strrpos($base,'/') + 1) . $url;
    }
    function inTimeRange($time) {
        global $maxRange;

        if ($maxRange === 'nolimit') return true;

        $now = new DateTime;
        $ago = new DateTime((string) $time);
        $diff = $now->diff($ago);

        return !$diff->m && $maxRange >= $diff->d;
    }
    function elapsedTime($time, $full = false) {
        $now = new DateTime;
        $ago = new DateTime((string) $time);
        $diff = $now->diff($ago);
        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $str = [
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        ];

        foreach ($str as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($str[$k]);
            }
        }

        if (!$full) $str = array_slice($str, 0, 1);

        return $str ? implode(', ', $str) . ' ago' : 'just now';
    }
    function datetime($time) {
        return date('Y-m-d\TH:i\Z', strtotime((string) $time));
    }
    function summarizeText($str) {
        global $summarizeLenght;

        $str = preg_replace('/\s+/', ' ', strip_tags((string) $str));

        if (strlen($str) > $summarizeLenght) $str =  trim(mb_substr($str, 0, $summarizeLenght, 'utf-8')) . '…';

        return $str;
    }
    function newRequest() {
        global $json;

        if (count($json['session'])) {
            $diff = array_diff($json['session'], $json['loaded']);

            if (count($diff)) receiveData($diff);
        }
    }
    function setData($rss, $url) {
        global $base, $json, $maxCount, $status, $isPersonalized, $imageShow, $defaultURL;

        $newURL = isset($_SESSION['personalized']) || $json['session'] !== $defaultURL;

        if (empty($_SESSION['personalized'])) {
            $imageShow = isset($_SESSION['imageShow']) ? $_SESSION['imageShow'] : $newURL;
            $isPersonalized = $newURL;
            $maxCount = $newURL ? false : $maxCount;
        }
        if ($rss) {
            $i = 0;
            $namespaces = $rss->getNamespaces(true);
            $imageQuery = '//img[@src][not(contains(@src, "advertisement.gif")) and not(contains(@src, ".php"))]'; // Skip https://www.smashingmagazine.com/feed/ ads
            $dom = new DOMDocument;
            $dom->preserveWhiteSpace = false;
            libxml_use_internal_errors(true);

            if (@$rss->channel->item) { // RSS2.0
                $json['info'][$url] = array_filter([
                    'type' => 'RSS2.0',
                    'base' => $base,
                    'title' => summarizeText($rss->channel->title),
                    'link' => absURL($rss->channel->link),
                    'description' => summarizeText($rss->channel->description),
                    'lastBuildDate' => elapsedTime($rss->channel->lastBuildDate),
                    'datetime' => datetime($rss->channel->lastBuildDate),
                    'search' => $url
                ]);
                $json['info'][$url]['count'] = 0;
                $source = $json['info'][$url]['title'];

                foreach ($rss->channel->item as $item) {
                    if (inTimeRange($item->pubDate)) {
                        $image = @$item->children($namespaces['media'])->thumbnail;

                        if ($image) $image = $image->attributes()->url;
                        if (!$image) {
                            $media = @$item->children($namespaces['media'])->content;

                            if ($isPersonalized && $imageShow) {
                                if ($media && (string) $media->attributes()->medium === 'image') {
                                    // Workaround for http://rss.nytimes.com/services/xml/rss/nyt/InternationalHome.xml
                                    $image = $media->attributes()->url;
                                } else {
                                    $image = $media ? @$media->children($namespaces['media'])->thumbnail->attributes()->url : null;
                                }
                            }

                            $description = $media ? @$media->children($namespaces['media'])->description : null;
                        }
                        if (!$image && $isPersonalized && $imageShow) {
                            $dom->loadHTML($item->description);
                            libxml_clear_errors();
                            $xpath = new DOMXPath($dom);
                            $nodes = $xpath->query($imageQuery);

                            if (!$nodes->length) {
                                $content = @$item->children($namespaces['content'])->encoded;

                                if ($content) {
                                    $dom->loadHTML($content);
                                    libxml_clear_errors();
                                    $xpath = new DOMXPath($dom);
                                    $nodes = $xpath->query($imageQuery);
                                }
                            }
                            if ($nodes->length) {
                                foreach ($nodes as $node) {
                                    $image = $node->getAttribute('src');
                                    break;
                                }
                            }
                        }
                        if (isset($item->title) && strlen($item->title)) {
                            array_push($json['item'], array_filter([
                                'title' => summarizeText($item->title),
                                'link' => absURL($item->link),
                                'image' => $isPersonalized && $imageShow ? absURL($image) : null,
                                'description' => summarizeText(isset($item->description) ? $item->description : $description),
                                'pubDate' => elapsedTime($item->pubDate),
                                'datetime' => datetime($item->pubDate),
                                'source' => $source
                            ]));

                            $json['info'][$url]['count'] += 1;

                            if ($maxCount && ++$i === $maxCount) break;
                        }
                    } else {
                        break;
                    }
                }
                if (!count($json['item'])) array_push($json['status'], $status['range']);
            } else if (@$rss->item) { // RSS1.0
                $json['info'][$url] = array_filter([
                    'type' => 'RSS1.0',
                    'base' => $base,
                    'title' => summarizeText($rss->channel->title),
                    'link' => absURL($rss->channel->link),
                    'description' => summarizeText($rss->channel->description),
                    'lastBuildDate' => elapsedTime($rss->channel->lastBuildDate),
                    'datetime' => datetime($rss->channel->lastBuildDate),
                    'search' => $url
                ]);
                $json['info'][$url]['count'] = 0;
                $source = $json['info'][$url]['title'];

                foreach ($rss->item as $item) {
                    if (inTimeRange($item->children('http://purl.org/dc/elements/1.1/')->date)) {
                        $media = @$item->children($namespaces['content']);
                        $description = $media ? $media->description : null;

                        if ($isPersonalized && $imageShow) {
                            $image = $media && $media->encoded ? @$media->content->attributes()->url : null;

                            if (!$image) {
                                $dom->loadHTML($item->description);
                                libxml_clear_errors();
                                $xpath = new DOMXPath($dom);
                                $nodes = $xpath->query($imageQuery);

                                if (!$nodes->length) {
                                    $media = @$item->children($namespaces['content']);
                                    $content = $media ? $media->encoded : null;

                                    if ($content) {
                                        $dom->loadHTML($content);
                                        libxml_clear_errors();
                                        $xpath = new DOMXPath($dom);
                                        $nodes = $xpath->query($imageQuery);
                                    }
                                }
                                if ($nodes->length) {
                                    foreach ($nodes as $node) {
                                        $image = $node->getAttribute('src');
                                        break;
                                    }
                                }
                            }
                        }
                        if (isset($item->title) && strlen($item->title)) {
                            array_push($json['item'], array_filter([
                                'title' => summarizeText($item->title),
                                'link' => absURL($item->link),
                                'image' => $isPersonalized && $imageShow ? absURL($image) : null,
                                'description' => summarizeText($item->description),
                                'pubDate' => elapsedTime($item->children('http://purl.org/dc/elements/1.1/')->date),
                                'datetime' => datetime($item->children('http://purl.org/dc/elements/1.1/')->date),
                                'source' => $source
                            ]));

                            $json['info'][$url]['count'] += 1;

                            if ($maxCount && ++$i === $maxCount) break;
                        }
                    } else {
                        break;
                    }
                }
                if (!count($json['item'])) array_push($json['status'], $status['range']);
            } else if (@$rss->entry) { // ATOM
                $json['info'][$url] = array_filter([
                    'type' => 'ATOM',
                    'base' => $base,
                    'title' => summarizeText(isset($rss->entry->title) ? $rss->entry->title : $rss->title),
                    'link' => absURL($rss->link[count($rss->link) - 1]->attributes()->href),
                    'lastBuildDate' => elapsedTime($rss->updated),
                    'datetime' => datetime($rss->updated),
                    'search' => $url
                ]);
                $json['info'][$url]['count'] = 0;
                $source = isset($json['info'][$url]['title']) ? $json['info'][$url]['title'] : null;

                foreach ($rss->entry as $item) {
                    $date = $item->published;
                    $date = isset($date) ? $date : $item->updated;

                    if (inTimeRange($date)) {
                        // Youtube approach
                        $media = @$item->children($namespaces['media'])->group;
                        $image = $media ? @$media->children($namespaces['media'])->thumbnail->attributes()->url : null;
                        $description = $media ? @$media->children($namespaces['media'])->description : null;

                        if (!$image) {
                            $media = @$item->children($namespaces['media'])->media;
                            $image = $media ? @$media->children($namespaces['media'])->thumbnail->attributes()->url : null;
                            $description = $media ? @$media->children($namespaces['media'])->description : null;
                        }
                        if (!$image && $item->content && $isPersonalized && $imageShow) {
                            $dom->loadHTML($item->content);
                            libxml_clear_errors();
                            $xpath = new DOMXPath($dom);
                            $nodes = $xpath->query($imageQuery);

                            if ($nodes->length) {
                                foreach ($nodes as $node) {
                                    $image = $node->getAttribute('src');
                                    break;
                                }
                            }
                        }
                        if (isset($item->title) && strlen($item->title)) {
                            array_push($json['item'], array_filter([
                                'title' => summarizeText($item->title),
                                'link' => absURL($item->link->attributes()->href),
                                'image' => $isPersonalized && $imageShow ? absURL($image) : null,
                                'description' => summarizeText(isset($item->content) ? $item->content : $description),
                                'pubDate' => elapsedTime($date),
                                'datetime' => datetime($date),
                                'source' => $source
                            ]));

                            $json['info'][$url]['count'] += 1;

                            if ($maxCount && ++$i === $maxCount) break;
                        }
                    } else {
                        break;
                    }
                }
                if (!count($json['item'])) array_push($json['status'], $status['range']);
            }
            if ($newURL) {
                // Set personalized SESSION
                if (empty($_SESSION['listURL'])) {
                    $_SESSION['listURL'] = [];
                }
                if (empty($_SESSION['personalized'])) {
                    $_SESSION['personalized'] = true;
                    $_SESSION['imageShow'] = true;
                }
                if (empty($_SESSION['listURL'][$url])) {
                    $_SESSION['listURL'][$url] = [
                        'url' => $url,
                        'link' => isset($json['info'][$url]['link']) ? $json['info'][$url]['link'] : null,
                        'title' => isset($json['info'][$url]['title']) ? $json['info'][$url]['title'] : null,
                        'description' => isset($json['info'][$url]['description']) ? $json['info'][$url]['description'] : null,
                        'type' => isset($json['info'][$url]['type']) ? $json['info'][$url]['type'] : null
                    ];
                }

                $_SESSION['listURL'][$url]['count'] = isset($json['info'][$url]['count']) ? $json['info'][$url]['count'] : 0;
            }
        }
    }
    function receiveData($listURL, $result = '', $feedSearch = true, $originURL = null) {
        global $base, $json, $cacheDir, $cacheAge, $status;

        $url = is_array($listURL) ? array_shift($listURL) : $listURL;
        $originURL = isset($originURL) ? $originURL : $url;

        array_push($json['loaded'], $originURL);

        if (!$url) {
            array_push($json['status'], $status['empty']);
            return $json;
        }

        $url = preg_replace('/^(?!https?:\/\/)/', 'http://', $url);
        $file = $cacheDir . '/' . md5($originURL);
        $mtime = 0;
        $time = time();

        // if (!is_dir($cacheDir)) mkdir($cacheDir);
        // if (file_exists($file)) $mtime = filemtime($file);
        $mtime = @filemtime($file);

        if ($mtime && ($mtime + $cacheAge >= $time)) {
            // Return cached content
            $content = file_get_contents($file);
            $base = parse_url($url);
            $base = $base['scheme'] . '://' . $base['host'];

            setData(@simplexml_load_string($content), $originURL);
            newRequest();

            return $json;
        } else if (!filter_var($url, FILTER_VALIDATE_URL)) {
            array_push($json['status'], $status['url']);
            newRequest();

            return $json;
        } else if (!checkdnsrr(parse_url(trim($url), PHP_URL_HOST), 'A')) {
            array_push($json['status'], $status['exist']);
            newRequest();

            return $json;
        } else if ($ch = curl_init()) {
            // Remove old cache
            foreach (new DirectoryIterator($cacheDir) as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                } else if ($time - $fileInfo->getCTime() >= $cacheAge * 2) {
                    @unlink($fileInfo->getRealPath());
                }
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HEADER => 0,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_CONNECTTIMEOUT => 2,
                CURLOPT_FOLLOWLOCATION => 1,
                CURLOPT_USERAGENT => $_SERVER['HTTP_USER_AGENT'],
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_AUTOREFERER => 1,
                CURLOPT_SSL_VERIFYPEER => 0,
                CURLOPT_SSL_VERIFYHOST => 0
            ]);

            $content = curl_exec($ch);
            $redir = curl_getinfo($ch, CURLINFO_EFFECTIVE_URL);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

            curl_close($ch);

            if ($redir !== $url) {
                array_push($json['status'], 'Redirected to ' . $redir);

                $url = preg_replace('/^(?!https?:\/\/)/', 'http://', $redir);
            }
            if ($code !== 200 && $code !== 302 && $code !== 304) {
                array_push($json['status'], sprintf($status['error'], $code));
                newRequest();
                return $json;
            } else if ($rss = @simplexml_load_string($content)) {
                file_put_contents($file, $content);

                $base = parse_url($url);
                $base = $base['scheme'] . '://' . $base['host'];

                setData($rss, $originURL);
            } else if ($content && $feedSearch) {
                $dom = new DOMDocument;
                $dom->preserveWhiteSpace = false;

                libxml_use_internal_errors(true);
                $dom->loadHTML($content);
                libxml_clear_errors();

                $xpath = new DOMXPath($dom);
                $nodes = $xpath->query('//link[@href][(@type="application/atom+xml" or @type="application/rss+xml" or @type="application/rdf+xml")]');

                foreach ($nodes as $node) {
                    $urlUpdated = $node->getAttribute('href');
                    break;
                }

                if (isset($urlUpdated) && $urlUpdated !== $url) {
                    array_push($json['status'], $status['found'] . $urlUpdated);
                    return receiveData($urlUpdated, null, false, $originURL);
                } else {
                    array_push($json['status'], sprintf($status['feed'], $originURL));
                }
            } else {
                array_push($json['status'], sprintf($status['feed'], $originURL));
            }

            newRequest();

            return $json;
        } else {
            array_push($json['status'], sprintf($status['access'], $originURL));

            return $json;
        }
    }

    newRequest();

    return $json;
}

$json = feedFeach($listURL);

if (!count($json['item'])) unset($json['item']);
if (!count($json['info'])) unset($json['info']);
if (!count($json['loaded'])) unset($json['loaded']);
if (!count($json['status'])) unset($json['status']);
if (isset($json['item'])) {
    usort($json['item'], function($a, $b) {
        return strtotime($b['datetime']) - strtotime($a['datetime']);
    });

    if ($maxCount) $json['item'] = array_slice($json['item'], 0, $maxCount);
}

unset($json['session']);

setSession();
