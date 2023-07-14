<?php
/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		David Akopyan <davids@efumo.lv>
 * @copyright	Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		2
 */

// ------------------------------------------------------------------------

/**
 * Database class
 * open connection with DB
 * close connections, etc...
 * 16.02.2010
 */
class Db
{
    /** @var PDO */
    public $connect_id;
    public $type;
    public $query_id;
    public $remote_connect_id;
    public $local_connect_id;

    /**
     * Constructor
     */
    public function __construct($database_type = "mysql")
    {
        $this->type = $database_type;
    }

    /**
     * Open connection with selected database
     *
     * @param string    db name
     * @param string    db host
     * @param string    db user name
     * @param string    db password
     *
     * @return PDO
     */
    public function open($database = "{database}", $host = "{host}", $user = "{user}", $password = "{password}")
    {
        try {
            $connection = new PDO(
                'mysql:host=' . $host . ';dbname=' . $database . '',
                $user,
                $password,
                array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION)
            );

            if ($connection) {
                $this->setConnectionOptions($database, $connection);

                $this->connect_id = $connection;
                $query = new query($this, "SET NAMES 'utf8'");
            }
        } catch (PDOException $e) {
            $connection = null;
            $this->connect_id = $connection;
            showError("Failed to connect to database", $e->getMessage(), $e->getCode(), array('exception' => $e));
        }

        return $this->connect_id;
    }

    /**
     * Lock table
     *
     * @param string    table name
     * @param enum    maybe 'read' or 'write'
     */
    public function lock($table, $mode = "write")
    {
        $query = new query($this, "LOCK TABLES " . $table . " " . $mode);
        $result = $query->result;
        return $result;
    }

    /**
     * Unlock tables
     * unlocks any and all tables which this process locked
     *
     */
    public function unlock()
    {
        $query = new query($this, "UNLOCK TABLES");
        $result = $query->result;
        return $result;
    }

    /**
     * Function returns the next available id for $sequence, if it's not
     * already defined, the first id will start at 1.
     * This function will create a table for each sequence called
     * '{sequence_name}_seq' in the current database.
     *
     * @param string    sequence
     */
    public function nextid($sequence)
    {
        $esequence = ereg_replace("'", "''", $sequence) . "_seq";
        $nextid = null;

        try {
            $checkTable = $this->connect_id->query("SELECT 1 FROM " . $esequence . " LIMIT 1");
            $tableExist = $checkTable->execute();
        } catch (PDOException $e) {
            $tableExist = false;
        }

        if ($tableExist) {
            try {
                $query = new query($this, "REPLACE INTO " . $esequence . " VALUES ('', nextval + 1)");

                if ($query->result) {
                    $nextid = (int)$this->connect_id->lastInsertId();
                }
            } catch (PDOException $e) {
                $nextid = null;
            }
        } else {
            try {
                $query = new query($this, "CREATE TABLE " . $esequence . " ( seq char(1)
									DEFAULT '' NOT NULL, nextval bigint(20) unsigned NOT NULL auto_increment,
									PRIMARY KEY (seq), KEY nextval (nextval) )");

                $query->query($this, "REPLACE INTO " . $esequence . " VALUES ( '', nextval+1 )");
                if ($query->result) {
                    $nextid = (int)$this->connect_id->lastInsertId();
                }
            } catch (PDOException $e) {
                $nextid = 0;
            }
        }
        return $nextid;
    }

    /**
     * Get last insert id
     *
     */
    public function get_insert_id()
    {
        if(!$this->connect_id) {
            logWarn("DB connection error!" . PHP_EOL);
        }

        $ins_id = (int)$this->connect_id->lastInsertId();

        return $ins_id;
    }

    /**
     * Return mysql error
     *
     */
    public function error()
    {
        $message = $this->connect_id->errorInfo[1] . ": " . $this->connect_id->errorInfo[2];
        $this->connect_id = null;
        return $message;
    }

    /**
     * Close db connection
     * Closes the database connection and frees any query results left.
     *
     */
    public function close()
    {
        if ($this->query_id && is_array($this->query_id)) {
            while (list($key, $val) = each($this->query_id)) {
                $this->query_id[$key] = null;
            }
        }
        $this->connect_id = null;
        $result = true;

        return $result;
    }

    /**
     * Function used by the constructor of query. Notifies the
     * this object of the existance of a query_result for later cleanup
     * internal function, don't use it yourself.
     *
     * @param mix        query id
     * @param string    query
     * @param int        seconds
     */
    public function addquery($query_id, $query, $time = '')
    {
        $this->query_id[] = $query_id;
        $this->queries[] = $query;
        $this->qTimes[] = $time;
    }

    /**
     * @param array $params
     *      array _result
     *            bool _continue
     *            string _error
     *      string tableOriginal
     *        string tableCopy
     *        [bool copyData = true]
     * @return array $result
     */
    public function helperCopyTable($params)
    {
        $result = $params['_result'];

        if (!isset($result['currentCopyTable'])) {
            $result['currentCopyTable'] = array();
            $result['copyTables'] = array();
        }

        if (!empty($result['currentCopyTable'])) {
            $result['copyTables'][] = $result['currentCopyTable'];
            $result['currentCopyTable'] = array();
        }

        $result['currentCopyTable']['tableOriginal'] = $params['tableOriginal'];
        $result['currentCopyTable']['tableCopy'] = $params['tableCopy'];
        $result['currentCopyTable']['copyData'] = (isset($params['copyData']) && $params['copyData'] === false) ? false : true;

        // Delete table copy
        if ($result['_continue'] === true) {
            $result['currentCopyTable']['queryDropTableCopy'] = 'DROP TABLE IF EXISTS ' . $result['currentCopyTable']['tableCopy'];
            $query = new query($this, $result['currentCopyTable']['queryDropTableCopy']);

            if (!$query->result) {
                $result['_continue'] = false;
            }
        }

        // Create table copy
        if ($result['_continue'] === true) {
            $result['currentCopyTable']['queryCreateTableCopy'] = 'CREATE TABLE ' . $result['currentCopyTable']['tableCopy']
                . ' LIKE ' . $result['currentCopyTable']['tableOriginal'];
            $query = new query($this, $result['currentCopyTable']['queryCreateTableCopy']);

            if (!$query->result) {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to create table copy';
            }
        }

        // Copy data
        if ($result['_continue'] === true && $result['currentCopyTable']['copyData'] === true) {
            $result['currentCopyTable']['queryCopyData'] = 'INSERT ' . $result['currentCopyTable']['tableCopy']
                . ' SELECT * FROM ' . $result['currentCopyTable']['tableOriginal'];
            $query = new query($this, $result['currentCopyTable']['queryCopyData']);

            if ($query->result !== true) {
                $result['_continue'] = false;
                $result['_error'] = 'Failed to copy data';
            }
        }

        // Return
        return $result;
    }
    /**
     * Set connection options
     *
     * @param string $database
     * @param PDO $connection
     * @return void
     */
    private function setConnectionOptions($database, $connection)
    {
        /** @var config $cfg */

        $cfg = &loadLibClass('config');

        if ($database == $cfg->get('db_db')) {
            $this->remote_connect_id = $connection;
        }

        if ($database == $cfg->get('db_db_local')){
            $this->local_connect_id = $connection;
        }

        if (!$cfg->get('db_db_local')){
            $this->local_connect_id = $connection;
        }
    }
}

