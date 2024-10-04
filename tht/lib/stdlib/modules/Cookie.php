<?php

namespace o;

class u_Cookie extends OStdModule {

    private $localCache = [];

    function u_set($key, $value) {

        $this->ARGS('ss', func_get_args());

        if (preg_match('/[^a-zA-Z0-9]/', $value)) {
            $this->error('Cookie value may only contain alphanumeric characters (a-zA-Z0-9).');
        }

        $this->validateKey($key);

        $options = [
            'expires' => time() + 30 * 24 * 3600,  // 30 days
            'path' => '/',
            'domain' => '',
            'secure' => Security::isDev() ? false : true,
            'httponly' => true,
            'samesite' => 'Lax',
        ];

        setcookie($key, $value, $options);

        $this->localCache[$key] = $value;

        return NULL_NORETURN;
    }

    function u_get($key) {

        $this->ARGS('s', func_get_args());

        $this->validateKey($key);

        if (isset($this->localCache[$key])) {
            return $this->localCache[$key];
        }

        return Tht::getPhpGlobal('cookie', $key, '');
    }

    function u_delete($key) {

        $this->ARGS('s', func_get_args());

        $this->validateKey($key);

        $options = [
            'expires' => -3600,
            'path' => '/',
            'domain' => '',
        ];

        setcookie($key, '', $options);

        unset($this->localCache[$key]);

        return NULL_NORETURN;
    }

    function validateKey($key) {

        if (preg_match('/[^a-zA-Z0-9]/', $key)) {
            $this->error("Cookie key may only contain alphanumeric characters (a-zA-Z0-9).  Got: `$key`");
        }

        if (strlen($key) > 40) {
            $this->error("Cookie key length must be 40 characters or less.  Got: `$key`");
        }

        $sessionKey = Tht::module('Session')->sessionIdName;
        if ($key == $sessionKey) {
            $this->error("Read/write to Session cookie `$sessionKey` is restricted.");
        }
    }

}


