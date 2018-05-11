<?php

namespace o;

// Sensitive operations in one place, for easier auditing

class Security {

	static private $CSP_NONCE = '';
	static private $CSRF_TOKEN_LENGTH = 64;
	static private $NONCE_LENGTH = 40;

	static private $SESSION_ID_LENGTH = 48;
    static private $SESSION_COOKIE_DURATION = 0;  // until browser is closed

    static private $isCrossOrigin = null;
    static private $isOpenFileSandbox = false;

    static private $PHP_BLACKLIST_MATCH = '/pcntl_|posix_|proc_|ini_/i';

    static private $PHP_BLACKLIST = [
        'assert',
        'call_user_func',
        'call_user_func_array',
        'create_function',
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

    

	static function createPassword ($plainText) {
		return new OPassword ($plainText);
	}

	static function getCsrfToken() {
		$token = Tht::module('Session')->u_get('csrfToken', '');
        if (empty($token)) {
            $token = Tht::module('String')->u_random(self::$CSRF_TOKEN_LENGTH);
            Tht::module('Session')->u_set('csrfToken', $token); 
        }

        return $token;
	}

	static function getNonce() {
		if (!self::$CSP_NONCE) {
            self::$CSP_NONCE = self::randomString(self::$NONCE_LENGTH);
        }

        return self::$CSP_NONCE;
	}

	// Length = final string length, not byte length
	static function randomString($len) {
		    
        $bytes = '';
        
        if (function_exists('random_bytes')) {
            $bytes = random_bytes($len);
        } else if (function_exists('mcrypt_create_iv')) {
            $bytes = mcrypt_create_iv($len, MCRYPT_DEV_URANDOM);
        } else {
            $bytes = openssl_random_pseudo_bytes($len);
        }
        
        $b64 = base64_encode($bytes);

        return substr($b64, 0, $len);
	}

	static function initSessionParams() {

		ini_set('session.use_only_cookies', 1);
        ini_set('session.use_strict_mode', 0);
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_trans_sid', 1);

        ini_set('session.gc_maxlifetime',  Tht::getConfig('sessionDurationMins') * 60);
        ini_set('session.cookie_lifetime', self::$SESSION_COOKIE_DURATION);
        ini_set('session.sid_length',      self::$SESSION_ID_LENGTH);
	}

	static function sanitizeString($str) {
        if (is_array($str)) {
            foreach ($str as $k => $v) {
                $str[$k] = self::sanitizeString($v);
            }
        } else if (is_string($str)) {
            $str = str_replace(chr(0), '', $str);  // remove null bytes
            $str = trim($str);
        }
        return $str;
	}

	static function validateCsrfToken() {

		$localCsrfToken = Tht::module('Session')->u_get('csrfToken', '');
        
        $post = Tht::data('phpGlobals', 'post');
        $remoteCsrfToken = isset($post['csrfToken']) ? $post['csrfToken'] : '';

        if ($localCsrfToken && hash_equals($localCsrfToken, $remoteCsrfToken)) {

            // client (human) took longer than 2 seconds to submit
            $formLoadTime = Tht::module('Session')->u_get('formLoadTime', 0);
            if (time() >= $formLoadTime + 2) {
                return true;
            }
        }
        return false;
	}

	static function validatePhpFunction($func) {
		if (in_array(strtolower($func), self::$PHP_BLACKLIST) || preg_match(self::$PHP_BLACKLIST_MATCH, $func)) {
            Tht::error("PHP function is blacklisted: `$func`");
        }
	}

    static function validatePath ($path, $checkSandbox=true) {

        $path = str_replace('\\', '/', $path);
        if (strlen($path) > 1) {  $path = rtrim($path, '/');  }

        if (!strlen($path)) {
            Tht::error("File path cannot be empty: `$path`");
        }
        if (v('' . $path)->u_is_url()) {
            Tht::error("Remote URL not allowed: `$path`");
        }
		if (strpos($path, '..') !== false) {
            Tht::error("Parent shortcut `..` not allowed in path: `$path`");
        }

        if ($checkSandbox && Tht::isMode('fileSandbox')) {
            $path = self::getSandboxedPath($path);
        }

        return $path;
    }

    // Make sure path is under data/files
    static function getSandboxedPath($path) {

        if ($path[0] !== '/') {
            return Tht::path('files', $path);
        } 
        else {
            $sandboxDir = Tht::path('files');
            if (strpos($path, $sandboxDir) !== 0) {
                Tht::error("Path must be relative to `data/files`: `$path`");
            }
            return $path;
        }
    }

    static function isCrossOrigin () {  

        if (!is_null(self::$isCrossOrigin)) {
            return self::$isCrossOrigin;
        }

        $web = Tht::module('Web');

        if ($web->u_request()['method'] !== 'get') {
           $host  = $web->u_request_header('host');
           $origin = $web->u_request_header('origin');
           $origin = preg_replace('/^https?:\/\//i', '', $origin);
           if (!$origin) {
               $referrer = $web->u_request_header('referrer');

               if (strpos($referrer, $host)) {
                   self::$isCrossOrigin = false;
               } else {
                   self::$isCrossOrigin = true;
               }
           }
           else if ($origin !== $host) {
               self::$isCrossOrigin = true;
           }
        }

        return self::$isCrossOrigin;
    }

    static function initResponseHeaders () {

        // Set response headers
        header_remove('Server');
        header_remove("X-Powered-By");
        header('X-Frame-Options: deny');
        header('X-Content-Type-Options: nosniff');
        header("X-UA-Compatible: IE=Edge");

        // Content Security Policy (CSP)
        $csp = Tht::getConfig('contentSecurityPolicy');
        if (!$csp) {
            $nonce = "'nonce-" . Tht::module('Web')->u_nonce() . "'";
            $eval = Tht::getConfig('dangerDangerAllowJsEval') ? 'unsafe-eval' : '';
            $scriptSrc = "script-src $eval $nonce";
            $csp = "default-src 'self' $nonce; style-src 'unsafe-inline' *; img-src data: *; media-src *; font-src *; " . $scriptSrc;
        }
        header("Content-Security-Policy: $csp");
    }

    // set PHP ini
    static function initPhpIni () {

        // locale
        date_default_timezone_set(Tht::getConfig('timezone'));
        ini_set('default_charset', 'utf-8');
        mb_internal_encoding('utf-8');

        // logging
        error_reporting(E_ALL);
        ini_set('display_errors', Tht::isMode('cli') ? '1' : (Tht::getConfig('_phpErrors') ? '1' : '0'));
        ini_set('display_startup_errors', '1');
        ini_set('log_errors', '0');  // assume we are logging all errors manually

        // file security
        ini_set('allow_url_fopen', '0');
        ini_set('allow_url_include', '0');

        // limits
        ini_set('max_execution_time', Tht::isMode('cli') ? 0 : intval(Tht::getConfig('maxExecutionTimeSecs')));
        ini_set('max_input_time', intval(Tht::getConfig('maxInputTimeSecs')));
        ini_set('memory_limit', intval(Tht::getConfig('memoryLimitMb')) . "M");


        // Configs that are only set in .ini or .htaccess
        // Trigger an error if PHP is more strict than Tht.
        $thtMaxPostSize = intval(Tht::getConfig('maxPostSizeMb'));
        $phpMaxFileSize = intval(ini_get('upload_max_filesize'));
        $phpMaxPostSize = intval(ini_get('post_max_size'));
        $thtFileUploads = Tht::getConfig('allowFileUploads');
        $phpFileUploads = ini_get('file_uploads');

        if ($thtMaxPostSize > $phpMaxFileSize) {
            Tht::configError("Config `maxPostSizeMb` ($thtMaxPostSize) is larger than php.ini `upload_max_filesize` ($phpMaxFileSize).\n"
                . "You will want to edit php.ini so they match.");
        }
        if ($thtMaxPostSize > $phpMaxPostSize) {
            Tht::configError("Config `maxPostSizeMb` ($thtMaxPostSize) is larger than php.ini `post_max_size` ($phpMaxPostSize).\n"
                . "You will want to edit php.ini so they match.");
        }
        if ($thtFileUploads && !$phpFileUploads) {
            Tht::configError("Config `allowFileUploads` is true, but php.ini `file_uploads` is false.\n"
                . "You will want to edit php.ini so they match.");
        }
    }

    // Register an un-sandboxed version of File, for internal use.
    static function registerInternalFileModule() {
    	self::$isOpenFileSandbox = true;
    	Runtime::registerStdModule('*File', new u_File ());  
    	self::$isOpenFileSandbox = false;
    }

    static function isOpenFileSandbox() {
    	return self::$isOpenFileSandbox;
    }

}

// Wrapper for incoming passwords to prevent leakage of plaintext
class OPassword {

	private $plainText = '';
	private $hash = '';

 	function __construct ($plainText) {
 		$this->plainText = $plainText;
 	}

 	function __toString() {
 		return '[Password]';
 	}

 	function hash() {
 		if (!$this->hash) {
 			$this->hash = password_hash($this->plainText, PASSWORD_DEFAULT);
 		}
 		return $this->hash;
 	}

 	function u_is_correct($otherPasswordHash) {
 		return password_verify($this->plainText, $otherPasswordHash);
 	}
}