;

// ------------------------------------------------------------------------

/**
 * Query class
 * work with queries and results
 * 16.02.2010
 */
class query
{

    public $result;
    public $queryResult;
    public $row;
    public $rArray = array();

    /**
     * Constructor of the query object.
     * executes the query, notifies the db object of the query result to clean
     * up later.
     *
     * @param object    db connector
     * @param string    query string
     * @param bool        die on sql error or not
     * @return void
     */
    public function query(db &$db, $query = "", $die = true)
    {
        if ($query) {

            // query not called as constructor therefore there may be something to clean up.
            if ($this->result) {
                $this->free();
            }

            $wk = &loadLibClass('workTime');
            $wk->mark("query-start");

            try {

                $connectionId = null;

                /** @var config $cfg */
                $cfg = loadLibClass('config');

                if ($cfg->get('whiteLabel') && $cfg->get('ownContentTables')) {

                    $tableName = $this->getTableName($query);

                    $isContentTable = $this->isContentTable($tableName);

                    if ($isContentTable) {

                        $connectionId = $db->local_connect_id;

                    } else {

                        $connectionId = $db->remote_connect_id;
                    }
                }

                if (!$connectionId) {
                    $connectionId = $db->connect_id;
                }


                $connection = $connectionId;
                /** @var PDOStatement $newQuery */
                $newQuery = $connection->prepare($query);

                // TODO: remove after debug!!!
                // log 'sended' change

//                $q = strtolower($query);
//
//                if(strpos($q, 'update ') !== false && strpos($q, 'sended') !== false && strpos($q, 'a_log_db_query') === false) {
//
//                    $table = 'a_log_db_query';
//
//                    // trace
//
//                    $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
//
//                    $traceArr = array();
//
//                    foreach ($trace as $trace) {
//
//                        $traceArr[] = array(
//                            'class' => $trace['class'],
//                            'function' => $trace['function'],
//                        );
//                    }
//
//                    $data = array(
//                        'query_dt' => date('Y-m-d H:i:s', time()),
//                        'query' => $query,
//                        'call_trace' => json_encode($traceArr),
//                    );
//
//                    saveValuesInDb($table, $data);
//                }
                // END of debug



                $executed = $newQuery->execute();
                $this->result = $executed;
                $this->queryResult = $newQuery;

                $wk->mark("query-end");
                if ($wk->elapsedTime("query-start", "query-end") > 1) {
                    logWarn("Slow QUERY: " . $query);
                }
                $db->addquery($this->result, $query, $wk->elapsedTime("query-start", "query-end"));

            } catch (PDOException $e) {

//				$db->connect_id = $e;

                if (DEBUG) {

                    $err = array(
                        'result' => 'SQL error!',
                        'query' => $query,
                        'dbError' => $db->error(),
                        'PDOException' => array(
                            'message' => $e->getMessage(),
                            'code' => $e->getCode(),
                            'fileLine' => $e->getFile() . ' Line: ' . $e->getLine(),
                            'trace' => $e->getTrace(),
                        ),
                    );

//					pre("SQL error! Query: " . PHP_EOL . $query . PHP_EOL . " Error:" . $db->error());

                    pre($err);
                }

                logWarn("SQL error! Query: " . PHP_EOL . $newQuery->queryString . PHP_EOL . 'Exception message: ' . $e->getMessage() . PHP_EOL . 'Exception code: ' . $e->getCode() . PHP_EOL);
                exit;

                if ($die) {
                    //showError("<b>SQL error!<b>" . '<br><p style="color: blue;">' . $query . '</p><br>' . " Error: " . $db->error());
                }
            }
        }
    }

