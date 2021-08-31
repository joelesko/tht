<?php

// TODO: Add Cache.useDriver, like Db.use?


namespace o;

class u_Cache extends OStdModule {

    private $driver;
    private $triedGarbageCollect = false;

    // Values already retrieved during this request
    private $localCache = [];

    function __construct() {

        // TODO: support memcache, redis, etc.

        if ($this->u_get_driver() == 'apcu') {
            $this->driver = new ApcuCacheDriver();
        }
        else {
            $this->driver = new FileCacheDriver();
        }
    }

    function u_get_driver() {

        $this->ARGS('', func_get_args());

     //   return 'file';

        if (extension_loaded('apcu') && apcu_enabled()) {
            return 'apcu';
        }
        else {
            return 'file';
        }
    }

    // Undocumented
    function u_force_file_driver() {
        $this->driver = new FileCacheDriver();
    }

    // Undocumented
    function u_clear_local_cache() {
        $this->localCache = [];
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

    function u_has($origKey) {

        $this->ARGS('s', func_get_args());

        $k = $this->cleanKey($origKey);

        $lcKey = '__has:' . $k;
        if (isset($this->localCache[$lcKey])) {
            return $this->localCache[$lcKey];
        }

        Tht::module('Perf')->start('Cache.has', $origKey);

        $v = $this->driver->has($k);

        $this->localCache[$lcKey] = $v;

        Tht::module('Perf')->u_stop();

        return $v;
    }

    function u_get($origKey, $default='', $ttl='1 hour') {

        $this->ARGS('s*s', func_get_args());

        $k = $this->cleanKey($origKey);

        if (isset($this->localCache[$k])) {
            return $this->localCache[$k];
        }

        Tht::module('Perf')->start('Cache.get', $origKey);

        $v = $this->driver->get($k, -1, '');

        $ttlSecs = Tht::module('Date')->u_duration_to_secs($ttl);

        if ($ttlSecs <= 0) {
            $v = '';
        }

        if ($v === '') {
            if (is_callable($default)) {
                $v = $default();
                if ($v instanceof ONothing) {
                    $this->error('Default function must return a value.');
                }
                if ($ttlSecs >= 0) {
                    $this->u_set($origKey, $v, $ttlSecs);
                }
            }
            else {
                $v = $default;
            }
        }

        Tht::module('Perf')->u_stop();

        $this->localCache[$k] = $v;

        return $v;
    }

    function u_get_sync($origKey, $syncFileTime, $default='') {

        $this->ARGS('si*', func_get_args());

        $k = $this->cleanKey($origKey);

        if (isset($this->localCache[$k])) {
            return $this->localCache[$k];
        }

        Tht::module('Perf')->start('Cache.getSync', $origKey);

        $v = $this->driver->get($k, $syncFileTime, $default);

        Tht::module('Perf')->u_stop();

        $this->localCache[$k] = $v;

        return $v;
    }

    function u_set($origKey, $v, $ttl) {

        $this->ARGS('s*s', func_get_args());

        $k = $this->cleanKey($origKey);
        $ttlSecs = Tht::module('Date')->u_duration_to_secs($ttl);

        Tht::module('Perf')->start('Cache.set', $origKey);

        $this->tryGarbageCollect();
        $this->driver->set($k, $v, $ttlSecs);

        $this->localCache[$k] = $v;

        Tht::module('Perf')->u_stop();

        return $v;
    }

    function u_delete($origKey) {

        $this->ARGS('s', func_get_args());

        $k = $this->cleanKey($origKey);

        Tht::module('Perf')->start('Cache.delete', $origKey);

        $v = $this->driver->delete($k);

        unset($this->localCache[$k]);

        Tht::module('Perf')->u_stop();

        return $v;
    }

    function u_counter($origKey, $delta=1) {

        $this->ARGS('si', func_get_args());

        $k = $this->cleanKey($origKey);

        Tht::module('Perf')->start('Cache.counter', $origKey);

        $num = $this->driver->counter($k, $delta);
        $this->localCache[$k] = $num;

        Tht::module('Perf')->u_stop();

        return $num;
    }
}

// TODO: create interface
class CacheDriver {

    function has($k) {

        // TODO: optimize away redundant get()
        $getVal = $this->get($k, -1, null);

        return !is_null($getVal);
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

    function serialize($v, $ttlSecs) {

        // v = value
        // c = create date
        // ttl = time to live
        $record = [
            'v' => $v,
            'c' => Tht::module('Date')->u_unix_time(),
            'ttl' => $ttlSecs,
        ];

        return serialize($record);
    }

    function get($k, $syncFileTime, $default) {}

    function set($k, $v, $ttlSecs) {}

    function delete($k) {}

    function garbageCollect() {}
}

class FileCacheDriver extends CacheDriver {

    private $didActOnFile = [];

    function path($k) {

        return Tht::path('kvCache', $k . '.txt');
    }

    function get($k, $syncFileTime, $default) {

        $data = $this->fetch($k);

        if (!$data) {
            return $default;
        }

        $record = unserialize($data);

        if (!$record || !isset($record['v'])) {
            return $default;
        }

        if ($syncFileTime >= 0) {
            // synced file was updated
            if ($syncFileTime > $record['c']) {
                return $default;
            }
        }
        else {
            // check standard expiry - createTime + TTL
            if ($record['ttl'] > 0) {
                $expiry = $record['c'] + $record['ttl'];
                if (Tht::module('Date')->u_unix_time() > $expiry) {
                    return $default;
                }
            }
        }

        return $record['v'];
    }

    function fetch($k) {

         $path = $this->path($k);

         if (file_exists($path)) {
            return file_get_contents($path);
         }
         else {
             return null;
         }
    }

    function set($k, $v, $ttlSecs) {

        $path = $this->path($k, $ttlSecs);

        file_put_contents($path, $this->serialize($v, $ttlSecs), LOCK_EX);

        // update file modtime to indicate when it should be gc'd
        $gcTtl = $ttlSecs > 0 ? $ttlSecs : Tht::module('Date')->u_duration_to_secs('30 days');
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

        Tht::module('*File')->u_loop_dir(Tht::path('kvCache'), function($f) use ($now, $numDeleted){
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

class ApcuCacheDriver extends CacheDriver {

    function get($k, $syncFileTime=-1, $default='') {

        $val = apcu_fetch($k, $success);

        if (!$success) {
            return $default;
        }

        if ($syncFileTime >= 0) {

            $keyInfo = apcu_key_info($k);

            if (!$keyInfo) { return $default; }

            // synced file was updated
            if ($syncFileTime > $keyInfo['creation_time']) {
                return $default;
            }
        }

        return $val;
    }

    function set($k, $v, $ttlSecs) {

        apcu_add($k, $v, $ttlSecs);
    }

    function delete($k) {

        apcu_delete($k);
    }

    function has($k) {

        $keyInfo = apcu_key_info($k);

        if (!$keyInfo || $keyInfo['ttl'] == 0) {
            return false;
        }

        $expiry = $keyInfo['creation_time'] + $keyInfo['ttl'];
        if (Tht::module('Date')->u_unix_time() > $expiry) {
            return false;
        }

        return true;
    }

    function counter($k, $delta) {

        $ttlSecs = (60 * 60 * 24 * 30); // 30 days

        return apcu_inc($k, $delta, $isSuccess, $ttlSecs);
    }
}
