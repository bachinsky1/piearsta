<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		Jānis Šakars <janis.sakars@efumo.lv>
 * @copyright   Copyright (c) 2010, Efumo.
 * @link		http://adweb.lv
 * @version		1
 */
// ------------------------------------------------------------------------

class newsletterData extends Module_cms {

    private $dbTable;

    /**
     * Constructor
     */
    public function __construct() {

        parent :: __construct();
        $this->dbTable = "mod_newsletter";
    }

    public function showTable() {
        $table = array(
            "email" => array(
                'sort' => true,
                'title' => gLA("email", 'Email'),
                'function' => '',
                'fields' => array()
            ),
            "lang" => array(
                'sort' => true,
                'title' => gLA("lang", 'Language'),
                'function' => '',
                'fields' => array()
            ),
            "blocked" => array(
                'sort' => true,
                'title' => gLA("m_blocked", 'Blocked'),
                'function' => array(&$this, 'moduleEnableLink'),
                'fields' => array('id', 'blocked')
            ),
            "actions" => array(
                'sort' => false,
                'title' => gLA("actions"),
                'function' => array(&$this, 'moduleDeleteLink'),
                'fields' => array('id')
            )
        );

        // making filters
        $filterLanguage = mres(getP('filterLanguage'));
        $filterEmail = mres(getP('filterEmail'));

        $sqlWhere = array();

        if (!empty($filterLanguage)) {
            $sqlWhere[] = "`lang`='{$filterLanguage}'";
        }
        if (!empty($filterEmail)) {
            $sqlWhere[] = "`email` LIKE '%{$filterEmail}%'";
        }
		
        /**
         * Getting all information from DB about this module
         */
        $dbQuery = "SELECT SQL_CALC_FOUND_ROWS * FROM `" . $this->dbTable . "` ";
        if (!empty($sqlWhere)) {
            $dbQuery .= " WHERE " . implode(' AND ', $sqlWhere);
        }
        $dbQuery .= $this->moduleTableSqlParms("email", "ASC");

        $query = new query($this->db, $dbQuery);
		// Get row count
		$return['rCounts'] = $this->getTotalRecordsCount(false);
        // Create module table
        $this->cmsTable->createTable($table, $query->getArray(), true, 'id');
		$return['html'] = $this->cmsTable->returnTable;
		
        return $return;
    }

	/**
	 * Enable or disable
	 * 
	 * @param int/array 	news id
	 * @param bool 			enable/disable value
	 */
	public function enable($id, $value) {
		
		if (!is_numeric($id)) {
			$id = addSlashesDeep(jsonDecode($id));
		}
		
		if (!empty($id)) {
			$dbQuery = "UPDATE `" . $this->dbTable . "` SET `blocked` = '" . $value . "' WHERE " . (is_array($id) ? "`id` IN (" . implode(",", $id) . ")" : "`id` = '" . $id . "'");
			$query = new query($this->db, $dbQuery);
		}			
	}
	
    /**
     * Delete from DB
     *
     * @param int	id
     */
    public function delete($id) {

        if (!empty($id)) {
            deleteFromDbById($this->dbTable, $id);
        }
    }

}