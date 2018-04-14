<?php

namespace o;

/*

	CREATE TABLE map_db (
	    id integer PRIMARY KEY,
	    bucket text NOT NULL,
	    key text NOT NULL,
	    createDate INTEGER,
	    isDeleted INTEGER,
	    data TEXT
	);

	CREATE INDEX i_map_db_bucket ON map_db (bucket);
	CREATE INDEX i_map_db_key ON map_db (key);
	CREATE INDEX i_map_db_create_date ON map_db (createDate);

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
			'isDeleted'  => 0,
			'data'       => json_encode(uv($map)),
		];

		$this->dbh->u_insert_row('map_db', $record);

		return true;
	}

	public function u_select_map($bucket, $id) {

		$this->connect();

		if (!is_numeric($id)) {
			Tht::error('selectMap() argument `id` must be a Number.');
		}
		$sql = new \o\SqlLockString('select * from map_db where bucket = {bucket} and id = {id} limit 1');
		$sql->u_fill([ 'bucket' => $bucket, 'id' => $id ]);

		$row = $this->dbh->u_select_row($sql);
		return $this->convertRowToMap($row);
	}


	// TODO: 
	// optional key
	// older than / younger than createDate
	// limit, order by
	public function u_select_maps($bucket, $key='', $limit=-1) {

		$this->connect();

		$sql = new \o\SqlLockString('select * from map_db where bucket = {bucket} and key = {key} order by createDate desc');
		$sql->u_fill([ 'bucket' => $bucket, 'key' => $key ]);

		$rows = $this->dbh->u_select_rows($sql);

		$maps = [];
		foreach ($rows as $row) {
			$maps []= $this->convertRowToMap($row);
		}

		return $maps;
	}

	private function convertRowToMap($row) {
		if (!$row) { return []; }
		$map = json_decode($row['data'], true);
		$map['id'] = intval($row['id']);
		$map['createDate'] = intval($row['createDate']);
		return $map;
	}

	public function u_buckets() {
		$this->connect();
		$sql = new \o\SqlLockString('select bucket, count(*) numMaps from map_db group by bucket order by bucket ASC');
		$rows = $this->dbh->u_select_rows($sql);
		foreach ($rows as $row) {
			$row['numMaps'] = intval($row['numMaps']);
		}
		return $rows;
	}

	public function u_delete_bucket($bucket) {
		$this->connect();
		$where = new OLockString('bucket = {0}');
		$where->u_fill($bucket);
		$this->dbh->u_delete_rows('map_db', $where);
		return true;
	}

	public function u_delete_id($bucket, $id) {
		$this->connect();
		$where = new OLockString('bucket = {0} and id = {1}');
		$where->u_fill($bucket, $id);
		$this->dbh->u_delete_rows('map_db', $where);
		return true;
	}

	public function u_delete_key($bucket, $key) {
		$this->connect();
		$where = new OLockString('bucket = {0} and key = {1}');
		$where->u_fill($bucket, $key);
		$this->dbh->u_delete_rows('map_db', $where);
		return true;
	}




 }