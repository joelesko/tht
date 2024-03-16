<?php

namespace o;

class u_Db extends OStdModule {

    private $connectionCache = [];
    private $dbId = 'default';
    private $driver = '';
    private $server = '';
    private $lastStatus = [ 'numRows' => -1, 'fnGetInsertId' => null ];
    private $oneQueryIsSafe = [];

    private function connect () {

        Tht::module('Meta')->u_fail_if_template_mode();

        $dbId = $this->dbId;

        if (isset($this->connectionCache[$dbId])) {
            return $this->connectionCache[$dbId];
        }

        Tht::module('Perf')->u_start('db.connect', $dbId);

        $dbConfig = $this->u_get_config($dbId);

        $this->checkConfig($dbConfig, ['driver']);

        $this->driver = $dbConfig['driver'];
        $this->server = $dbConfig['server'];

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

            $this->checkConfig($dbConfig, ['database', 'server', 'username', 'password']);

            // Change localhost to 127.0.0.1
            // See https://stackoverflow.com/questions/21046672/pdo-not-working-with-port
            $server = ($dbConfig['server'] == 'localhost') ? '127.0.0.1' : $dbConfig['server'];

            $dsn = $dbConfig['driver'] . ':';
            $dsn .= 'host=' . $server . '; ';
            $dsn .= 'dbname=' . $dbConfig['database'] . '; ';
            if (isset($dbConfig['port'])) {
                $dsn .= 'port=' . $dbConfig['port'] . '; ';
            }
            $dsn .= 'charset=UTF8;';

            $atts = [];
            if ($dbConfig['driver'] == 'mysql') {
                $atts = $this->initMysqlAtts($dbConfig);
            }

            $dbh = new \PDO(
                $dsn,
                $dbConfig['username'],
                $dbConfig['password'],
                $atts,
            );
        }

        $dbh->setAttribute(\PDO::ATTR_PERSISTENT, false);
        $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

        $this->connectionCache[$dbId] = $dbh;

        Tht::module('Perf')->u_stop();

