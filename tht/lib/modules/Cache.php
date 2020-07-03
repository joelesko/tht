<?php

namespace o;

class u_Cache extends OStdModule {

    private $driver;
    private $triedGarbageCollect = false;

    function __construct() {
        // TODO: support memcache, etc.
        $this->driver = new FileCacheDriver ();
    }

    function tryGarbageCollect() {
        if (!$this->triedGarbageCollect) {
            if (rand(1, Tht::getConfig('cacheGarbageCollectRate')) == 1) {
                $this->driver->garbageCollect();
            }
            $this->triedGarbageCollect = true;
        }
    }

    function cleanKey($k) {
        $k = preg_replace('/[^a-zA-Z0-9]/', '_', trim(strtolower($k)));
        $k = Tht::getThtPhpVersionToken() . '_' . $k;
        return $k;
    }

    function u_has($k) {
        Tht::module('Perf')->start('Cache.has', $k);
        $k = $this->cleanKey($k);
        $v = $this->driver->has($k);
        Tht::module('Perf')->u_stop();
        return $v;
    }

    function u_get($origKey, $default='', $ttlSecs=3600) {
        Tht::module('Perf')->start('Cache.get', $origKey);
        $k = $this->cleanKey($origKey);
        $v = $this->driver->get($k, -1, '');

        if ($v === '') {
            if (is_callable($default)) {
                $v = $default();
                if ($v instanceof ONothing) {
                    $this->error('Default function must return a value.');
                }
                $this->u_set($origKey, $v, $ttlSecs);
            }
            else {
                $v = $default;
            }
        }

        Tht::module('Perf')->u_stop();
        return $v;
    }

    function u_get_sync($k, $syncFileTime, $default='') {
        Tht::module('Perf')->start('Cache.getSync', $k);
        $k = $this->cleanKey($k);
        $v = $this->driver->get($k, $syncFileTime, $default);
        Tht::module('Perf')->u_stop();
        return $v;
    }

    function u_set($k, $v, $ttlSecs) {
        $k = $this->cleanKey($k);
        $this->tryGarbageCollect();
        return $this->driver->set($k, $v, $ttlSecs);
    }

    function u_delete($k) {
        $k = $this->cleanKey($k);
        return $this->driver->delete($k);
    }

    function u_counter($k, $delta=1) {
        $k = $this->cleanKey($k);
        $num = $this->driver->counter($k, $delta);
        return $num;
    }
}

class CacheDriver {

    function has($k) {
        // TODO: optimize away redundant get()
        return !is_null($this->get($k, -1, null));
    }

    function get($k, $syncFileTime, $default) {

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
                if ($syncFileTime > ceil($r['c'] / 1000)) {
                    $v = $default;
                }
            }
            else {
                // check standard expiry
                $expiry = $r['c'] + $r['ttl'];
                if ($r['ttl'] > 0 && Tht::module('Date')->u_now(true) > $expiry) {
                    $v = $default;
                }
            }
        }

        return $v;
    }

    function serialize($v, $ttlSecs) {
        $record = [
            'v' => $v,
            'c' => Tht::module('Date')->u_now(true),
            'ttl' => ceil($ttlSecs * 1000)
        ];
        return serialize($record);
    }

    // TODO: figure out how to cleanly support user TTL
    function counter($k, $delta) {
        $v = $this->get($k, -1, 0);
        $v += $delta;
        if ($v < 0) { $v = 0; }
        $ttlSecs = (60 * 60 * 24 * 30); // 30 days
        $this->set($k, $v, $ttlSecs);
        return $v;
    }

    function garbageCollect() {}
}

class FileCacheDriver extends CacheDriver {

    function path($k) {
        return Tht::path('kvCache', $k . '.txt');
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
        $path = $this->path($k, $ttlSecs);
        file_put_contents($path, $this->serialize($v, $ttlSecs));

        $gcTtl = $ttlSecs > 0 ? $ttlSecs : Tht::module('Date')->u_days(30);

        touch($path, time() + $gcTtl);
    }

    function delete($k) {
        $path = $this->path($k);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    // The GC only looks at the modtime of the file, which is forced to the expiry date.
    function garbageCollect() {
        $now = time();
        $numDeleted = 0;
        Tht::module('*File')->u_for_files(Tht::path('kvCache'), function($f) use ($now, $numDeleted){
            if (filemtime($f['fullPath']) <= $now) {
                unlink($f['fullPath']);
                $numDeleted += 1;
                if ($numDeleted == 100) {
                    return true;
                }
            }
        });
        return $numDeleted;
    }
}
