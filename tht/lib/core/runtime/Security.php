<?php

namespace o;

// All sensitive operations in one place, for easier auditing

class Security {

    static private $CSP_NONCE = '';
    static private $CSRF_TOKEN_LENGTH = 32;
    static private $NONCE_LENGTH = 40;

    static private $SESSION_ID_LENGTH = 48;
    static private $SESSION_COOKIE_DURATION = 0;  // until browser is closed

    static private $THROTTLE_PASSWORD_WINDOW_SECS = 3600;

    static private $isCrossOrigin = null;
    static private $isCsrfTokenValid = null;
    static private $isDev = null;

    static private $prevHash = null;

    static private $PHP_BLOCKLIST_MATCH = '/pcntl_|posix_|proc_|ini_|mysql|sqlite/i';

    static private $PHP_BLOCKLIST = [
        'assert',
        'call_user_func',
        'call_user_func_array',
        'create_function',
        'dl',
        'eval',
        'exec',
        'extract',
        'file',
        'file_get_contents',
        'file_put_contents',
        'fopen',
        'include',
        'include_once',
        'parse_str',
        'passthru',
        'phpinfo',
        'popen',
        'require',
        'require_once',
        'rmdir',
        'serialize',
        'shell_exec',
        'system',
        'unlink',
        'unserialize',
        'url_exec',
    ];

    static function error($msg) {

        ErrorHandler::addOrigin('security');
        Tht::error($msg);
    }

    // TODO: Allow multiple IPs (via list in app.jcon)
    static function isDev() {

        if (self::$isDev !== null) { return self::$isDev; }

        $ip = self::getClientIp();
        $devIp = Tht::getConfig('devIp');
        $isDevIp = $devIp && $devIp == $ip;

        self::$isDev = false;
        if ($isDevIp || self::isLocalServer()) {
            self::$isDev = true;
        }

        return self::$isDev;
    }

    static function getClientIp() {
        return Tht::getPhpGlobal('server', 'REMOTE_ADDR');
    }

    // Print sensitive data.  Only print to log if not in Admin mode.
    static function safePrint($data) {

        if (Security::isDev()) {
            Tht::module('Bare')->u_print($data);
        }
        else {
            Tht::module('Bare')->u_print('Info written to `data/files/app.log`');
            Tht::module('Bare')->u_log($data);
        }
    }

    // Filter super globals and move them to internal data
    static function initRequestData () {

        $data = [
            'get'     => $_GET,
            'post'    => $_POST,
            'cookie'  => $_COOKIE,
            'files'   => $_FILES,
            'server'  => $_SERVER,
            'env'     => getenv(),
            'headers' => self::initHttpRequestHeaders($_SERVER),
        ];

        if (isset($data['headers']['content-type']) && $data['headers']['content-type'] == 'application/json') {

            // Parse JSON
            $raw = file_get_contents("php://input");
            $data['post'] = Tht::module('Json')->u_decode($raw);
            $data['post']['_raw'] = $raw;
        }
        // else if (in_array($data['headers']['method'], ['put', 'patch', 'delete'])) {

        //     // Convert other HTTP methods to POST
        //     $raw = file_get_contents("php://input");
        //     parse_str($raw, $data['post']);
        //     $data['post']['_raw'] = $raw;
        // }
        else if (isset($HTTP_RAW_POST_DATA)) {

            $data['post']['_raw'] = $HTTP_RAW_POST_DATA;
        }

        // Make env all uppercase, to be case-insensitive
        $upEnv = $data['env'];
        foreach ($data['env'] as $k => $v) {
            $upEnv[strtoupper($k)] = $v;
        }
        $data['env'] = $upEnv;

        return $data;
    }

