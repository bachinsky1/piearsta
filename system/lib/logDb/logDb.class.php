<?php

/**
 * LogDb class
 * logs data to passed table and allows to fetch log records by passed conditions
 */
class LogDb
{
    public $db;
    public $cfg;
    public $tableGroup = 'logs';

    public function __construct()
    {
        $this->db = &loadLibClass('db');
        $this->cfg = &loadLibClass('config');
    }

    // Logs $data to $table dbTable
    /**
     * @param $table
     * @param $data
     * @return bool|string
     */
    public function log($table, $data)
    {
        $table = $this->cfg->getDbTable($this->tableGroup, $table);

        return saveValuesInDb($table, $data);
    }

    // Searches and returns log records
    /**
     * @param $table
     * @param $params
     * @return array|null
     */
    public function getLogBy($table, $params)
    {
        $table = $this->cfg->getDbTable($this->tableGroup, $table);

        $conditions = ' WHERE 1 ';
        foreach ($params as $field => $cond) {
            $conditions .= " AND " . $field . " " . $cond['operator'] . " " . $cond['value'] . " ";
        }

        $dbQuery =  "SELECT * FROM " . $table . $conditions;
        $query = new query($this->db, $dbQuery);

        $result = array();

        if($query->num_rows()) {

            while($row = $query->getrow()) {
                $result[] = $row;
            }
            return $result;
        }

        return null;
    }

}

?>