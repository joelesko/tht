<?php

namespace o;

// Sensitive operations in one place, for easier auditing

class Security {

	static private $CSP_NONCE = '';
	static private $CSRF_TOKEN_LENGTH = 64;
	static private $NONCE_LENGTH = 40;

	static private $SESSION_ID_LENGTH = 48;
    static private $SESSION_COOKIE_DURATION = 0;  // until browser is closed

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

    static private $PHP_BLACKLIST_MATCH = '/pcntl_|posix_|proc_|ini_/i';



    /// METHODS


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

	static function getSessionChecksum() {
		$plainKey = Tht::getPhpGlobal('server', 'REMOTE_ADDR') . '::' . Tht::getPhpGlobal('server', 'HTTP_USER_AGENT');
		return md5($plainKey);
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



