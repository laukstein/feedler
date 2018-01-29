# [Feedler v2](https://lab.laukstein.com/feedler)

Feedler is a personalized news reader, optimized for performance and accessibility.

![Feedler](feedler.png "Feedler with feeds")


## History

Feedler began with participating to [10K Apart](https://a-k-apart.com/gallery/Feedler-personalized-news) with [Version 1](https://github.com/laukstein/feedler/releases/tag/v1.0), later continued improved.


## How to use

**Type a feed address and click Enter.**<br>
Supports RSS2.0, RSS1.0 and ATOM feed formats, LTR/RTL articles.<br>
By default Feedler returns the last 3 days news, is customizable in UI.<br>
After added the first feed, it will display also images for article is has, is customizable in UI.


## Benifits

* 10kB inital page load (till user customized profile)
* Accessiable without JavaScript
* Simple offline with Service Worker
* Optimized images over CDN
* HTML5 native features


## Server requirements

Apache 2.4 + rewrite_module or IIS web.config, PHP 5.4 + dom, curl and SimpleXML.<br>
Directory [`~cache`](~cache) must be writable:

    chmod -R 777 ~cache
    chcon -Rt httpd_sys_content_rw_t ~cache/

[`config.php`](config.php) contains configuration flags.

**Faster delivery** applies Cloudinary CDN for better image optimization (notice, Cloudinary may exceed the bandwidth).


## Storage

* CSS assets stored in Web Cache Storage
* Served feeds are stored for 5 minutes in `~cache`
* The user session is stored in `~cache` till PHP `session.gc_maxlifetime` expires


## License

Released under the [CC BY-NC-ND 4.0 License](LICENSE).