        return $dbh;
    }

    function checkConfig($conf, $fields) {
        foreach ($fields as $f) {
            // Password can be empty, but everything else should be defined.
            if (!isset($conf[$f]) || ($f != 'password' && !$conf[$f])) {
                $this->error("Missing field `$f` in config for database `" . $this->dbId . "`. Try: Update `config/app.local.jcon`");
            }
        }
    }

    // See https://www.php.net/manual/en/ref.pdo-mysql.php
    function initMysqlAtts($dbConfig) {

        $atts = [];

        // Security: don't allow multiple statements in a query by default
        $atts[\PDO::MYSQL_ATTR_MULTI_STATEMENTS] = false;

        // Enable network compression for databases thare are not on localhost
        if ($dbConfig['server'] != '127.0.0.1') {
            $atts[\PDO::MYSQL_ATTR_COMPRESS] = true;
        }

        // Map string keys to PHP numeric constants
        $attKeys = [
            'sslKey'    => \PDO::MYSQL_ATTR_SSL_KEY,
            'sslCert'   => \PDO::MYSQL_ATTR_SSL_CERT,
            'sslCa'     => \PDO::MYSQL_ATTR_SSL_CA,
            'sslCaPath' => \PDO::MYSQL_ATTR_SSL_CAPATH,
            'sslCipher' => \PDO::MYSQL_ATTR_SSL_CIPHER,
            'sslVerifyServerCert' => \PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT,
            'allowMultiStatements' => \PDO::MYSQL_ATTR_MULTI_STATEMENTS,
        ];

        foreach ($attKeys as $k => $v) {
            if (isset($dbConfig[$k])) {
                $atts[$v] = $dbConfig[$k];
            }
        }

        return $atts;
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

        $topConfig = Tht::getTopConfig('databases', $dbId);
        if (!$topConfig['server']) { $topConfig['server'] = 'localhost'; }

        return OMap::create($topConfig);
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

    // Convert arguments like { 'value >=': 5 } to 'where value >= 5'
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

        foreach ($map as $col => $value) {
            $col = trim($col);
            $op = '=';
            preg_match('/ (>|<|>=|<=|!=|in)$/', $col, $m);
            if ($m && $m[1]) {
                $op = $m[1];
                $map->u_remove($col);
                $col = preg_replace('/\s.+?$/', '', $col);
                $map[$col] = $value;
            }

            $col = $this->validateName($col, 'column');

            if ($op == 'in') {
                $pair = "$col in (:$col)";
            }
            else {
                $pair = "$col $op :$col";
            }

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
            return v($this->u_select_rows($sqlString))->u_get_column('name');
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

        $sth = $this->query(new SqlTypeString('SHOW COLUMNS FROM ' . $tableName));

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

    // function u_get_columns ($tableName){

    //     $this->ARGS('s', func_get_args());

    //     $tableName = $this->validateName($tableName, 'table');

    //     if ($this->driver == 'sqlite') {
    //         $sqlString = new \o\SqlTypeString ("PRAGMA table_info(" . $tableName . ")");
    //         return $this->u_select_rows($sqlString);
    //     }
    //     else {
    //         // return OList::create(
    //         //     $this->fetchColNames($tableName)
    //         // );

    //         return OMap::create(
    //             $this->getColThtTypes($tableName)
    //         );
    //     }
    // }

    function getTableInfo_Mysql($table) {

        // Field       Type                    Null     Key     Default      Extra
        // group_id    bigint(20) unsigned     NO       PRI     NULL         auto_increment
        // slug        varchar(255)            NO       MUL     NULL

        $rows = $this->u_select_rows(new SqlTypeString("describe $table"));

        $info = [];
        foreach ($rows as $row) {
            $colName = $row['Field'];
            unset($row['Field']);
            $colInfo = [];
            foreach ($row as $k => $v) {
                if ($k == 'Null') {
                    $v = $v == 'YES';
                }
                if ($k == 'Key') {
                    $map = ['PRI' => 'primary', 'MUL' => 'multiple', 'UNI' => 'unique'];
                    $v = isset($map[$v]) ? $map[$v] : $v;
                }
                $colInfo[lcfirst($k)] = $v;
            }
            $colInfo['thtType'] = $this->getThtType($colName, $colInfo['type']);
            $info[$colName] = OMap::create($colInfo);
        }

        // schema: "show create table $table"

        return $info;
    }

    function getTableInfo_Sqlite($table) {

        // cid  name      type           notnull  dflt_value  pk
        // ---  --------  -------------  -------  ----------  --
        // 0    AlbumId   INTEGER        1                    1
        // 1    Title     NVARCHAR(160)  1                    0
        // 2    ArtistId  INTEGER        1                    0

        $rows = $this->u_select_rows(new SqlTypeString("PRAGMA table_info($table)"));

        $info = [];
        foreach ($rows as $row) {
            $colName = $row['name'];
            unset($row['name']);
            unset($row['cid']);
            $colInfo = [];
            foreach ($row as $k => $v) {
                if ($k == 'notnull') {
                    $k = 'null';
                    $v = $v == 0;
                }
                else if ($k == 'dflt_value') {
                    $k = 'default';
                }
                else if ($k == 'pk') {
                    $k = 'key';
                    $v = 'primary';
                }
                else if ($k == 'type') {
                    $v = strtolower($v);
                }

                // TODO: auto_increment
                   // $schema = $this->u_select_rows(new SqlTypeString("select * from sqlite_schema where type=\"table\" and tbl_name = \"$table\""));
                  //  $info['_schema'] = $schema;
                //  CREATE TABLE users(id integer primary key autoincrement, primaryIndex varchar(255), parentIndex varchar(255), createDate unsigned int, level unsigned int, data text not null)

                $colInfo[lcfirst($k)] = $v;
            }
            $colInfo['thtType'] = $this->getThtType($colName, $colInfo['type']);
            $info[$colName] = OMap::create($colInfo);
        }

        return $info;
    }

    function u_get_columns($table) {

        $info = [];

        if ($this->driver == 'mysql') {
            $info = $this->getTableInfo_Mysql($table);
        }
        else if ($this->driver == 'sqlite') {
            $info = $this->getTableInfo_Sqlite($table);
        }

        foreach ($info as $colName => $colSchema) {
            $info[$colName]['validationRule'] = $this->getValidationRule($colName, $colSchema);
        }

        return OMap::create($info);
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

    function u_x_delete_all_rows($table)  {

        $this->ARGS('s', func_get_args());

        $table = $this->validateName($table, 'table');
        $sql = "TRUNCATE TABLE $table";

        $this->query(
            new \o\SqlTypeString ($sql)
        );

        return $this->lastStatus['numRows'];
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

        $cols = $this->fetchColNames($table);
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

    function u_get_server() {

        $this->ARGS('', func_get_args());

        $dbConfig = $this->u_get_config($this->dbId);

        return $dbConfig['server'];
    }

    function u_get_driver() {

        $this->ARGS('', func_get_args());

        $dbConfig = $this->u_get_config($this->dbId);

        return $dbConfig['driver'];
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
        $colThtTypes = [];

        while (true) {
            $row = $sth->fetch(\PDO::FETCH_ASSOC);

            if (!$row) { break; }

            if (!$colThtTypes) {
                $colThtTypes = $this->getColThtTypes($sth, $row);
            }
            $row = $this->convertToThtTypes($row, $colThtTypes);

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

        $sqlVals = OMap::create([]);

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

    function u_insert_row ($tTable, $fields) {

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
        $sqlString->u_fill($fields);

        $this->query($sqlString);

        return EMPTY_RETURN;
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

        $sqlString->u_fill($params);

        $this->query($sqlString);

        return EMPTY_RETURN;
    }

    function u_delete_row ($tTable, $whereSql) {

        $this->ARGS('s*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        $whereSql = $this->assertLimitOne('deleteRow', $tTable, $whereSql);

        $this->delete($tTable, $whereSql);

        return EMPTY_RETURN;
    }

    function u_delete_rows ($tTable, $whereSql) {

        $this->ARGS('s*', func_get_args());

        $whereSql = $this->mapToWhereSql($whereSql);

        $this->delete($tTable, $whereSql);

        return EMPTY_RETURN;
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

    function u_run_query ($sqlString) {

        $this->ARGS('*', func_get_args());

        $sth = $this->query($sqlString);

        return Tht::module('Php')->u_wrap_object($sth);
    }



    // Core Query Methods
    //-----------------------------------------------------------------

    function query ($sqlTypeString) {

        $sql = OTypeString::getUntyped($sqlTypeString, 'sql', true);

        Tht::module('Perf')->u_start('Db.query', $sql);
        Tht::module('Meta')->u_fail_if_template_mode();

        $sql = $this->buildQuery($sql);
        $params = $sqlTypeString->u_params();

        list($sql, $params) = $this->expandListParams($sql, $params);

        $sth = $this->executeQuery($sql, $params);

        Tht::module('Perf')->u_stop();

        return $sth;
    }

    function buildQuery($sql) {

        // Convert to placeholder :0 or :param_0 syntax
        $sql = preg_replace('/\{([0-9]+)\}/', ':param_$1', $sql);
        $sql = preg_replace('/\{([a-zA-Z0-9]+)\}/', ':$1', $sql);

        // Replace empty {} with :param_0, and so on.
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

    // Expand lists for "where in()" queries
    function expandListParams($sql, $params) {

        foreach ($params as $k => $v) {

            // TODO: Would prefer to accept only THT Lists objects, but value is getting
            // downgraded to PHP list via (I think) TypeString.appendTypeString. (happens when using whereMap)
            if (OList::isa($v) || is_array($v)) {

                $inPlaceholders = [];
                foreach ($v as $listNum => $listValue) {
                    $listKey = $k . '_list_' . $listNum;
                    $inPlaceholders []= ':' . $listKey;
                    $params[$listKey] = $listValue;
                }
                unset($params[$k]);

                $phSql = implode(', ', $inPlaceholders);
                $sql = str_replace(':' . $k, $phSql, $sql);
            }
        }

        return [$sql, $params];
    }

    // Convert THT values to PHP values
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

    function getColThtTypes($sth, $cols) {

        $colToType = [];

        $i = 0;
        foreach ($cols as $colName => $colValue) {

            // Unfortunately, native_type doesn't really normalize the
            // value as you would expect.  The types aren't really native PHP types,
            // so we have to do some fuzzy matching.
            $meta = $sth->getColumnMeta($i);
            $metaType = strtolower($meta['native_type']);

            $colToType[$colName] = $this->getThtType($colName, $metaType);

            $i += 1;
        }

        return $colToType;
    }

    function getThtType($colName, $sqlType) {

        $type = preg_replace('/\s*unsigned\s*/i', '', $sqlType);

        if (preg_match('/date|time/', $type) || preg_match('/date$/i', $colName)) {
            return 'date';
        }
        else if (preg_match('/^(long|tiny|bit|short|int|tinyint|smallint|mediumint|bigint)/', $type)) {
            return 'int';
        }
        else if (preg_match('/double|float|decimal|numeric/', $type)) {
            return 'float';
        }
        else if (preg_match('/^(tinyint\(1\)|bool)/', $type)) {
            return 'boolean';
        }
        else {
            // Basically leave it as is, which will be a string.
            return 'string';
        }
    }

    function convertToThtTypes($row, $colTypes) {

        foreach ($row as $colName => $sqlValue) {
            $type = $colTypes[$colName];
            $row[$colName] = $this->convertToThtType($type, $sqlValue);
        }

        return $row;
    }

    function convertToThtType($type, $sqlVal) {

        if ($type == 'date') {
            return Tht::module('Date')->u_create($sqlVal);
        }
        else if ($type == 'int') {
            return intval($sqlVal);
        }
        else if ($type == 'float') {
            return floatval($sqlVal);
        }
        else if ($type == 'boolean') {
            // Mysql is tinyint(1).  pgsql is strings.
            return $sqlVal == '1' || $sqlVal == 'true' || $sqlVal == 'on' || $sqlVal == 'yes';
        }
        else if (is_null($sqlVal)) {
            return '';
        }

        // string
        return $sqlVal;
    }

    function getIntRange($bytes, $unsigned) {


        $byteMap = [
            1 => 255,
            2 => 65535,
            3 => 16777215,
            4 => 4294967295,
            8 => pow(2, 64) -1,
        ];

        $num = $byteMap[$bytes];
        if ($unsigned) {
            return [0, $num];
        }
        else {
            return [-1 * ceil($num/2), floor($num/2)];
        }
    }

    function getBytesForSqlType($sqlType) {

        $type = preg_replace('/\s*unsigned\s*/i', '', $sqlType);

        $typeToBytes = [
            'tinyint'    => 1,
            'smallint'   => 2,
            'mediumint'  => 3,
            'int'        => 4,
            'integer'    => 4,
            'bigint'     => 8,

            'tinytext'   => 1,
            'text'       => 2,
            'mediumtext' => 3,
            'longtext'   => 4,

            'tinyblob'   => 1,
            'blob'       => 2,
            'mediumblob' => 3,
            'longblob'   => 4,
        ];

        return isset($typeToBytes[$type]) ? $typeToBytes[$type] : 2;
    }

    function getValidationRule($colName, $colSchema) {

        $type = $colSchema['thtType'];

        $size = 0;
        if (preg_match('/\((\d+)\)/', $colSchema['type'], $m)) {
            $size = intval($m[1]);
        }

        if ($type == 'date') {
            //     Password_timestamp: {
            // type: 'timestamp(6)',
            // null: false,
            // key: 'primary',
            // default: 'CURRENT_TIMESTAMP(6)',
            // extra: 'DEFAULT_GENERATED',
            // thtType: 'date',
            // validationRule: 'date'

            // date, dateTime, dateWeek, dateMonth, time
            return 'dateTime';
        }
        else if ($type == 'int') {
            if (!$size) {
                $size = $this->getBytesForSqlType($type);
            }

            $isUnsigned = preg_match('/unsigned/i', $colSchema['type']);
            $range = $this->getIntRange($size, $isUnsigned);
            return 'i|min:' . $range[0] . '|max:' . $range[1];

            return 'i';
        }
        else if ($type == 'float') {
            // TODO: how to validate this? (total digits)
            return 'f';
        }
        else if ($type == 'boolean') {
            return 'b';
        }
        else {

            $isMultiline = false;
            if (!$size) {
                $bytes = $this->getBytesForSqlType($type);
                $range = $this->getIntRange($bytes, false);
                $size = $range[1];
                if ($bytes >= 2) {
                    $isMultiline = true;
                }
            }

            // TODO: enum

            $validationType = $isMultiline ? 'ms' : 's';

            return $validationType . '|max:' . $size;
        }

        // TODO:
        //   max rules
        //   longtext/text = 'ms'
    }
}
