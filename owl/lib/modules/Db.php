<?php

namespace o;

class u_Db extends StdModule {

    private $connectionCache = [];
    private $dbId = 'default';
    private $lastStatus = [ 'numRows' => -1, 'insertId' => -1 ];

    private function connect () {

        Owl::module('Meta')->u_no_template_mode();

        $dbId = $this->dbId;

        if (isset($this->connectionCache[$dbId])) {
            return $this->connectionCache[$dbId];
        }

        Owl::module('Perf')->u_start('db.connect', $dbId);

        $dbConfig = $this->u_get_database_config($dbId);
        if (isset($dbConfig['file'])) {
            $dbFilePath = Owl::path('db', $dbConfig['file']);
            if (! file_exists($dbFilePath)) {
                Owl::error("Can't find database file '$dbFilePath'.");
            }
            $dbh = new \PDO('sqlite:' . $dbFilePath);
        } else {
             $dbh = new \PDO(
                 $dbConfig['driver'] . ':host=' . $dbConfig['server'] . ';dbname=' . $dbConfig['database'],
                 $dbConfig['username'],
                 $dbConfig['password'],
                 array(PDO::ATTR_PERSISTENT => false)
             );
        }
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->connectionCache[$dbId] = $dbh;

        Owl::module('Perf')->u_stop('db.connect', $dbId);

        return $dbh;
    }

    function u_use ($dbId) {
        $this->u_get_database_config($dbId);
        $dbh = new u_Db ();
        $dbh->dbId = $dbId;
        $dbh->connect();
        return $dbh;
    }

    function u_get_database_config ($dbId) {
        return Owl::getTopConfig('databases', $dbId);
    }

    function u_last_insert_id () {
        if ($this->lastStatus['insertId'] < 0) { Owl::error('No Database insert has been executed.'); }
        return $this->lastStatus['insertId'];
    }

    function u_last_row_count () {
        if ($this->lastStatus['numRows'] < 0) { Owl::error('No Database query has been executed.'); }
        return $this->lastStatus['numRows'];
    }

    function u_get_databases() {
        $config = Owl::getAllConfig();
        $dbs = [];
        foreach ($config as $k => $v) {
            if (strpos($k, 'database.') !== false) {
                $dbId = substr($k, 9);
                if ($dbId === 'default') {
                    continue;
                }
                $v['databaseId'] = $dbId;
                $dbs []= $v;
            }
        }
        return $dbs;
    }

    function u_get_tables() {
        $lSql = new \o\SqlLockString ("SELECT name FROM sqlite_master WHERE type='table'");
        return v($this->u_select_rows($lSql))->u_column('name');
    }

    function u_table_exists($tableName) {
        $lSql = new \o\SqlLockString ("SELECT name FROM sqlite_master WHERE type='table' AND name = {0} LIMIT 1");
        $lSql->u_fill($tableName);
        $rows = $this->u_select_rows($lSql);
        return count($rows) == 1;
    }

    function u_get_columns ($tableName){
        if (preg_match('/[^a-zA-Z0-9_-]/', $tableName)) {
            Owl::error('Invalid character in table name: ' . $tableName);
        }
        $lSql = new \o\SqlLockString ("PRAGMA table_info(" . $tableName . ")");
        return $this->u_select_rows($lSql);
    }

    function u_create_table($table, $cols)  {
        $table = $this->untaintName($table, 'table');
        $sql = "CREATE TABLE IF NOT EXISTS $table (\n";
        $aCols = [];
        foreach (uv($cols) as $name => $type) {
            $name = $this->untaintName($name, 'column');
            $type = $this->untaintArg($type, 'column type');
            $aCols []= "$name $type";
        }
        $sql .= implode(",\n", $aCols);
        $sql .= ')';
        return $this->u_query(new \o\SqlLockString ($sql));
    }

    function u_create_index($table, $col)  {
        $table = $this->untaintName($table, 'table');
        $col = $this->untaintName($col, 'column');
        $sql = "CREATE INDEX i_{$table}_{$col} ON $table ($col)";
        return $this->u_query(new \o\SqlLockString ($sql));
    }

    function untaintName($n, $label) {
        $n = trim($n);
        if (!strlen($n) || preg_match('/[^a-zA-Z0-9_]/', $n)) {
            Owl::error("Invalid $label name: '$n'");
        }
        return $n;
    }

    function untaintArg($n, $label) {
        $n = trim($n);
        if (!strlen($n) || preg_match('/[^a-zA-Z0-9_() ]/', $n)) {
            Owl::error("Invalid $label: '$n'");
        }
        return $n;
    }


