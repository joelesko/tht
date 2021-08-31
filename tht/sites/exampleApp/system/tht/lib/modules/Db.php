<?php

namespace o;

class u_Db extends OStdModule {

    private $connectionCache = [];
    private $dbId = 'default';
    private $driver = '';
    private $lastStatus = [ 'numRows' => -1, 'fnGetInsertId' => null ];
    private $oneQueryIsSafe = [];

    private function connect () {

        Tht::module('Meta')->u_no_template_mode();

        $dbId = $this->dbId;

        if (isset($this->connectionCache[$dbId])) {
            return $this->connectionCache[$dbId];
        }

        Tht::module('Perf')->u_start('db.connect', $dbId);

        $dbConfig = $this->u_get_config($dbId);

        $this->checkConfig($dbConfig, ['driver']);

        $this->driver = $dbConfig['driver'];

        if ($dbConfig['driver'] == 'sqlite') {

            $this->checkRequiredLib('sqlite');
            $this->checkConfig($dbConfig, ['file']);

            $dbFilePath = Tht::path('db', $dbConfig['file']);
            if (!file_exists($dbFilePath)) {
                $this->error("Can not find database file `$dbFilePath`.");
            }

            // Don't add charset or other params. It just gets appended to filename.
            $dsn = 'sqlite:' . $dbFilePath;

            $dbh = new \PDO($dsn);
        }
        else {

            $this->checkRequiredLib($dbConfig['driver']);

            // TODO: allow arbitrary options

            $this->checkConfig($dbConfig, ['database', 'server', 'username', 'password']);

            $dsn = $dbConfig['driver'] . ':host=' . $dbConfig['server'] . '; ';
            $dsn .= 'dbname=' . $dbConfig['database'] . '; ';
            $dsn .= 'charset=UTF8;';

            $dbh = new \PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                [\PDO::ATTR_PERSISTENT => false]
            );
        }

        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->connectionCache[$dbId] = $dbh;

        Tht::module('Perf')->u_stop('db.connect', $dbId);

