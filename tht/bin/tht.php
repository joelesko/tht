<?php

namespace o;

if (isset($_SERVER['HTTP_USER_AGENT'])) {

    // Avoid timezone warning
    if (!ini_get('date.timezone')) {
        date_default_timezone_set('UTC');
    }

    // Report startup errors until the main handlers are initialized.
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
    set_error_handler('o\onStartupError');

    // Doing this right away to include hits from static cache
    startupHitCounter();

    startupStaticCache();


    restore_error_handler();
}


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

    $cacheTag = defined('STATIC_CACHE_TAG') ? constant('STATIC_CACHE_TAG') : '';

    // Serve directly from HTML cache
    if ($cacheTag) {

        $cacheFile = md5($_SERVER['SCRIPT_NAME']);
        $cachePath = DATA_ROOT . '/cache/html/' . $cacheTag . '_' . $cacheFile . '.html';

        if (file_exists(realpath($cachePath))) {

            // security headers
            header("X-Content-Type-Options: nosniff");
            header("X-Frame-Options: deny");
            header("X-UA-Compatible: IE=Edge");
            header("X-Static-Cache: true");
            header_remove("X-Powered-By");
            header("Content-Type: text/html");

            ob_start("ob_gzhandler");
            readfile($cachePath);
            ob_end_flush();

            exit(0);
        }
    }
}

// Hit Counter - no significant performance hit
function startupHitCounter() {

    if (defined('HIT_COUNTER') && constant('HIT_COUNTER') === false) {
        return;
    }

    // skip bots
    $botRx = '/bot\b|crawl|spider|slurp|baidu|\bbing|duckduckgo|yandex|teoma|aolbuild/i';
    if (preg_match($botRx, $_SERVER['HTTP_USER_AGENT'])) { return; }

    $counterDir = DATA_ROOT . '/counter';

    // date counter - 1 byte per hit
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

    // referrer log - 1 line per referrer
    $ref = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
    if ($ref) { 
        if (stripos($ref, $_SERVER['HTTP_HOST']) === false) {
            $fileDate = strftime('%Y%m');
            $lineDate = strftime('%Y-%m-%d');
            $referrerLogPath = $counterDir . '/referrer/' . $fileDate . '.txt';
            file_put_contents($referrerLogPath, "[$lineDate] $ref -> " . $_SERVER['REQUEST_URI'] . "\n", FILE_APPEND|LOCK_EX);
        }
    }
}



