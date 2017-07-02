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
startupHitCounter();
startupStaticCache();

restore_error_handler();

require dirname(__FILE__) . '/../lib/core/Owl.php';

$OWL_RETURN_STATUS = Owl::start();



//====================================


// TODO: sanitize file paths
function onStartupError($a, $errstr) {
    print '<b>OWL Startup Error</b>';
    print '<p style="font-family:monospace">' . $errstr . '</p>';
    exit(1);
}

function startupStaticCache() {

    // Serve directly from HTML cache
    if (defined('STATIC_CACHE_SECONDS') && STATIC_CACHE_SECONDS !== 0) {

        $cacheFile = md5($_SERVER['SCRIPT_NAME']);
        $cachePath = APP_ROOT . '/data/cache/html/' . $cacheFile . '.html';
        if (file_exists($cachePath)) {
            if (STATIC_CACHE_SECONDS < 0 || time() < filemtime($cachePath) + STATIC_CACHE_SECONDS) {

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

    if (!defined('APP_ROOT')) { return; }

    // skip bots
    $botRx = '/bot\b|crawl|spider|slurp|baidu|\bbing|duckduckgo|yandex|teoma|aolbuild/i';
    if (preg_match($botRx, $_SERVER['HTTP_USER_AGENT'])) { return; }

    $counterDir = APP_ROOT . '/data/files/counter';

    // date counter
    $date = strftime('%Y%m%d');
    $dateLogPath = $counterDir . '/date/' . $date . '.txt';
    file_put_contents($dateLogPath, '+', FILE_APPEND|LOCK_EX);

    // page counter
    $page = $_SERVER['REQUEST_URI'];
    $page = preg_replace('/\?.*/', '', $page);
    $page = preg_replace('/[^a-zA-Z0-9]/', '_', $page);
    $page = trim($page, '_') ?: 'home';
    $pageLogPath = $counterDir . '/page/' . $page . '.txt';
    file_put_contents($pageLogPath, '+', FILE_APPEND|LOCK_EX);
}

//====================================


return $OWL_RETURN_STATUS;