    /**
     * Gets the next row for processing with $this->field function later.
     *
     */
    public function getrow()
    {
        $this->row = $this->result ? $this->queryResult->fetch(PDO::FETCH_ASSOC) : 0;
        return $this->row;
    }

    /**
     * Get one field value
     *
     */
    public function getOne()
    {
        $return = ($this->result ? $this->queryResult->fetchColumn() : false);
        $this->free();
        return $return;
    }

    /**
     * Get array from DB
     */
    public function getArray($field = '', $simple = true)
    {
        while ($r = $this->getrow()) {
            if ($field && isset($r[$field])) {
                if ($simple) {
                    $this->rArray[$r[$field]] = $r;
                } else {
                    $this->rArray[] = $r[$field];
                }
            } else {
                $this->rArray[] = $r;
            }
        }
        $this->free();
        return $this->rArray;
    }

    /**
     * Get the value of the field with name $field
     * in the current row
     *
     * @param string    field name
     */
    public function field($field)
    {
        $result = $this->row[$field];
        if (!get_magic_quotes_gpc()) {
            $result = stripslashes($result);
        }
        return $result;
    }

    /**
     * Return the name of field number $fieldnum
     * only call this after query->getrow() has been called at least once
     *
     * @param string    field number
     */
    public function fieldname($fieldnum)
    {
        $meta = $this->queryResult->getColumnMeta($fieldnum);
        $result = $meta['name'];
        return $result;
    }

    /**
     * Return the current row pointer to the first row
     * (CAUTION: other versions may execute the query again!! (e.g. for oracle))
     *
     */
    public function firstrow()
    {
        $row = $this->getrow();
        $this->row = $row ? $row : [];

        if (!get_magic_quotes_gpc()) {
            $this->row = array_map('stripslashes', $this->row);
        }
        return $this->row;
    }

    /**
     * Free the mysql result tables
     *
     */
    public function free()
    {
        return $this->queryResult->closeCursor();
    }

    /**
     * Get query num rows
     *
     */
    public function num_rows()
    {
        $result = $this->queryResult->rowCount();
        return $result;
    }

    /**
     * Get affected rows
     *
     */
    public function affected_rows()
    {
        $result = $this->queryResult->rowCount();
        return $result;
    }

    /**
     * Get table name from query string
     * @param string $query
     * @return string
     *
     */
    private function getTableName($query)
    {
        $tableName = '';

        $removeWhiteSpace = trim(preg_replace('/\s\s+/', ' ', $query));

        if (preg_match_all('/((FROM|INTO|UPDATE) (.*))/', $removeWhiteSpace, $matches)) {

            $tables = array_unique($matches[3]);

            if (!empty($tables[0])) {
                $tableName = explode(' ', $tables[0]);
                if (!empty($tableName[0])) {
                    $tableName = $tableName[0];
                }
            }
        }

        return $tableName;
    }

    /**
     * Check if table is content table (ad_*)
     * @param string $tableName
     * @return bool
     */
    private function isContentTable($tableName)
    {
        $result = false;

        if (strpos($tableName, 'ad_') !== false) {
            $result = true;
        }

        return $result;
    }
}

;

?>