    static private function initHttpRequestHeaders($serverVars) {

        $headers = [];

        // Convert http headers to uniform kebab-case
        foreach ($serverVars as $k => $v) {
            if (substr($k, 0, 5) === 'HTTP_') {
                $base = substr($k, 5);
                $base = str_replace('_', '-', strtolower($base));
                $headers[$base] = $v;
            }
        }

        // Make sure these are retrieved via the Request API.  Not raw headers.
        unset($headers['referer']);
        unset($headers['cookie']);
        unset($headers['accept-language']);
        unset($headers['host']);
        unset($headers['user-agent']);

        return $headers;
    }

    // Fetch policy using 'sec-fetch-*' headers
    // https://web.dev/fetch-metadata/
    static function validateRequestOrigin() {

        $site = Tht::getPhpGlobal('headers', 'sec-fetch-site');

        // Old browser.  Just deny 'accept: image' as a stopgap.
        if (!$site) {
            $accept = Tht::getPhpGlobal('headers', 'accept');
            if (preg_match('#image/#i', $site)) {
                Tht::error(403, 'Remote request not allowed.');
            }
            return;
        }

        // Allow all requests from same origin or direct navigation
        if (in_array($site, ['same-origin', 'same-site', 'none'])) {
            return;
        }

        // Only allow navigation action for remote origin.
        $mode = Tht::getPhpGlobal('headers', 'sec-fetch-mode');
        if ($mode != 'navigate') {
            Tht::error(403, 'Only navigation is allowed from remote origin.');
        }

        // Don't allow remote navigation from objects, etc.
        $dest = Tht::getPhpGlobal('headers', 'sec-fetch-dest');
        if (in_array($dest, ['object', 'embed'])) {
            Tht::error(403, 'Remote request from objects not allowed.');
            return;
        }
    }

    static function hashString($raw) {

        self::checkPrevHash($raw);

        $hash = hash('sha256', $raw);
        self::$prevHash = $hash;

        return $hash;
    }

    // Prevent well-meaning attempts to hash a string multiple times for "extra" security.
    static function checkPrevHash($raw) {

        if (!is_null(self::$prevHash) && $raw == self::$prevHash) {
            self::error('Hashing an already-hashed value results in a value that is easier to attack.');
        }
    }

    static function hashPassword($raw) {

        Tht::module('Perf')->u_start('Password.hash');

        self::checkPrevHash($raw);

        $hash = password_hash($raw, PASSWORD_DEFAULT);
        self::$prevHash = $hash;

        Tht::module('Perf')->u_stop();

        return $hash;
    }

    static function verifyPassword($plainText, $correctHash) {

        return password_verify($plainText, $correctHash);
    }

    static function createPassword ($plainText) {

        return new OPassword ($plainText);
    }

    static function getCsrfToken() {

        $token = Tht::module('Session')->u_get('csrfToken', '');

        if (!$token) {
            $token = Tht::module('String')->u_random(self::$CSRF_TOKEN_LENGTH);
            Tht::module('Session')->u_set('csrfToken', $token);
        }

        return $token;
    }

    static function validateCsrfToken() {

        if (!is_null(self::$isCsrfTokenValid)) {
            return self::$isCsrfTokenValid;
        }

        self::$isCsrfTokenValid = false;

        $localCsrfToken = Tht::module('Session')->u_get('csrfToken', '');
        $post = Tht::data('requestData', 'post');
        $remoteCsrfToken = isset($post['csrfToken']) ? $post['csrfToken'] : '';

        if ($localCsrfToken && hash_equals($localCsrfToken, $remoteCsrfToken)) {
            self::$isCsrfTokenValid = true;
        }

        return self::$isCsrfTokenValid;
    }

    static function getNonce() {

        if (!self::$CSP_NONCE) {
            self::$CSP_NONCE = self::randomString(self::$NONCE_LENGTH);
        }

        return self::$CSP_NONCE;
    }

    static function randomString($len) {

        // random_int is crypto secure
        $str = '';
        for ($i = 0; $i < $len; $i++) {
            $str .= base_convert(random_int(0, 35), 10, 36);
        }

        return $str;
    }

