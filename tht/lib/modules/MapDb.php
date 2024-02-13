<?php


// TODO: Use ->> operator to reach into JSON objects.

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

class u_MapDb extends OStdModule {

    private $dbh = null;
    private $doCreateBucket = false;

    private function connect() {
        if (is_null($this->dbh)) {
            $this->dbh = Tht::module('Db')->u_use_database('mapDb');
        }
    }
    // create table users(id integer primary key autoincrement, primaryIndex varchar(255), parentIndex varchar(255), createDate unsigned int, level unsigned int, data text not null);
    // create index idx_users_primary on users (primaryIndex);
    // create index idx_users_parent on users (parentIndex);

    public function u_add($bucket, $map) {

        if (isset($map['id'])) {
            Tht::error("MapDb: Key `id` not allowed for `$bucket`.  This is a reserved key.");
        }
        if (isset($map['createDate'])) {
            Tht::error("MapDb: Key `createDate` not allowed for `$bucket`.  This is a reserved key.");
        }

        $self = $this;
        return $this->executeForBucket($bucket, function() use ($self, $bucket, $map) {

            // TODO: expand Date objects (to SqlString?)
            $record = [
                'createDate' => time(),
                'data'       => json_encode(unv($map)),
                         //   'level'      => 0,
             //   'primaryIndex' => $primaryIndex,
            ];

            $self->dbh->u_insert_row($bucket, OMap::create($record));

            return $self->dbh->u_last_insert_id();
        });
    }

    public function u_get($bucket, $id) {

        $self = $this;
        return $this->executeForBucket($bucket, function() use ($self, $id, $bucket) {

            $sql = new \o\SqlTypeString('select * from `' . $bucket . '` where id = {id} limit 1');
            $sql->u_fill(OMap::create([ 'id' => $id ]));

            $row = $self->dbh->u_select_row($sql);

            return $self->convertRowToMap($row);

        });
    }

    public function u_get_many($bucket, $whereOrMap) {

        $this->ARGS('*m', func_get_args());

        if ($whereMap) {
            $whereSql = $this->mapToWhereSql($whereMap, true);
            $sql->appendTypeString($whereSql);
        }

        $self = $this;
        return $this->executeForBucket($bucket, function() use ($self, $id, $bucket) {

            $sql = new \o\SqlTypeString('select * from `' . $bucket . '` where id = {id} limit 1');
            $sql->u_fill(OMap::create([ 'id' => $id ]));

            $row = $self->dbh->u_select_row($sql);

            return $self->convertRowToMap($row);

        });
    }

    private function executeForBucket($bucket, $fn) {

        $this->connect();

        try {
            return $fn();
        } catch (\Exception $e) {

            $msg = $e->getMessage();
            if (preg_match('/no such table/i', $msg)) {
                $this->createBucket($bucket);
                return $fn();
            }
        }

        return '';
    }

    private function createBucket($bucket) {

        // TODO: validate bucket

        $this->dbh->u_run_query(new SqlTypeString("
             create table $bucket(
                id integer primary key autoincrement,
                primaryIndex varchar(255),
                parentIndex varchar(255),
                createDate unsigned int,
                level unsigned int,
                data text not null
            );
        "));

        $this->dbh->u_run_query(new SqlTypeString("
            create index idx_primary_$bucket on users (primaryIndex);
        "));

        $this->dbh->u_run_query(new SqlTypeString("
            create index idx_parent_$bucket on users (parentIndex);
        "));
    }

    private function convertRowToMap($row) {

        if (!$row) { return OMap::create([]); }

        $map = Tht::module('Json')->u_decode(new JsonTypeString($row['data']));

        $map['id'] = intval($row['id']);
        $map['createDate'] = $row['createDate'];

        return $map;
    }




 }