<?php

namespace o;


// Report startup errors until the main handlers are initialized.
ini_set('display_errors', '1');
error_reporting(E_ALL);
set_error_handler('o\onStartupError');

// Avoid timezone warning
if (!ini_get('date.timezone')) {
    date_default_timezone_set('UTC');
}

// Doing this right away to include hits from static cache
if (isset($_SERVER['HTTP_USER_AGENT'])) {
    startupHitCounter();
    startupStaticCache();
}

restore_error_handler();

// Include THT lib
require dirname(__FILE__) . '/../lib/core/Tht.php';

// Run app
$thtReturnStatus = Tht::start();

return $thtReturnStatus;


//====================================



// TODO: sanitize file paths
function onStartupError($a, $errstr) {
    print '<b>THT Startup Error</b>';
    print '<p style="font-family:monospace">' . $errstr . '</p>';
    exit(1);
}

function startupStaticCache() {

    $cacheSecs = defined('STATIC_CACHE_SECONDS') ? constant('STATIC_CACHE_SECONDS') : 0;

    // Serve directly from HTML cache
    if ($cacheSecs && $cacheSecs !== 0) {

        $cacheFile = md5($_SERVER['SCRIPT_NAME']);
        $cachePath = APP_ROOT . '/data/cache/html/' . $cacheFile . '.html';
        if (file_exists($cachePath)) {
            if ($cacheSecs < 0 || time() < filemtime($cachePath) + $cacheSecs) {

                // security headers
                header("X-Content-Type-Options: nosniff");
                header("X-Frame-Options: deny");
                header("X-UA-Compatible: IE=Edge");
                header("X-Cached: true");
                header_remove("X-Powered-By");
                header("Content-Type: text/html");

                ob_start("ob_gzhandler");
                readfile($cachePath);
                ob_end_flush();

                exit(0);

            } else {
                // prevent stampede while cache is updated
                touch($cachePath);
            }
        }
    }
}

// Hit Counter
function startupHitCounter() {

    // skip bots
    $botRx = '/bot\b|crawl|spider|slurp|baidu|\bbing|duckduckgo|yandex|teoma|aolbuild/i';
    if (preg_match($botRx, $_SERVER['HTTP_USER_AGENT'])) { return; }

    $counterDir = APP_ROOT . '/data/counter';

    // date counter
    $date = strftime('%Y%m%d');
    $dateLogPath = $counterDir . '/date/' . $date . '.txt';
    file_put_contents($dateLogPath, '+', FILE_APPEND|LOCK_EX);

    // page counter - 1 byte per hit
    $page = $_SERVER['REQUEST_URI'];
    $page = preg_replace('/\?.*/', '', $page);
    $page = preg_replace('/[^a-zA-Z0-9]/', '_', $page);
    $page = trim($page, '_') ?: 'home';
    $pageLogPath = $counterDir . '/page/' . $page . '.txt';
    file_put_contents($pageLogPath, '+', FILE_APPEND|LOCK_EX);
}



