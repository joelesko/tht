<?php

namespace o;

class u_Cache extends StdModule {

    private $driver;

    function __construct() {
        $this->driver = new FileCacheDriver ();
    }

    // TODO: support memcache, etc.

    // function u_set_driver($d) {
    //     if ($d == 'file') {
    //         $this->driver = new FileCacheDriver ();
    //     } else if ($d == 'db') {
    //         $this->driver = new DbCacheDriver ();
    //     }
    // }


    function u_has($k) {
    	Tht::module('Perf')->start('Cache.has', $k);
        $v = $this->driver->has($k);
        Tht::module('Perf')->u_stop();
        return $v;
    }

    function u_get($k) {

    	Tht::module('Perf')->start('Cache.get', $k);
        $v = $this->driver->get($k, -1);
        Tht::module('Perf')->u_stop();

        return $v;
    }

    function u_get_sync($k, $syncFileTime) {

    	Tht::module('Perf')->start('Cache.getSync', $k);
        $v = $this->driver->get($k, $syncFileTime);
        Tht::module('Perf')->u_stop();
        
        return $v;
    }

    function u_set($k, $v, $ttlSecs) {
        return $this->driver->set($k, $v, $ttlSecs);
    }

    function u_delete($k) {
    	$this->driver->setPrev($k, '');
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

    function getPrev($k) {
    	if ($this->prevKey === $k) {
    		return $this->prevValue;
    	} else {
    		return null;
    	}
    }

	function has($k) {
		return $this->get($k, -1) !== '';
	}

	function get($k, $syncFileTime) {
		
		$prevValue = $this->getPrev($k);
    	if (!is_null($prevValue)) { return $prevValue; }


		$json = $this->fetch($k);
		
		$v = '';

		if (!is_null($json)) {

			$r = unserialize($json);

			$v = $r['v'];

			if (!isset($r['v']) || !isset($r['ttl']) || !isset($r['c'])) {
				$v = '';
			}
	        else if ($syncFileTime >= 0) {
	        	// synced file was updated
	        	if ($syncFileTime > $r['c']) {
	        		$v = '';
	        	}
	        } 
	        else {
	        	// check standard expiry
	        	$expiry = $r['c'] + $r['ttl'];
		        if (time() > $expiry) {
		        	$v = '';
		        }  
	        }
		}

		$this->setPrev($k, $v);

        return $v;
	}

    function wrap($v, $ttlMins) {
    	$record = [
    		'v' => $v, 
    		'c' => time(), 
    		'ttl' => $ttlMins
    	];
        return serialize($record);
    }

    function normalKey($k) {
        return preg_replace('/[^a-zA-Z0-9]/', '_', trim($k));
    }

    function counter($k, $delta) {
        $v = $this->get($k, 0);
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

    function set($k, $v, $ttlMins) {
        $path = $this->path($k);
        file_put_contents($path, $this->wrap($v, $ttlMins));
        //touch($path, $ex);
    }

    function delete($k) {
        $path = $this->path($k);
        if (file_exists($path)) {
            unlink($path);
        }
    }
}

// About 4x slower than File Driver...
// class DbCacheDriver extends CacheDriver {

//     function db() {
//         return Tht::module('Database')->u_use('cache');
//     }

//     function get($k, $def='', $expiry=-1) {
//         $db = $this->db();
//         $lsql = new \o\OLockString('SELECT * FROM cache WHERE key = {0} AND expireDate > {1}');
//         $lsql->u_fill($k, time());
//         $row = $db->u_select_row($lsql);
//         return $row ? $this->unwrap($row['value']) : $def;
//     }

//     function set($k, $v, $ex) {
//         $db = $this->db();
//         $lWhere = new \o\OLockString('key = {0}');
//         $db->u_delete_row('cache', $lWhere->u_fill($k));
//         $db->u_insert_row('cache', [
//             'key' => $k,
//             'value' => $this->wrap($v),
//             'expireDate' => $ex
//         ]);
//     }

//     function delete() {

//     }

// }

