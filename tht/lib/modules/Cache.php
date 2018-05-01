<?php

namespace o;

class u_Cache extends StdModule {

    private $driver;

    function __construct() {
    	// TODO: support memcache, etc.
        $this->driver = new FileCacheDriver ();
    }

    function u_has($k) {
    	Tht::module('Perf')->start('Cache.has', $k);
        $v = $this->driver->has($k);
        Tht::module('Perf')->u_stop();
        return $v;
    }

    function u_get($k, $default='') {

    	Tht::module('Perf')->start('Cache.get', $k);
        $v = $this->driver->get($k, -1, $default);
        Tht::module('Perf')->u_stop();

        return $v;
    }

    function u_get_sync($k, $syncFileTime, $default='') {

    	Tht::module('Perf')->start('Cache.getSync', $k);
        $v = $this->driver->get($k, $syncFileTime, $default);
        Tht::module('Perf')->u_stop();
        
        return $v;
    }

    function u_set($k, $v, $ttlSecs) {
        return $this->driver->set($k, $v, $ttlSecs);
    }

    function u_delete($k) {
    	$this->driver->clearPrev($k);
        return $this->driver->delete($k);
    }

    function u_counter($k, $delta=1) {
        $num = $this->driver->counter($k, $delta);
        $this->driver->setPrev($k, $num);
        return $num;
    }
}

class CacheDriver {

    private $prevKey = '';
    private $prevValue = '';


	// most recent key/value
    function setPrev($k, $v) {
    	$this->prevKey = $k;
    	$this->prevValue = $v;
    }

    function clearPrev($k) {
    	if ($k === $this->prevKey) {
    		$this->prevKey = '';
    		$this->prevValue = '';
    	}	
    }

    function getPrev($k) {
    	if ($this->prevKey === $k) {
    		return $this->prevValue;
    	} else {
    		return null;
    	}
    }

	function has($k) {
		return !is_null($this->get($k, -1, null));
	}

	function get($k, $syncFileTime, $default) {
		
		$prevValue = $this->getPrev($k);
    	if (!is_null($prevValue)) { return $prevValue; }


		$json = $this->fetch($k);
		
		$v = $default;

		if (!is_null($json)) {

			$r = unserialize($json);

			$v = $r['v'];

			if (!isset($r['v']) || !isset($r['ttl']) || !isset($r['c'])) {
				$v = $default;
			}
	        else if ($syncFileTime >= 0) {
	        	// synced file was updated
	        	if ($syncFileTime > $r['c']) {
	        		$v = $default;
	        	}
	        } 
	        else {
	        	// check standard expiry
	        	$expiry = $r['c'] + $r['ttl'];
		        if (Tht::module('Date')->u_now(true) > $expiry && $r['ttl'] > 0) {
		        	$v = $default;
		        }  
	        }
		}

		if (!is_null($v)) {  
			$this->setPrev($k, $v);
		}

        return $v;
	}

    function wrap($v, $ttlSecs) {
    	$record = [
    		'v' => $v, 
    		'c' => Tht::module('Date')->u_now(true), 
    		'ttl' => ceil($ttlSecs * 1000)
    	];
        return serialize($record);
    }

    function normalKey($k) {
        return preg_replace('/[^a-zA-Z0-9]/', '_', trim($k));
    }

    function counter($k, $delta) {
        $v = $this->get($k, -1, 0);
        $v += $delta;
        if ($v < 0) { $v = 0; }
        $this->set($k, $v, time() + (60 * 60 * 24 * 30));  // 30 days
        return $v;
    }
}


class FileCacheDriver extends CacheDriver {

    function path($k) {
        return Tht::path('kvCache', $this->normalKey($k) . '.txt');
    }

    function fetch($k) {
    	 $path = $this->path($k);
    	 if (file_exists($path)) {
            return file_get_contents($path);
         } else {
         	return null;
         }
    }

    function set($k, $v, $ttlSecs) {
        $path = $this->path($k);
        file_put_contents($path, $this->wrap($v, $ttlSecs));
        //touch($path, $ex);
    }

    function delete($k) {
        $path = $this->path($k);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}