        return $dbh;
    }

    function checkConfig($conf, $fields) {
        foreach ($fields as $f) {
            if (!isset($conf[$f]) || !$conf[$f]) {
                $this->error("Missing field `$f` in config for database `" . $this->dbId . "`. Try: Update `config/app.local.jcon`");
            }
        }
    }

    function checkRequiredLib($rawDriver) {

        $driver = strtolower($rawDriver);

        $libs = [
            'sqlite' => 'pdo_sqlite',
            'mysql'  => 'pdo_mysql',
            'pgsql'  => 'pdo_pgsql',
            'odbc'   => 'pdo_odbc',
        ];

        $allowed = implode(', ', array_keys($libs));

        if (!isset($libs[$driver])) {
            $this->error("Unknown Db driver `$rawDriver`. Allowed drivers: $allowed");
        }

        // TODO: support ODBC params in connect()
        if ($driver === 'odbc') {
            $this->error('Sorry, ODBC is not supported yet. Feel free to submit a patch! :)');
        }

        $lib = $libs[$driver];
        if (!extension_loaded($lib)) {
            $errMsg = Tht::getLibIniError($lib);
            $this->error($errMsg);
        }
    }

    function u_use_database ($dbId) {

        $this->ARGS('s', func_get_args());

        $dbh = new u_Db ();
        $dbh->dbId = $dbId;
        $dbh->connect();

        return $dbh;
    }

    function u_get_config ($dbId) {

        $this->ARGS('s', func_get_args());

        return OMap::create(Tht::getTopConfig('databases', $dbId));
    }

    function u_last_insert_id ($seqName = null) {

        $this->ARGS('s', func_get_args());

        if (!$this->lastStatus['fnGetInsertId']) {
            $this->error('No Database insert has been executed.');
        }

        $id = $this->lastStatus['fnGetInsertId']($seqName);

        return is_numeric($id) ? (int) $id : $id;
    }

    function validateName($n, $label) {

        $n = trim($n);

        if (!strlen($n) || preg_match('/[^a-zA-Z0-9_]/', $n)) {
            $this->error("Invalid $label name: `$n`");
        }

        return $n;
    }

    function validateArg($n, $label) {

        $n = trim($n);

        if (!strlen($n) || preg_match('/[^a-zA-Z0-9_() ]/', $n)) {
            $this->error("Invalid $label: `$n`");
        }

        return $n;
    }

    function validateNum($n, $label) {

        if (!preg_match('/^\d+$/', $n)) {
            $this->error("Invalid number `$n` for `$label`.");
        }

        return $n;
    }

    function mapToWhereSql($map, $prependWhere = false) {

        if (!OMap::isa($map)) {
            if (preg_match('/^\bwhere\b/i', $map->u_raw_string())) {
                $this->error('Please remove `WHERE` keyword from `where` argument. (It will be added for you.)');
            }
            return $map;
        }

        $pairs = [];

        $append = '';
        if ($map['orderBy']) {
            $append .= " ORDER BY " . $this->validateName($map['orderBy'], 'column');
            $map->u_remove('orderBy');
        }
        else if ($map['orderByDesc']) {
            $append .= " ORDER BY " . $this->validateName($map['orderByDesc'], 'column') . ' DESC';
            $map->u_remove('orderByDesc');
        }

        if ($map['limit']) {
            $append .= " LIMIT " . $this->validateNum($map['limit'], 'limit');
            $map->u_remove('limit');
        }

        foreach (unv($map) as $col => $value) {
            $col = trim($col);
            $op = '=';
            preg_match('/ (>|<|>=|<=|!=)$/', $col, $m);
            if ($m && $m[1]) {
                $op = $m[1];
                $col = preg_replace('/\s*[<>!=]+$/', '', $col);
                $map[$col] = $value;
            }

            $col = $this->validateName($col, 'column');

            $pair = "$col $op :$col";
            $pairs []= $pair;
        }

        $sPairs = join(" AND ", $pairs);

        if ($prependWhere && $sPairs) {
            $sPairs = ' WHERE ' . $sPairs;
        }

        $whereSql = OTypeString::create('sql', $sPairs . $append);
        $whereSql->u_fill($map);

        return $whereSql;
    }



    // Meta Database/Table Methods
    //-----------------------------------------------------------------

    function u_get_databases() {

        $this->ARGS('', func_get_args());

        // TODO: verify key exists
        $dbs = Tht::getAllConfig()['databases'];

        return OList::create(array_keys(unv($dbs)));
    }

    function u_get_tables() {

        $this->ARGS('', func_get_args());

        if ($this->driver == 'sqlite') {
            $sqlString = new \o\SqlTypeString (
                "SELECT name FROM sqlite_master WHERE type='table'"
            );
            return v($this->u_select_rows($sqlString))->u_column('name');
        }
        else {

            return $this->fetchTableNames();
        }
    }

    function fetchOneCol($sth) {
        $rows = [];

        while (true) {
            $row = $sth->fetch(\PDO::FETCH_NUM);
            if (!$row) { break; }
            $rows []= $row[0];
        }

        return OList::create($rows);
    }

    function fetchTableNames($tableName = '') {

        $likeSql = $tableName ? " LIKE '$tableName'" : '';
        $sth = $this->query(
            new \o\SqlTypeString ('SHOW TABLES' . $likeSql)
        );

        return $this->fetchOneCol($sth);
    }

    function fetchColNames($tableName) {

        $sth = $this->query('SHOW COLUMNS FROM ' . $tableName);

        return $this->fetchOneCol($sth);
    }

    function u_table_exists($tableName) {

        $this->ARGS('s', func_get_args());

        $tableName = $this->validateName($tableName, 'table');

        $this->connect();

        if ($this->driver == 'sqlite') {
            $sqlString = new \o\SqlTypeString (
                "SELECT name FROM sqlite_master WHERE type='table' AND name = {0} LIMIT 1"
            );
            $sqlString->u_fill($tableName);
            $rows = $this->u_select_rows($sqlString);
            return count($rows) == 1;
        }
        else {
            $tables = $this->fetchTableNames($tableName);
            return count($tables) == 1;
        }
    }

    function u_get_columns ($tableName){

        $this->ARGS('s', func_get_args());

        $tableName = $this->validateName($tableName, 'table');

        if ($this->driver == 'sqlite') {
            $sqlString = new \o\SqlTypeString ("PRAGMA table_info(" . $tableName . ")");
            return $this->u_select_rows($sqlString);
        }
        else {
            return OList::create(
                $this->fetchColNames($tableName)
            );
        }
    }

    function u_x_danger_drop_table($table)  {

        $this->ARGS('s', func_get_args());

        if (!$this->u_table_exists($table)) { return false; }

        $table = $this->validateName($table, 'table');
        $sql = "DROP TABLE IF EXISTS $table";

        $this->query(
            new \o\SqlTypeString ($sql)
        );

        return true;
    }

    function u_create_table($table, $cols)  {

        $this->ARGS('sm', func_get_args());

        if ($this->u_table_exists($table)) { return false; }

        $table = $this->validateName($table, 'table');
        $sql = "CREATE TABLE $table (\n";

        $aCols = [];

        foreach (unv($cols) as $name => $type) {
            $name = $this->validateName($name, 'column');
            $type = $this->validateArg($type, 'column type');

            $aCols []= "$name $type";
        }

        $sql .= implode(",\n", $aCols);
        $sql .= ')';

        $this->query(
            new \o\SqlTypeString ($sql)
        );

        return true;
    }

    function u_create_index($table, $col)  {

        $this->ARGS('ss', func_get_args());

        $cols = $this->fetchColNames();
        if (!in_array($col, $cols)) {
            $this->error("Column `$col` does not exist in table `$table`");
        }

        $table = $this->validateName($table, 'table');
        $col = $this->validateName($col, 'column');

        $sql = "CREATE INDEX i_{$table}_{$col} ON $table ($col)";

        $this->query(
            new \o\SqlTypeString ($sql)
        );

        return $this;
    }



    // CRUD Commands
    //-----------------------------------------------------------------


    function u_select_rows ($sql, $whereMap = null) {

        $this->ARGS('*m', func_get_args());

        if ($whereMap) {
            $whereSql = $this->mapToWhereSql($whereMap, true);
            $sql->appendTypeString($whereSql);
        }

        $sth = $this->query($sql);
        $rows = [];
        $colTypes = [];

        while (true) {
            $row = $sth->fetch(\PDO::FETCH_ASSOC);

            if (!$row) { break; }

            if (!$colTypes) {
                $colTypes = $this->getColTypes($sth, $row);
            }
            $row = $this->convertColTypes($row, $colTypes);

            $rows []= OMap::create($row);
        }

        return OList::create($rows);
    }

    function u_select_row ($sql, $whereMap = null) {

        $this->ARGS('*m', func_get_args());

        if ($whereMap) {
            $whereSql = $this->mapToWhereSql($whereMap, true);
            $sql->appendTypeString($whereSql);
        }

        $rows = $this->u_select_rows($sql);

        if (count($rows) > 1) {
            $this->error(
                '`selectRow` got ' . count($rows) . " rows.  Expected only one."
                . 'Try: Add `LIMIT 1` clause to the end of your query.'
            );
        }

        $row = isset($rows[ONE_INDEX]) ? $rows[ONE_INDEX] : '';

        return $row;
    }

    function filterInsertVals($rawVals) {

        $sqlVals = [];

        foreach ($rawVals as $k => $val) {
            if (is_object($val)) {
                 $sqlVals[$k] = $val->u_to_sql_string();
            }
            else {
                $sqlVals[$k] = $val;
            }
        }

        return $sqlVals;
    }

    function u_insert_row ($tTable, $fields){

        $this->ARGS('sm', func_get_args());

        $table = $this->validateName($tTable, 'table');

        $cols = [];
        $vals = [];

        foreach ($fields as $col => $value) {
            $col = $this->validateName($col, 'column');
            $cols []= $col;
            $vals []= ':' . $col;
        }

        $sCols = join(", ", $cols);
        $sVals = join(", ", $vals);

        $sql = "INSERT INTO $table ($sCols) VALUES ($sVals)";

        $sqlString = new \o\SqlTypeString ($sql);

        $fields = $this->filterInsertVals($fields);
        $sqlString->u_fill(v($fields));

        $this->query($sqlString);

        return ONothing::create('Db.insertRow');
    }

    function u_update_row ($tTable, $vals, $whereSql) {

        $this->ARGS('sm*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        $whereSql = $this->assertLimitOne('updateRow', $tTable, $whereSql);

        return $this->update($tTable, $vals, $whereSql);
    }

    function u_update_rows ($tTable, $vals, $whereSql) {

        $this->ARGS('sm*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        return $this->update($tTable, $vals, $whereSql);
    }

    // Make sure updateRow and deleteRow do not affect more than one row.
    function assertLimitOne($method, $tTable, $whereSql) {

        $table = $this->validateName($tTable, 'table');

        if ($this->driver == 'sqlite') {

            // Have to run a select query, which being a local file, shouldn't be too bad?

            $oneQueryKey = $table . '/' . $whereSql->u_raw_string();
            if (isset($this->oneQueryIsSafe[$oneQueryKey])) {
                return $whereSql;
            }

            $select = OTypeString::create('sql', "select count(*) as numRows from $table WHERE")
                ->appendTypeString($whereSql);

            $limitTwo = OTypeString::create('sql', ' LIMIT 2');
            $fullSelect = $select->appendTypeString($limitTwo);

            $row = $this->u_select_row($fullSelect);

            if ($row['numRows'] > 1) {
                $this->error("Query for `$method` will affect more than one row.");
            }

            $this->oneQueryIsSafe[$oneQueryKey] = true;

            return $whereSql;
        }
        else {
            $limitOne = OTypeString::create('sql', ' LIMIT 1');
            return $whereSql->appendTypeString($limitOne);
        }
    }

    function update ($tTable, $vals, $whereSql) {

        $table = $this->validateName($tTable, 'table');
        $where = OTypeString::getUntyped($whereSql, 'sql', true);

        // create SET expression
        $cols = [];
        $sets = [];

        foreach (unv($vals) as $col => $value) {
            $col = $this->validateName($col, 'column');
            $set = "$col = :$col";
            $sets []= $set;
        }

        $sSets = join(", ", $sets);

        $params = array_merge(unv($vals), unv($whereSql->u_params()));

        $params = $this->filterInsertVals($params);

        $sql = "UPDATE $table SET $sSets WHERE $where";

        $sqlString = new \o\SqlTypeString ($sql);

        $sqlString->u_fill(v($params));

        $this->query($sqlString);

        return ONothing::create('Db.update');
    }

    function u_delete_row ($tTable, $whereSql) {

        $this->ARGS('s*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        $whereSql = $this->assertLimitOne('deleteRow', $tTable, $whereSql);

        $this->delete($tTable, $whereSql);

        return ONothing::create('Db.deleteRow');
    }

    function u_delete_rows ($tTable, $whereSql) {

        $this->ARGS('s*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        $this->delete($tTable, $whereSql);

        return ONothing::create('Db.deleteRows');
    }

    function delete ($tTable, $whereSql) {

        $where = OTypeString::getUntyped($whereSql, 'sql', true);
        $table = $this->validateName($tTable, 'table');

        $plainSql = "DELETE FROM $table WHERE";
        $sqlString = new \o\SqlTypeString ($plainSql);

        $sqlString->appendTypeString($whereSql);

        return $this->query($sqlString);
    }

    function u_count_rows ($tTable, $whereSql) {

        $this->ARGS('s*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        $table = $this->validateName($tTable, 'table');

        $plainSql = "SELECT count(*) AS numRows FROM $table WHERE";
        $sqlString = new \o\SqlTypeString ($plainSql);

        $sqlString->appendTypeString($whereSql);

        $row = $this->u_select_row($sqlString);

        return $row['numRows'];
    }



    // Core Query Methods
    //-----------------------------------------------------------------

    function u_run_query ($sqlString) {

        $this->ARGS('*', func_get_args());

        $sth = $this->query($sqlString);

        return Tht::module('Php')->u_wrap_object($sth);
    }

    function query ($sqlTypeString) {

        $sql = OTypeString::getUntyped($sqlTypeString, 'sql', true);

        Tht::module('Perf')->u_start('Db.query', $sql);
        Tht::module('Meta')->u_no_template_mode();

        $sql = $this->buildQuery($sql);
        $params = $sqlTypeString->u_params();

        $sth = $this->executeQuery($sql, $params);

        Tht::module('Perf')->u_stop();

        return $sth;
    }

    function buildQuery($sql) {

        // Convert to placeholder {0} or {param} syntax
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

        if (!trim($sql)) {
            $this->error('Empty SQL query');
        }

        return $sql;
    }

    function initParams($sql, $params) {

        $params = $this->unwrapParams($params);

        // make sure params match placeholders
        preg_match_all('/\:([a-zA-Z0-9_]+)\b/m', $sql, $match);

        $filteredParams = [];

        foreach ($match[1] as $ph) {

            if (!isset($params[$ph])) {
                $this->error("Missing placeholder value for `$ph`.");
            }

            $filteredParams[$ph] = $params[$ph];
        }

        return $filteredParams;
    }

    // Unwrap THT values
    function unwrapParams($params) {

        $params = unv($params);
        $unwrappedParams = [];

        foreach ($params as $k => $v) {

            if (is_numeric($k)) {
                $unwrappedParams['param_' . $k] = unv($params[$k]);
            } else {
                $unwrappedParams[$k] = unv($params[$k]);
            }
        }

        return $unwrappedParams;
    }

    function executeQuery($sql, $params) {

        $this->lastStatus['insertId'] = -1;
        $this->lastStatus['numRows'] = -1;

        $sth = null;

        $startTime = Tht::module('Perf')->u_now();

        // Disabled for now.
        // Security::preventDestructiveSql($sql);

        try {

            $dbh = $this->connect();
            $sth = $dbh->prepare($sql);

            if (!count($params)) {
                $sth->execute();
            }
            else {
                $filteredParams = $this->initParams($sql, $params);
                $sth->execute($filteredParams);
            }

            // Not really used, since support across DBs isn't consistent
            // https://www.php.net/manual/en/pdostatement.rowcount.php
            $this->lastStatus['numRows'] = $sth->rowCount();

            $this->lastStatus['fnGetInsertId'] = function ($s = null) use ($dbh) {
                return $dbh->lastInsertId($s);
            };
        }
        catch (\PDOException $e) {
            $this->error('DB: ' . $e->getMessage() . ' Query: ' . $sql);
        }

        $endTime = Tht::module('Perf')->u_now();
        $durationMs = $endTime - $startTime;
        $durationSecs = floor($durationMs / 1000);

        if ($durationSecs >= Tht::getConfig('logSlowDbQuerySecs')) {
            $msg = $durationMs . " secs\n";
            $msg .= "  SQL: " . $sql . "\n";
            $msg .= "  Params: " . json_encode($params);
            Tht::module('File')->u_log('slowDbQueries.txt', $msg);
        }

        return $sth;
    }

    function getColTypes($sth, $row) {

        $colToType = [];

        $i = 0;
        foreach ($row as $colName => $colValue) {

            // Unfortunately, native_type doesn't really normalize the
            // value as you would expect.  The types aren't really native PHP types,
            // so we have to do some fuzzy matching.
            $meta = $sth->getColumnMeta($i);
            $type = strtolower($meta['native_type']);

            // Note: sqlite does not have date/time column types
            if (preg_match('/date|time/', $type) || preg_match('/date$/i', $colName)) {
                $colToType[$colName] = 'date';
            }
            else if (preg_match('/^(long|tiny|bit|short|int)/', $type)) {
                $colToType[$colName] = 'int';
            }
            else if (preg_match('/double|float|decimal|numeric/', $type)) {
                $colToType[$colName] = 'float';
            }
            else {
                // Basically leave it as is, which will be a string.
                $colToType[$colName] = 'auto';
            }

            $i += 1;
        }

        return $colToType;
    }

    function convertColTypes($row, $colTypes) {

        foreach ($row as $colName => $colValue) {

            $type = $colTypes[$colName];

            if ($type == 'date') {
                $row[$colName] = Tht::module('Date')->u_create($colValue);
            }
            else if ($type == 'int') {
                $row[$colName] = intval($colValue);
            }
            else if ($type == 'float') {
                $row[$colName] = floatval($colValue);
            }
            else if (is_null($colValue)) {
                $row[$colName] = '';
            }
        }

        return $row;
    }
}
