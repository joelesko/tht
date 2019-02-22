<?php

namespace o;

class u_Db extends StdModule {

    private $connectionCache = [];
    private $dbId = 'default';
    private $lastStatus = [ 'numRows' => -1, 'insertId' => -1 ];

    private function connect () {

        Tht::module('Meta')->u_no_template_mode();

        $dbId = $this->dbId;

        if (isset($this->connectionCache[$dbId])) {
            return $this->connectionCache[$dbId];
        }

        Tht::module('Perf')->u_start('db.connect', $dbId);

        $dbConfig = $this->u_get_database_config($dbId);
        if (isset($dbConfig['file'])) {
            $dbFilePath = Tht::path('db', $dbConfig['file']);
            if (! file_exists($dbFilePath)) {
                Tht::error("Can not find database file `$dbFilePath`.");
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

        Tht::module('Perf')->u_stop('db.connect', $dbId);

        return $dbh;
    }

    function u_use ($dbId) {
        $dbh = new u_Db ();
        $dbh->dbId = $dbId;
        $dbh->connect();
        return $dbh;
    }

    function u_get_database_config ($dbId) {
        ARGS('s', func_get_args());
        return Tht::getTopConfig('databases', $dbId);
    }

    function u_last_insert_id () {
        if ($this->lastStatus['insertId'] < 0) { Tht::error('No Database insert has been executed.'); }
        return $this->lastStatus['insertId'];
    }

    function u_last_row_count () {
        if ($this->lastStatus['numRows'] < 0) { Tht::error('No Database query has been executed.'); }
        return $this->lastStatus['numRows'];
    }

    function u_get_databases() {
        $config = Tht::getAllConfig();
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

    // TODO: support more than just sqlite
    function u_get_tables() {
        $lSql = new \o\SqlLockString ("SELECT name FROM sqlite_master WHERE type='table'");
        return v($this->u_select_rows($lSql))->u_column('name');
    }

    // TODO: support more than just sqlite
    function u_table_exists($tableName) {
        ARGS('s', func_get_args());
        $lSql = new \o\SqlLockString ("SELECT name FROM sqlite_master WHERE type='table' AND name = {0} LIMIT 1");
        $lSql->u_fill($tableName);
        $rows = $this->u_select_rows($lSql);
        return count($rows) == 1;
    }

    // TODO: support more than just sqlite
    function u_get_columns ($tableName){
        ARGS('s', func_get_args());
        if (preg_match('/[^a-zA-Z0-9_-]/', $tableName)) {
            Tht::error("Invalid character in table name: `$tableName`");
        }
        $lSql = new \o\SqlLockString ("PRAGMA table_info(" . $tableName . ")");
        return $this->u_select_rows($lSql);
    }

    // TODO: support more than just sqlite
    function u_create_table($table, $cols)  {
        ARGS('sm', func_get_args());
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

    // TODO: support more than just sqlite
    function u_create_index($table, $col)  {
        ARGS('ss', func_get_args());
        $table = $this->untaintName($table, 'table');
        $col = $this->untaintName($col, 'column');
        $sql = "CREATE INDEX i_{$table}_{$col} ON $table ($col)";
        return $this->u_query(new \o\SqlLockString ($sql));
    }

    function untaintName($n, $label) {
        $n = trim($n);
        if (!strlen($n) || preg_match('/[^a-zA-Z0-9_]/', $n)) {
            Tht::error("Invalid $label name: `$n`");
        }
        return $n;
    }

    function untaintArg($n, $label) {
        $n = trim($n);
        if (!strlen($n) || preg_match('/[^a-zA-Z0-9_() ]/', $n)) {
            Tht::error("Invalid $label: `$n`");
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
            Tht::error('selectRow got ' . count($rows) . " rows.  Expected only one.\n\nTry adding a `LIMIT 1` clause to your query.");
        }
        $row = isset($rows[0]) ? $rows[0] : '';

        return OMap::create($row);
    }

    function u_insert_row ($tTable, $fields){

        ARGS('sm', func_get_args());

        $table = $this->untaintName($tTable, 'table');

        $fields = uv($fields);

        $cols = [];
        $vals = [];
        foreach ($fields as $col => $value) {
            $col = $this->untaintName($col, 'column');
            $cols []= $col;
            $vals []= ':' . $col;
        }
        $sCols = join(", ", $cols);
        $sVals = join(", ", $vals);

        $sql = "INSERT INTO $table ($sCols) VALUES ($sVals)";

        $lSql = new \o\SqlLockString ($sql);
        $lSql->u_fill(v($fields));

        $this->query($lSql);
    }

    function u_update_rows ($tTable, $vals, $lWhere) {

        $table = $this->untaintName($tTable, 'table');
        $where = OLockString::getUnlockedRaw($lWhere, 'sql');

        // create SET expression
        $cols = [];
        $sets = [];
        foreach (uv($vals) as $col => $value) {
            $col = $this->untaintName($col, 'column');
            $set = "$col = :$col";
            $sets []= $set;
        }
        $sSets = join(", ", $sets);

        $params = array_merge(uv($vals), uv($lWhere->u_params()));

        if (strpos(strtolower($where), 'where') !== false) {
            Tht::error('Please remove `WHERE` keyword from `where` argument', array('table' => $table, 'where' => $where));
        }

        $sql = "UPDATE $table SET $sSets WHERE $where";
        $lSql = new \o\SqlLockString ($sql);
        $lSql->u_fill(v($params));

        $this->query($lSql);
    }

    function u_delete_rows ($tTable, $lWhere) {

        $where = OLockString::getUnlockedRaw($lWhere, 'sql');
        $table = $this->untaintName($tTable, 'table');

        $sql = "DELETE FROM $table WHERE $where";
        $lSql = new \o\SqlLockString ($sql);
        $lSql->u_fill($lWhere->u_params());

        $this->query($lSql);
    }

    function u_query ($lockedSql) {
        $this->query($lockedSql);
    }

    function query ($lockedSql) {

        Tht::module('Meta')->u_no_template_mode();

        $sql = OLockString::getUnlockedRaw($lockedSql, 'sql');
        $params = $lockedSql->u_params();

        $this->lastStatus['insertId'] = -1;
        $this->lastStatus['numRows'] = -1;

        // placeholder {0} or {param} syntax
        $sql = preg_replace('/\{([0-9]+)\}/', ':param_$1', $sql);
        $sql = preg_replace('/\{([a-zA-Z0-9]+)\}/', ':$1', $sql);

        // Replace {} with :param_0, etc.
        $numSlots = 0;
        $countSlots = function($matches) use ($numSlots) {
            $s = ':param_' . $numSlots;
            $numSlots += 1;
            return $s;
        };
        $sql = preg_replace_callback('/\{\}/', $countSlots, $sql);


        Tht::module('Perf')->u_start('Db.query', $sql);

        if (!trim($sql)) {
            Tht::error('Empty SQL query');
        }

        $sth = null;
        try {
            $dbh = $this->connect();
            $sth = $dbh->prepare($sql);

            if (!count($params)) {
                $sth->execute();
            }
            else {

                // unwrap param values
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
                        Tht::error("Missing placeholder value for `$ph`." , array('placeholders' => $placeholders[1], 'params' => $params));
                    }
                    $fparams[$ph] = $params[$ph];
                }

                $sth->execute($fparams);
            }
            $this->lastStatus['numRows'] = $sth->rowCount();
            $this->lastStatus['insertId'] = $dbh->lastInsertId();
        }
        catch (\PDOException $e) {
            Tht::error('Database error: ' . $e->getMessage(), array('databaseId' => $this->dbId, 'query' => $sql, 'params' => $params));
        }

        Tht::module('Perf')->u_stop();

        return $sth;
    }
}