    ////// CRUD Commands


    function u_select_rows ($sql) {

        $sth = $this->query($sql);
        $rows = [];
        while (true) {
            $row = $sth->fetch(\PDO::FETCH_ASSOC);
            if (!$row) { break; }
            $rows []= OMap::create($row);
        }

        return $rows;
    }

    function u_select_row ($sql) {
        $rows = $this->u_select_rows($sql);
        if (count($rows) > 1) {
            Owl::error('selectRow got ' . count($rows) . " rows.  Expected only one.\n\nTry adding a LIMIT 1 clause to your query.");
        }
        $row = isset($rows[0]) ? $rows[0] : '';

        return $row;
    }

    function u_insert_row ($tTable, $fields){

        $table = $this->untaintName($tTable, 'table');

        $fields = uv($fields);

        $cols = [];
        $vals = [];
        foreach ($fields as $col => $value) {
            $cols []= $col;
            $vals []= ':' . $col;
        }
        $sCols = join(", ", $cols);
        $sVals = join(", ", $vals);

        $sql = "INSERT INTO $table ($sCols) VALUES ($sVals)";

        $lSql = new \o\SqlLockString ($sql);
        $lSql->u_fill($fields);

        $this->query($lSql);
    }

    function u_update_rows ($tTable, $vals, $lWhere) {

        $table = $this->untaintName($tTable, 'table');
        $where = OLockString::getUnlocked($lWhere);

        // create SET expression
        $cols = [];
        $sets = [];
        foreach (uv($vals) as $col => $value) {
            $set = "$col = :$col";
            $sets []= $set;
        }
        $sSets = join(", ", $sets);

        $params = array_merge(uv($vals), $lWhere->getParams());

        if (strpos(strtolower($where), 'where') !== false) {
            Owl::error('WHERE keyword is not needed in update() query', array('table' => $table, 'where' => $where));
        }

        $sql = "UPDATE $table SET $sSets WHERE $where";
        $lSql = new \o\SqlLockString ($sql);
        $lSql->u_fill($params);

        $this->query($lSql);
    }

    function u_delete_rows ($tTable, $lWhere) {

        $where = OLockString::getUnlocked($lWhere);
        $table = $this->untaintName($tTable, 'table');

        $sql = "DELETE FROM $table WHERE $where";
        $lSql = new \o\SqlLockString ($sql);
        $lSql->u_fill($lWhere->getParams());

        $this->query($lSql);
    }

    function u_query ($lockedSql) {
        $this->query($lockedSql);
    }

    function query ($lockedSql) {

        Owl::module('Meta')->u_no_template_mode();

        $sql = OLockString::getUnlocked($lockedSql);
        $params = $lockedSql->getParams();

        $this->lastStatus['insertId'] = -1;
        $this->lastStatus['numRows'] = -1;

        // placeholder {0} or {param} syntax
        $sql = preg_replace('/\{([0-9]+)\}/', ':param_$1', $sql);
        $sql = preg_replace('/\{([a-zA-Z0-9]+)\}/', ':$1', $sql);


        Owl::module('Perf')->u_start('Db.query', $sql);

        if (!trim($sql)) {
            Owl::error('Empty SQL query');
        }

        $sth = null;
        try {
            $dbh = $this->connect();
            $sth = $dbh->prepare($sql);

            if (!count($params)) {
                $sth->execute();
            }
            else {

                $params = uv($params);
                $aParams = [];
                foreach ($params as $k => $v) {
                    if (is_numeric($k)) {
                        $aParams['param_' . $k] = uv($params[$k]);
                    } else {
                        $aParams[$k] = uv($params[$k]);
                    }
                }
                $params = $aParams;

                // make sure params match placeholders
                $placeholders = array();
                preg_match_all('/\:([a-zA-Z0-9_]+)\b/m', $sql, $placeholders);
                $fparams = array();
                foreach ($placeholders[1] as $ph) {
                    if (!isset($params[$ph])) {
                        Owl::error("Missing placeholder value for '$ph'" , array('placeholders' => $placeholders[1], 'params' => $params));
                    }
                    $fparams[$ph] = $params[$ph];
                }

                $sth->execute($fparams);
            }
            $this->lastStatus['numRows'] = $sth->rowCount();
            $this->lastStatus['insertId'] = $dbh->lastInsertId();
        }
        catch (\PDOException $e) {
            Owl::error('Database error: ' . $e->getMessage(), array('databaseId' => $this->dbId, 'query' => $sql, 'params' => $params));
        }

        Owl::module('Perf')->u_stop();

        return $sth;
    }
}