    // Fisher-Yates with crypto-secure random_int()
    // Assumably more secure than built-in shuffle()
    static function shuffleList($list) {

        $numEls = count($list);

        for ($i = $numEls - 1; $i > 0; $i -= 1) {
            $j = random_int(0, $i);
            $tmp = $list[$j];
            $list[$j] = $list[$i];
            $list[$i] = $tmp;
        }

        return $list;
    }

    static function initSessionParams() {

        ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 0);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_trans_sid', 1);
        ini_set('session.cookie_samesite', 'Lax');

        ini_set('session.gc_maxlifetime',  Tht::getConfig('sessionDurationHours') * 3600);
        ini_set('session.cookie_lifetime', self::$SESSION_COOKIE_DURATION);
        ini_set('session.sid_length',      self::$SESSION_ID_LENGTH);
    }

    static function sanitizeInputString($str) {

        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $str[$k] = self::sanitizeInputString($v);
            }
        }
        else if (is_string($str)) {
            $str = str_replace(chr(0), '', $str);  // remove null bytes
            $str = trim($str);
        }

        return $str;
    }

    static function validatePhpFunction($func) {

        $func = strtolower($func);
        $func = preg_replace('/^\\\\/', '', $func);

        if (in_array($func, self::$PHP_BLOCKLIST) || preg_match(self::$PHP_BLOCKLIST_MATCH, $func)) {
            Tht::module('Php')->error("PHP function is blocklisted: `$func`");
        }
    }

    static function validateFilePath ($path, $allowOutsideSandbox=false) {

        if (is_uploaded_file($path)) {
            return $path;
        }

        $path = str_replace('\\', '/', $path);
        $path = preg_replace('#/{2,}#', '/', $path);

        if (strlen($path) > 1) {  $path = rtrim($path, '/');  }

        if (!strlen($path)) {
            Tht::module('File')->error("File path cannot be empty: `$path`");
        }
        else if (v($path)->u_is_url()) {
            Tht::module('File')->error("Remote URL not allowed: `$path`");
        }
        else if (strpos($path, '..') !== false) {
            Tht::module('File')->error("Parent shortcut `..` not allowed in path: `$path`");
        }
        else if (strpos($path, './') !== false) {
            Tht::module('File')->error("Dot directory `.` not allowed in path: `$path`");
        }
        // else if (preg_match('/^[a-zA-Z]:/', $path)) {
        //     Tht::module('File')->error("Drive letter not allowed in path: `$path`. Try: Use Unix filepaths (forward slashes)");
        // }

        if (!$allowOutsideSandbox) {
            $path = self::getSandboxedPath($path);
        }

        return $path;
    }

    // Reject any file name that has evasion patterns in it and
    // make sure the extension is in a whitelist.
    static function validateUploadedFile($file, $allowExtensions) {

        // Don't allow multiple files via []
        if (is_array($file['error'])) {
            u_Input::setUploadError('Duplicate file keys not allowed');
            return fales;
        }

        if ($file['error']) {
            if ($file['error'] == 1) {
                u_Input::setUploadError('Max upload size exceeded.');
            }
            else {
                u_Input::$setUploadError('Upload error: ' . $file['error']);
            }
            return false;
        }

        if (!$file['size']) {
            u_Input::setUploadError('File is empty.');
            return false;
        }


        $name = $file['name'];

        if (strpos($name, '..') !== false) {
            u_Input::setUploadError('Invalid filename');
            return false;
        }
        if (strpos($name, '/') !== false) {
            u_Input::setUploadError('Invalid filename');
            return false;
        }

        // only one extension allowed
        $parts = explode('.', $name);
        if (count($parts) !== 2) {
            u_Input::setUploadError('Invalid filename');
            return false;
        }

        // Check against allowlist of extensions
        $uploadedExt = strtolower($parts[1]);
        $found = false;
        foreach ($allowExtensions as $ext) {
            if ($uploadedExt == $ext) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            u_Input::setUploadError("Unsupported file extension: `$uploadedExt`");
            return false;
        }

        // Validate MIME type
        $actualMime = Tht::module('*File')->u_get_mime_type($file['tmp_name']);
        if (!$actualMime) {
            u_Input::setUploadError('Unknown file type');
            return false;
        }

        // MIME inferred from file extension
        $extMime = Tht::module('*File')->u_extension_to_mime_type($uploadedExt);

        $ok = self::validateUploadedMimeType($actualMime, $extMime);
        if (!$ok) {
            u_Input::setUploadError("File type `$actualMime` does not match file extension `$uploadedExt`.");
            return false;
        }
        else {
            return $uploadedExt;
        }
    }

    static function validateUploadedMimeType($actualMime, $extMime) {

        list($extMimeCat, $x) = explode('/', $extMime);
        list($actualMimeCat, $x) = explode('/', $actualMime);

        if ($extMime == $actualMime) {
            // exact match
            return true;
        }
        else if ($actualMimeCat == 'text') {
            // text is safe
            // allow 'text/plain' for json files, which should be 'application/json'
            return true;
        }
        else if ($actualMimeCat != 'application') {
            // application must be a strict match
            return false;
        }
        else if ($actualMimeCat == $extMimeCat) {
            // Match top-level category (e.g. 'text/html' = 'text')
            // e.g. if we expect an image, we should get an image.
            // This accounts for vagaries in actual mime types.
            return true;
        }

        return false;
    }

    // Make sure path is under data/files
    static function getSandboxedPath($path) {

        if ($path[0] !== '/') {
            return Tht::path('files', $path);
        }

        $sandboxDir = Tht::path('files');
        if (strpos($path, $sandboxDir) !== 0) {
            Tht::module('File')->error("Path must be relative to `data/files`: `$path`");
        }

        return $path;
    }

    static function isCrossSiteRequest() {

        // https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Sec-Fetch-Site
        // Note: Safari currently does not support this.
        $site = Tht::getPhpGlobal('headers', 'sec-fetch-site');

        // Only allow requests from same-site and browser action (address-bar/bookmarks, etc)
        if ($site == 'cross-site') {
            return true;
        }

        return false;
    }

    static function validatePostRequest() {

        if (self::isGetRequest()) {
            return;
        }

        // Make an exception for API calls
        $path = Tht::module('Request')->u_get_url()->u_get_path();
        if (strpos($path, '/api/') === 0) {
            return;
        }

        if (self::isCrossSiteRequest()) {
            Tht::module('Output')->u_send_error(403, 'Cross-Site POST Not Allowed');
        }

        if (!Security::validateCsrfToken()) {
            if (self::isDev()) {
                ErrorHandler::setHelpLink('/manual/module/form/csrf-tag', 'Form.csrfTag');
                Tht::error("Invalid or missing `csrfToken` input field in POST request.");
            }
            else {
                Tht::module('Output')->u_send_error(403, 'Invalid or Missing \'csrfToken\' Field');
            }
        }
    }

    static function isGetRequest() {

        $reqMethod = Tht::module('Request')->u_get_method();

        return ($reqMethod === 'get' || $reqMethod === 'head');
    }

    // All lowercase, no special characters, hyphen separators, no trailing slash
    static function validateRoutePath($path) {

        $pathSize = strlen($path);
        $isTrailingSlash = $pathSize > 1 && $path[$pathSize-1] === '/';

        if ($isTrailingSlash)  {
            Tht::module('Output')->u_send_error(400, 'Page address is not valid.', new HtmlTypeString('Remove trailing slash "/" from path.'));
        }
        else if (preg_match('/[^a-z0-9\-\/\.]/', $path))  {
            Tht::module('Output')->u_send_error(400, 'Page address is not valid.', new HtmlTypeString('Path must be lowercase, numbers, or characters: <code>-./</code>'));
        }
    }

    // Disabled for now.  Might be too restrictive.
    // static function preventDestructiveSql($sql) {
    //     $db = Tht::module('Db');
    //     if (self::isGetRequest()) {
    //         if (preg_match('/\b(update|delete|drop)\b/i', $sql, $m)) {
    //             $db->error("Can't execute a destructive SQL command (`" . $m[1] . "`) in an HTTP GET request (i.e. normal page view).");
    //         }
    //     }
    // }

    // https://stackoverflow.com/questions/549/the-definitive-guide-to-form-based-website-authentication
    // See part VI, on brute force attempts
    static function rateLimitedPasswordCheck($plainTextAttempt, $correctHash) {

        $isCorrectMatch = Security::verifyPassword($plainTextAttempt, $correctHash);

        // Default: 30 failed attempts allowed every 60 minutes
        // See: tht.dev/manual/class/password/check#rate-limiting
        $attemptsAllowedPerHour = Tht::getConfig('passwordAttemptsPerHour');
        if (!$attemptsAllowedPerHour) {
            return $isCorrectMatch;
        }

        // Truncate so the full hash isn't leaked elsewhere,
        // but is still unique enough for this purpose
        $pwHashkey = substr(hash('sha256', $plainTextAttempt), 0, 40);

        $ip = Tht::module('Request')->u_get_ip();

        // Check if this IP has successfully used this password in the past
        $allowKey = 'pwAllow:' . $pwHashkey;
        $allowList = Tht::module('Cache')->u_get($allowKey, []);

        $isInAllowList = in_array($ip, $allowList);

        if (!$isInAllowList) {

            // Track both by IP and individual password to stifle some botnet attempts.
            // This should be checked even if the password is correct (could be brute forced)
            $ipKey = 'pwThrottleIp:' . $ip;
            $pwKey = 'pwThrottlePw:' . $pwHashkey;

            if (self::isOverPasswordRateLimit($ipKey, $attemptsAllowedPerHour)) { return false; }
            if (self::isOverPasswordRateLimit($pwKey, $attemptsAllowedPerHour)) { return false; }
        }

        // Add IP to allowList - 10 days
        if ($isCorrectMatch && !$isInAllowList) {
            $allowList []= $ip;
            Tht::module('Cache')->u_set($allowKey, $allowList, 10 * 24 * 3600);
        }

        return $isCorrectMatch;
    }

    static private function isOverPasswordRateLimit($key, $attemptsAllowedPerHour) {

        $attempts = Tht::module('Cache')->u_get($key, []);
        $nowSecs = floor(microtime(true));

        foreach ($attempts as $tryTime) {
            if ($tryTime > $nowSecs - self::$THROTTLE_PASSWORD_WINDOW_SECS) {
                $recentAttempts []= $tryTime;
            }
        }

        $recentAttempts []= $nowSecs;

        if (count($recentAttempts) > $attemptsAllowedPerHour) {
            return true;
        }

        // Only write if allowed attempt, to prevent flooding cache
        $paddingSecs = 10;
        $cacheTtlSecs = self::$THROTTLE_PASSWORD_WINDOW_SECS + $paddingSecs;
        Tht::module('Cache')->u_set($key, $recentAttempts, $cacheTtlSecs);

        return false;
    }

    public static function isLocalServer() {

        $ip = self::getClientIp();

        return Tht::isMode('testServer') || $ip == '127.0.0.1' || $ip == '::1';
    }

    public static function assertIsOutsideDocRoot($path) {

        $docRoot = Tht::normalizeWinPath(
            Tht::getPhpGlobal('server', 'DOCUMENT_ROOT')
        );
        $inDocRoot = Tht::module('*File')->u_has_root_path($path, $docRoot);

        if ($inDocRoot) {
            self::error("(Security) File `$path` can not be inside the Document Root.");
        }
    }

    static function initResponseHeaders () {

        if (headers_sent($atFile, $atLine)) {
            Tht::startupError('Headers Already Sent');
        }

        // Set response headers
        header_remove('Server');
        header_remove("X-Powered-By");
        header('X-Frame-Options: deny');
        header('X-Content-Type-Options: nosniff');

        // HSTS - 1 year duration
        if (!self::isLocalServer()) {
            header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        }

        $csp = self::getCsp();
        if ($csp != 'xDangerNone') {
            header("Content-Security-Policy: $csp");
        }
    }

    // Content Security Policy (CSP)
    static function getCsp() {

        $csp = Tht::getConfig('contentSecurityPolicy');

        if (!$csp) {
            $nonce = "'nonce-" . Tht::module('Web')->u_nonce() . "'";
            $eval = Tht::getConfig('xDangerAllowJsEval') ? '\'unsafe-eval\'' : '';

            // Yuck, apparently nonces don't work on iframes (https://github.com/w3c/webappsec-csp/issues/116)
            // TODO: make this a config param for whitelist
            $frame = "*";

            $csp = "default-src 'self'; script-src 'strict-dynamic' $eval $nonce; style-src 'unsafe-inline' *; img-src data: *; media-src data: *; font-src *; frame-src $frame";
        }

        return $csp;
    }

    // Set PHP ini
    static function initPhpIni () {

        // locale
        date_default_timezone_set(Tht::getCOnfig('timezone'));

        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        // logging
        error_reporting(E_ALL & ~E_DEPRECATED);
        ini_set('display_errors', (Tht::isMode('cli') || Tht::getConfig('_coreDevMode')) ? '1' : '0');
        ini_set('display_startup_errors', '1');
        ini_set('log_errors', '0');  // assume we are logging all errors manually

        // file security
        ini_set('allow_url_fopen', '0');
        ini_set('allow_url_include', '0');

        // limits
        ini_set('max_execution_time', Tht::isMode('cli') ? 0 : intval(Tht::getConfig('maxExecutionTimeSecs')));
        ini_set('max_input_time', intval(Tht::getConfig('maxInputTimeSecs')));
        ini_set('memory_limit', intval(Tht::getConfig('memoryLimitMb')) . "M");
    }

    // Register an un-sandboxed version of File, for internal use.
    static function registerInternalFileModule() {

        $f = new u_File ();
        $f->xDangerDisableSandbox();
        ModuleManager::registerStdModule('*File', $f);
    }

    // https://www.owasp.org/index.php/XSS_Filter_Evasion_Cheat_Sheet
    static function validateUserUrl($sUrl) {

        $sUrl = trim($sUrl);
        $url = self::parseUrl($sUrl);
        $sUrl = urldecode($sUrl);

        if (!preg_match('!^https?\://!i', $sUrl)) {
            // must be absolute
            return false;
        }
        else if (preg_match('!\.\.!', $sUrl)) {
            // No parent '..' patterns
            return false;
        }
        else if (preg_match('![\'\"\0\s<>\\\\]!', $sUrl)) {
            // Illegal characters
            return false;
        }
        else if (strpos('&#', $sUrl) !== false) {
            // No HTML escapes allowed
            return false;
        }
        else if (strpos('%', $url['host']) !== false) {
            // No escapes allowed in host
            return false;
        }
        else if (preg_match('!^[0-9\.fxFX]+$!', $url['host'])) {
            // Can not be IP address
            return false;
        }
        else if (preg_match('!\.(loan|work|click|gdn|date|men|gq|world|life|bid)!i', $url['host'])) {
            // High spam TLD
            // https://www.spamhaus.org/statistics/tlds/
            return false;
        }
        else if (preg_match('!\.(zip|doc|xls|pdf|7z)!i', $sUrl)) {
            // High-risk file extension
            return false;
        }

        return true;
    }

    static function sanitizeHtmlPlaceholder($in) {

        $alpha = preg_replace('/[^a-z:]/', '', strtolower($in));

        if (strpos($alpha, 'javascript:') !== false) {
            $in = '(REMOVED:UNSAFE_URL)';
        }

        return $in;
    }

    static function escapeHtml($in, $options = '') {

        if (OTypeString::isa($in)) {
            $type = $in->u_string_type();
            $in = $in->u_render_string();
            if ($type == 'html') {
                return $in;
            }
        }

        if ($options == 'removeTags') {
            $in = self::removeHtmlTags($in);
        }

        return htmlspecialchars($in, ENT_QUOTES|ENT_HTML5, 'UTF-8');
    }

    // Only remove complete tags.  Assume standalone `<` and `>` will be escaped.
    static function removeHtmlTags($in) {

         return preg_replace('/<.*?>/', '', $in);
    }

    static function unescapeHtml($in) {

         return htmlspecialchars_decode($in, ENT_QUOTES|ENT_HTML5);
    }

    static function sanitizeUrlHash($hash) {

        $hash = preg_replace('![^a-z0-9\-]!', '-', strtolower($hash));
        $hash = rtrim($hash, '-');

        return $hash;
    }

    static function parseUrl($url) {

        // Remove user
        // https://www.cvedetails.com/cve/CVE-2016-10397/
        // $url = preg_replace('!(\/+).*@!', '$1', $url);
        // if (strpos($url, '@') !== false) {
        //     $url = preg_replace('!.*@!', '', $url);
        // }

        $url = preg_replace('!\s+!', '', $url);

        preg_match('!^(.*?)#(.*)$!', $url, $m);
        if (isset($m[2])) {
            $url = $m[1] . '#' . self::sanitizeUrlHash($m[2]);
        }

        $u = parse_url($url);

        unset($u['user']);

        $fullUrl = rtrim($url, '/');
        $u['full'] = $fullUrl;

        $relativeUrl = preg_replace('#^.*?//.*?/#', '/', $fullUrl);
        $u['relative'] = $relativeUrl;

        if (!isset($u['path'])) {
            $u['path'] = '';
        }

        // path parts
        if ($u['path'] === '' || $u['path'] === '/') {
            $u['pathParts'] = OList::create([]);
            $u['page'] = '';
        }
        else {
            $pathParts = explode('/', trim($u['path'], '/'));
            $u['pathParts'] = OList::create($pathParts);
            $u['page'] = end($pathParts);
        }

        // port
        if (!isset($u['port'])) {

            if (isset($u['scheme'])) {

                if ($u['scheme'] == 'http') {
                    $u['port'] = 80;
                }
                else if ($u['scheme'] == 'https') {
                    $u['port'] = 443;
                }
                else {
                    $u['port'] = 0;
                }
            }
            else {
                $u['port'] = 80;
            }
        }

        if (isset($u['fragment']) && $u['fragment']) {
            $u['hash'] = $u['fragment'];
            unset($u['fragment']);
        }
        else {
            $u['hash'] = '';
        }

        // remove hash & query
        $u['full'] = preg_replace('!#.*!', '', $u['full']);
        $u['full'] = preg_replace('!\?.*!', '', $u['full']);

        // without the query & hash, this is effectively same as path
        unset($u['relative']);

        return $u;
    }

    // PHP behavior:
    // ?foo=1&foo=2        -->  foo=2
    // ?foo[]=1&foo[]=2    -->  foo=[1,2]
    // ?foo[a]=1&foo[b]=2  -->  foo={a:1, b:2}
    static function stringifyQuery($queryMap) {

        $q = http_build_query(unv($queryMap), '', '&', PHP_QUERY_RFC3986);
        $q = str_replace('%5B', '[', $q);
        $q = str_replace('%5D', ']', $q);
        $q = preg_replace('!\[([0-9]+)\]!', '[]', $q);

        if ($q) {
            return '?' . $q;
        }
        else {
            return '';
        }
    }
}



