<?php

//namespace o;
//
// class u_Cache extends StdModule {
//
//     private $driver;
//
//     function __construct() {
//         $this->driver = new FileCacheDriver ();
//     }
//
//     function u_set_driver($d) {
//         if ($d == 'file') {
//             $this->driver = new FileCacheDriver ();
//         } else if ($d == 'db') {
//             $this->driver = new DbCacheDriver ();
//         }
//     }
//
//     function u_get($k) {
//         return $this->driver->get($k);
//     }
//
//     function u_set($k, $v, $expiryMins) {
//         $expiryDate = time() + $expiryMins * 60;
//         return $this->driver->set($k, $v, $expiryDate);
//     }
//
//     function u_delete($k) {
//         return $this->driver->delete($k);
//     }
//
//     function u_counter($k, $val=1) {
//         return $this->driver->counter($k, $val);
//     }
// }
//
// class CacheDriver {
//     function unwrap($json) {
//         $o = json_decode($json, true);
//         return $o['v'];
//     }
//
//     function wrap($v) {
//         return json_encode(['v' => $v]);
//     }
//
//     function normalKey($k) {
//         return preg_replace('/[^a-zA-Z0-9]/', '_', trim($k));
//     }
//
//     function counter($k, $delta) {
//         $v = $this->get($k, 0);
//         $v += $delta;
//         if ($v < 0) { $v = 0; }
//         $this->set($k, $v, time() + (60 * 60 * 24 * 30));
//         return $v;
//     }
// }
//
//
// class FileCacheDriver extends CacheDriver {
//
//     function path($k) {
//         return Owl::path('kvCache', $this->normalKey($k) . '.txt');
//     }
//
//     function get($k, $def='') {
//         $path = $this->path($k);
//         if (file_exists($path) && filemtime($path) > time()) {
//             $json = file_get_contents($path);
//             return $this->unwrap($json);
//         } else {
//             return $def;
//         }
//     }
//
//     function set($k, $v, $ex) {
//         $path = $this->path($k);
//         file_put_contents($path, $this->wrap($v));
//         touch($path, $ex);
//     }
//
//     function delete($k) {
//         $path = $this->path($k);
//         if (file_exists($path)) {
//             unlink($path);
//         }
//     }
// }
//
// // About 4x slower than File Driver...
// class DbCacheDriver extends CacheDriver {
//
//     function db() {
//         return Owl::module('Database')->u_use('cache');
//     }
//
//     function get($k, $def='') {
//         $db = $this->db();
//         $lsql = new \o\OLockString('SELECT * FROM cache WHERE key = {0} AND expireDate > {1}');
//         $lsql->u_fill($k, time());
//         $row = $db->u_select_row($lsql);
//         return $row ? $this->unwrap($row['value']) : $def;
//     }
//
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
//
//     function delete() {
//
//     }
//
// }

