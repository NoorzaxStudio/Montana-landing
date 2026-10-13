<?php $site = (((!empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off") || ($_SERVER["HTTP_X_FORWARDED_PROTO"] ?? "") === "https") ? "https://" : "http://") . $_SERVER["HTTP_HOST"]; ?>
<?php header("Content-Type: text/plain; charset=utf-8"); ?>
User-Agent: *
Allow: /
Disallow: /api/

Sitemap: <?php echo $site; ?>/sitemap.xml
