<?php

/**
 * ADWeb - Content managment system
 *
 * @package		Adweb
 * @author		Rolands Eņģelis <rolands@efumo.lv>
 * @copyright   Copyright (c) 2012, Efumo.
 * @link		http://adweb.lv
 * @version		1
 */
// ------------------------------------------------------------------------

class bannersData extends Module_cms {

    protected	$dbTable;
    protected	$uploadFolder = 'banners/';
    protected 	$slots = array(
    	'startpage_promo' => 'Startpage Promo(500x550 px)',
    	'startpage_coupons' => 'Startpage Coupons(220x380 px)',
    	'startpage_big' => 'Startpage Big(940x100 px)',
    	'side_banner' => 'Side Banner(220x380 px)',	
    );

    /**
     * Constructor
     */
    public function __construct() {

        parent :: __construct();

        $this->dbTable = 'mod_banners';
        $this->name = "banners";
        
    }

    /**
     * Returns HTML as promo blocks table content     
     */
    public function showTable() {

        $table = array(
            'id' => array(
                'sort' => true,
                'title' => gLA('m_id','Id'),
            ),
            'lang' => array(
                'sort' => true,
                'title' => gLA('m_lang','language')
            ),
            'title' => array(
                'sort' => true,
                'title' => gLA('m_title','Title'),
                'function' => array(&$this, 'clear'),
                'fields' => array('title')
            ),
            'url' => array(
                'sort' => true,
                'title' => gLA('m_target_url','Target url'),
                'function' => array(),
                'fields' => array()
            ),
        	"created" => array(
        		'sort' => false,
        		'title' => gLA('m_created', 'Created'),
        		'function' => 'convertDate',
        		'fields'	=> array('created'),
        		'params' => array('d-m-Y H:i:s')
        	),
            'enabled' => array(
                'sort' => true,
                'title' => gLA('m_enable','Enable'),
                'function' => array(&$this, 'moduleEnableLink'),
                'fields' => array('id', 'enabled')
            ),
            'actions' => array(
                'sort' => false,
                'title' => gLA('m_actions','Actions'),
                'function' => array(&$this, 'moduleActionsLink'),
                'fields' => array('id')
            )
        );
	

        // SQL request for promo blocks
        $dbQuery = "SELECT *
        				FROM `" . $this->dbTable . "`";
        $query = new query($this->db, $dbQuery);

        // Set total count
        $return['rCounts'] = $query->num_rows();

        $dbQuery .= $this->moduleTableSqlParms('id', 'DESC');
        $query = new query($this->db, $dbQuery);

        // Create table
        $this->cmsTable->createTable($table, $query->getArray());
        $return['html'] = $this->cmsTable->returnTable;      

        return $return;
    }

    /**
     * Returns array of data for promo block
     * @param   $id AS int, default 0
     */
    public function edit($id = 0) {

        $data = array();
        $data['edit']['id'] = 0;

        if ($id) {
            $query = new query($this->db, "SELECT * FROM `" . $this->dbTable . "` WHERE `id` = " . mres($id));
            if ($query->num_rows()) {
                $data['edit'] = $query->getrow();
            }
        } 
               
        $data['langs'] = getSiteLangsByCountry(getDefaultCountry());
        $data['slots'] = $this->slots;
        $data['edit']['uploadFolder'] = $this->uploadFolder;

        return $data;
    }

    /**
     * Save/update promo block
     * @param   $id AS int - product id if exists, default 0 - new promo block
     * @param   $values AS json serialized array
     */
    public function save($id, $values) {

        // Decode data to get array; Add slashes as well
        $values = addSlashesDeep(jsonDecode($values));

        if (empty($values["image"])) {
        	$values["image"] = '';
        } 
        
        if ($id) {
			$dbQuery = "SELECT `image` FROM `" . $this->dbTable . "` WHERE `id` = " . mres($id);
			$query = new query($this->db, $dbQuery);
			$image = $query->getOne();
			
			if($image != '' && $image != $values["image"]){
				deleteFileFromFolder(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . $image);
			}
        } else {
        	$values["created"] = time();
        }

        $id = saveValuesInDb($this->dbTable, $values, $id);

        return $id;
    }

    /**
     * Delete existing row from table
     * @param   $id AS int 
     */
    public function delete($id) {

        if ($id) {
			//Find images and delete them from file
			$dbQuery = "SELECT `image` FROM `" . $this->dbTable . "` WHERE `id` = " . mres($id);
			$query = new query($this->db, $dbQuery);
			$image = $query->getOne();
			
			if($image != ''){
				deleteFileFromFolder(AD_SERVER_UPLOAD_FOLDER . $this->uploadFolder . $image);
			}
			
            deleteFromDbById($this->dbTable, $id);

            return true;
        }

        return false;
    }

    /**
     * Enable or disable promo block
     * @param   $id AS int or array
     * @param   $enabled AS boolen
     */
    public function enable($id, $enabled) {

        if (!is_numeric($id)) {
            $id = addSlashesDeep(jsonDecode($id));
        }

        if (!empty($id)) {
            doQuery($this->db, "UPDATE `" . $this->dbTable . "` SET `enabled` = '" . $enabled . "' WHERE " . (is_array($id) ? "`id` IN (" . implode(", ", $id) . ")" : "`id` = " . intval($id)));

            return true;
        }

        return false;
    }
    
    public function clear($text){
        return stripslashes($text);
    }
}

?>