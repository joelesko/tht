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

            if (rand(1, Tht::getThtConfig('cacheGarbageCollectRate')) == 1) {
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

    function u_delete_all() {

        $this->ARGS('', func_get_args());

        $this->driver->clear();

        return $this;
    }

    function u_has($origKey) {

        $this->ARGS('s', func_get_args());

        $k = $this->cleanKey($origKey);

        $lcKey = '__has:' . $k;
        if (isset($this->localCache[$lcKey])) {
            return $this->localCache[$lcKey];
        }

        $perfTask = Tht::module('Perf')->u_start('Cache.has', $origKey);

        $v = $this->driver->has($k);

        $this->localCache[$lcKey] = $v;

        $perfTask->u_stop();

        return $v;
    }

    function u_get($origKey, $default='', $ttl='1 hour') {

        $this->ARGS('s*s', func_get_args());

        $k = $this->cleanKey($origKey);

        if (isset($this->localCache[$k])) {
            return $this->localCache[$k];
        }

        $perfTask = Tht::module('Perf')->u_start('Cache.get', $origKey);

        $v = $this->driver->get($k, -1, '');

        Tht::module('Date')->u_diff_to_seconds($ttl);

        $ttlSecs = Tht::module('Date')->u_diff_to_seconds($ttl);

        if ($ttlSecs <= 0) {
            $v = '';
        }

        if ($v === '') {
            if (is_callable($default)) {
                $v = $default();
                if ($v === null) {
                    $this->error('Default function must return a non-null value.');
                }
                if ($ttlSecs >= 0) {
                    $this->u_set($origKey, $v, $ttlSecs);
                }
            }
            else {
                $v = $default;
            }
        }

        $perfTask->u_stop();

        $this->localCache[$k] = $v;

        return $v;
    }

    function u_get_sync($origKey, $syncFileTime, $default='') {

        $this->ARGS('si*', func_get_args());

        $k = $this->cleanKey($origKey);

        if (isset($this->localCache[$k])) {
            return $this->localCache[$k];
        }

        $perfTask = Tht::module('Perf')->u_start('Cache.getSync', $origKey);

        $v = $this->driver->get($k, $syncFileTime, $default);
        $this->localCache[$k] = $v;

        $perfTask->u_stop();

        return $v;
    }

    function u_set($origKey, $v, $ttl) {

        $this->ARGS('s*s', func_get_args());

        $k = $this->cleanKey($origKey);
        $ttlSecs = Tht::module('Date')->u_diff_to_seconds($ttl);

        $perfTask = Tht::module('Perf')->u_start('Cache.set', $origKey);

        $this->tryGarbageCollect();
        $this->driver->set($k, $v, $ttlSecs);

        $this->localCache[$k] = $v;

        $perfTask->u_stop();

        return $this;
    }

    function u_delete($origKey) {

        $this->ARGS('s', func_get_args());

        $k = $this->cleanKey($origKey);

        $perfTask = Tht::module('Perf')->u_start('Cache.delete', $origKey);

        $v = $this->driver->delete($k);

        unset($this->localCache[$k]);

        $perfTask->u_stop();

        return $this;
    }

    function u_counter($origKey, $delta=1) {

        $this->ARGS('si', func_get_args());

        $k = $this->cleanKey($origKey);

        $perfTask = Tht::module('Perf')->u_start('Cache.counter', $origKey);

        $num = $this->driver->counter($k, $delta);
        $this->localCache[$k] = $num;

        $perfTask->u_stop();

        return $num;
    }
}

// TODO: create interface instead?
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

    function clear() {}
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

        // Perf: unserialize is 3x faster than json_decode, and 2x faster than requiring a file with literal data.
        try {
            $record = unserialize($data);
        } catch(\Exception $e) {
            Tht::error("Error unserializing object from cache. ");
        }

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
        $gcTtl = $ttlSecs > 0 ? $ttlSecs : Tht::module('Date')->u_diff_to_seconds('30 days');
        touch($path, time() + $gcTtl);
    }

    function delete($k) {

        $path = $this->path($k);
        if (file_exists($path)) {
            unlink($path);
        }
    }

    function clear() {
        $dir = PathTypeString::create(Tht::path('kvCache'));
        $dir->u_loop_dir(function($f) {
            $fullPath = $f->u_path_parts()['path'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
        });
    }

    // The GC only looks at the modtime of the file, which is forced to the expiry date.
    function garbageCollect() {

        $now = time();
        $numDeleted = 0;
        $maxDelete = 100;

        $dir = PathTypeString::create(Tht::path('kvCache'));
        $dir->u_loop_dir(function($f) use ($now, $numDeleted, $maxDelete) {
            $fullPath = $f->u_render_string();
            if (filemtime($fullPath) <= $now) {
                if (!file_exists($fullPath)) {
                    Tht::error("Unable to unlink cache file: `" . $fullPath . "`");
                }
                unlink($fullPath);
                $numDeleted += 1;
                if ($numDeleted == $maxDelete) {
                    return true;
                }
            }
        });

        return $numDeleted;
    }
}

class ApcuCacheDriver extends CacheDriver {

    function get($k, $syncFileTime=-1, $default='') {

        // If an object is being un-serialized, but the class definition has been
        // changed since it was saved, drop it on the floor.
        set_error_handler(function($errNum, $errStr) use ($k) {
            if (str_contains(strtolower($errStr), 'deprecated')) {
                apcu_delete($k);
            }
        });
        $val = apcu_fetch($k, $success);
        restore_error_handler();

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

        $ok = apcu_store($k, $v, $ttlSecs);

        if (!$ok) {
            Tht::module('Cache')->error("Error adding key `$k` to APCU Cache.");
        }
    }

    function delete($k) {

        apcu_delete($k);
    }

    function clear() {

        apcu_clear_cache();
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
