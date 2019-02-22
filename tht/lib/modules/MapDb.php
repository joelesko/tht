<?php

// types of uses:
//   key/value (ad hoc indexing)
//   lists indexed by key (e.g. userId)
//   how to order by a subkey? (e.g. most xp? lastVisitDate?)
// re-add isDeleted
// auto index numeric values?
// performant way to iterate through many rows
// one time query to get list of all tables and databases?  (a registry table on seperate db)
// just check if database file exists on first access


namespace o;

/*
    CREATE TABLE maps (
        id integer PRIMARY KEY,
        bucket text NOT NULL,
        key text NOT NULL,
        createDate INTEGER,
        data TEXT
    );

    CREATE INDEX i_maps_bucket_key ON maps (bucket, key);
    CREATE INDEX i_maps_bucket ON maps (bucket);
    CREATE INDEX i_maps_key ON maps (key);
    CREATE INDEX i_maps_create_date ON maps (createDate);
*/

class u_MapDb extends StdModule {

    private $dbh = null;

    private function connect() {
        if (is_null($this->dbh)) {
            $this->dbh = Tht::module('Db')->u_use('mapDb');
        }
    }

    public function u_insert_map($bucket, $key, $map) {

        $this->connect();

        if (isset($map['id'])) {
            Tht::error("MapDb: Key `id` not allowed for `$bucket/$key`.  This is a reserved key.");
        }
        if (isset($map['createDate'])) {
            Tht::error("MapDb: Key `createDate` not allowed for `$bucket/$key`.  This is a reserved key.");
        }

        $record = [
            'createDate' => time(),
            'bucket'     => $bucket,
            'key'        => $key,
            'data'       => json_encode(uv($map)),
        ];

        $this->dbh->u_insert_row('maps', OMap::create($record));

        return $this->dbh->u_last_insert_id();
    }

    public function u_select_id($bucket, $id) {

        $this->connect();

        if (!is_numeric($id)) {
            Tht::error('selectMap() argument `id` must be a Number.');
        }
        $sql = new \o\SqlLockString('select * from maps where bucket = {bucket} and id = {id} limit 1');
        $sql->u_fill([ 'bucket' => $bucket, 'id' => $id ]);

        $row = $this->dbh->u_select_row($sql);
        return $this->convertRowToMap($row);
    }

    // public function u_update_map($bucket, $id, $map) {

    //     $this->connect();

    //     if (!is_numeric($id)) {
    //         Tht::error('selectMap() argument `id` must be a Number.');
    //     }
    //     $where = new \o\SqlLockString('where bucket = {bucket} and id = {id} limit 1');
    //     $where->u_fill([ 'bucket' => $bucket, 'id' => $id, 'data' => ]);

    //     $row = $this->dbh->u_update_rows('map_db', [ 'data' => $map ], $where);
    //     return $this->convertRowToMap($row);
    // }


    // TODO:
    // older than / younger than createDate
    // limit
    public function u_select_maps($bucket, $key='', $limit=100) {

        $this->connect();

        $sql = new \o\SqlLockString('select * from maps where bucket = {bucket} and key = {key} order by id desc limit {limit}');
        $sql->u_fill([ 'bucket' => $bucket, 'key' => $key, 'limit' => $limit ]);

        $rows = $this->dbh->u_select_rows($sql);

        $maps = [];
        foreach ($rows as $row) {
            $maps []= $this->convertRowToMap($row);
        }

        return OList::create($maps);
    }

    private function convertRowToMap($row) {
        if (!$row) { return []; }
        $map = json_decode($row['data'], true);
        $map['id'] = intval($row['id']);
        $map['createDate'] = intval($row['createDate']);
        return OMap::create($map);
    }

    public function u_buckets() {
        $this->connect();
        $sql = new \o\SqlLockString('select bucket, count(*) numMaps from maps group by bucket order by bucket ASC');
        $rows = $this->dbh->u_select_rows($sql);
        foreach ($rows as $row) {
            $row['numMaps'] = intval($row['numMaps']);
        }
        return OList::create($rows);
    }

    public function u_delete_bucket($bucket) {
        $this->connect();
        $where = new OLockString('bucket = {0}');
        $where->u_fill($bucket);
        $this->dbh->u_delete_rows('maps', $where);
        return true;
    }

    public function u_delete_id($bucket, $id) {
        $this->connect();
        $where = new OLockString('bucket = {0} and id = {1}');
        $where->u_fill($bucket, $id);
        $this->dbh->u_delete_rows('maps', $where);
        return true;
    }

    public function u_delete_key($bucket, $key) {
        $this->connect();
        $where = new OLockString('bucket = {0} and key = {1}');
        $where->u_fill($bucket, $key);
        $this->dbh->u_delete_rows('maps', $where);
        return true;
    }




 }