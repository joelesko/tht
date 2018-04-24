<?php

namespace o;

class u_Session extends StdModule {

    private $sessionStarted = false;
    private $checksumKey = '**checksum';
    private $flashKey = '**flash';
    private $flashData = [];
    private $sessionIdName = 'sid';



    public function startSession() {
        if ($this->sessionStarted) {
            return;
        }
        $this->sessionStarted = true;

        if (headers_sent()) {
            Tht::error('Session can not be started after page content is sent.');
        }

        Tht::module('Perf')->u_start('Session.start');

        Security::initSessionParams();

        session_save_path(Tht::path('sessions'));

        session_name($this->sessionIdName);
        session_start();


        $checksum = Security::getSessionChecksum();

        if (isset($_SESSION[$this->checksumKey])) {
            if ($checksum !== $_SESSION[$this->checksumKey]) {

                // force a new session
                session_write_close();
                session_id(session_create_id());
                session_start();
                $_SESSION[$this->checksumKey] = $checksum;
            }
            // Refresh cookie to extend expiry
            // else if ($this->cookieDuration > 0) {
            //     setcookie($this->sessionIdName, session_id(), time() + $this->cookieDuration, '/', 'localhost', false, true);
            // }
        }
        else {
            $_SESSION[$this->checksumKey] = $checksum;
        }

        if (isset($_SESSION[$this->flashKey])) {
            $this->flashData = $_SESSION[$this->flashKey];
            unset($_SESSION[$this->flashKey]);
        }

        Tht::module('Perf')->u_stop();
    }

    function u_set($keyOrMap, $value=null) {
        
        $this->startSession();

        if (is_string($keyOrMap)) {
            if (is_null($value)) {
                Tht::error('Session.set() missing 2nd `value` argument.');
            }
            $_SESSION[$keyOrMap] = $value;
        }
        else {
            foreach($keyOrMap as $k => $v) {
                $_SESSION[$k] = $v;
            }   
        }
    }

    function u_get($key, $default=null) {
        $this->startSession();
        if (!isset($_SESSION[$key])) {
            if (is_null($default)) {
                Tht::error('Unknown session key: `' . $key . '`');
            } 
            return $default;
        }
        else {
            return $_SESSION[$key];
        }
    }

    function u_get_all() {
        $this->startSession();
        $all = $_SESSION;
        unset($all[$this->checksumKey]);
        unset($all[$this->flashKey]);
        return OMap::create($all);
    }

    function u_delete($key) {
        $this->startSession();
        if (isset($_SESSION[$key])) {
            $val = $_SESSION[$key];
            unset($_SESSION[$key]);
            return $val;
        }
        return '';
    }

    function u_delete_all() {
        $this->startSession();
        $checksum = $_SESSION[$this->checksumKey];
        $_SESSION = [];
        $_SESSION[$this->checksumKey] = $checksum;
    }

    function u_has_key($key) {
        $this->startSession();
        return isset($_SESSION[$key]);
    }

    function u_add_counter($key) {
        $this->startSession();
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = 0;
        }
        $_SESSION[$key] += 1;

        return $_SESSION[$key];
    }

    function u_add_to_list($key, $value) {
        $this->startSession();
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [];
        }
        $_SESSION[$key] []= $value;
    }

    function u_get_flash($key, $default='') {
        $this->startSession();
        if (isset($this->flashData[$key])) {
            return $this->flashData[$key];
        } 
        return $default;
    }

    function u_set_flash($key, $value) {
        $this->startSession();
        if (!isset($_SESSION[$this->flashKey])) {
            $_SESSION[$this->flashKey] = [];
        }
        $_SESSION[$this->flashKey][$key] = $value;
    }

    function u_has_flash($key) {
        $this->startSession();
        return isset($_SESSION[$this->flashKey]);
    }

    function u_repeat_flash() {
        $this->startSession();
        $_SESSION[$this->flashKey] = $this->flashData;
    }
}


