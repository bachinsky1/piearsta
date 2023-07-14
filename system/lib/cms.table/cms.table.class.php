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
 * Admin table class
 * Creating and drawing admin table
 * 04.06.2008
 */

class Cms_table {

	/**
	 * $returnTable - String with return html table
	 * $trClass - String, tr class changer
	 * $tableType string	table type(edit or simple)
	 * $module	obj		Module object
	 */
	public $returnTable = '';
	public $trClass;
	public $tableType;
	public $module;

    /**
	 * Get sql query fields names and return it
	 * 
	 * @param string	 Query result
	 */
	public function getQueryFieldsNames($query) {
		$fieldName = Array();
		
		for($i = 0; $query->fieldname($i); $i++){
			$fieldName[] = $query->fieldname($i);
		}
		
		return $fieldName;
	}
	
	/**
	 * Get sql query fields values and return it
	 * @param string	 Query result
	 */
	public function getQueryFieldsValues($query) {
		$fieldValue = Array();
		
		for($i = 0; $query->fieldname($i); $i++){
			$fieldValue[] = $query->field($query->fieldname($i));
		}
		
		return $fieldValue;
	}
	
	/**
	 * Draw table head
	 * 
	 * @param Array with table head values
	 * @param Array with booleans of sortable cells
	 * @param Array with table head db fields names
	 * @param String with css class name
	 */
	public function drawTableHead($tableHeadValues, $tableHeadSort, $tableHeadSortFields, $class = "") {
	
		$class = ($class ? ' class="' . $class . '"' : '');
					
		$html = '<thead><tr' . $class . '>';
		for ($i = 0; $i < count($tableHeadValues); $i++) {
			if ($tableHeadSort[$i]) {
				$html .= '<th' . ($tableHeadSortFields[$i] == getP("sortField") ? (getP("sortOrder") == "ASC" ? ' class="sort-down"' : ' class="sort-up"') : '') . '>' . $this->creatSortCell($tableHeadValues[$i], $tableHeadSortFields[$i]) . '</th>';
			}
			else {
				$html .= '<th>' . $tableHeadValues[$i] . '</th>';
			}			
		}
		$html .= '</tr></thead>';
		
		$this->returnTable .= $html;
		
		if ($this->tableType == 'edit') {
			$this->returnTable .= '<tr id="edit_"></tr>';
		}
	}
	
	/**
	 * Creat sortable cell
	 * 
	 * @param string		with cell value
	 * @param string		with db field value
	 */
	public function creatSortCell($value, $field) {
		return '<a href="javascript:;" onclick="moduleTable.sort(\'' . $field . '\'); return false;">' . $value . '</a>';;
	}
	
	/**
	 * Draw one cell
	 * 
	 * @param String with cell value
	 * @param String with css class name
	 * @param String with head id
	 */
	public function drawOneCell($value, $class = "", $id = "") {
		
		$class = ($class ? ' class="' . $class . '"' : '');
		$id = ($id ? ' id="' . $id . '"' : '');
		
		$this->returnTable .= '<td' . $class . $id . '>' . $value . ' </td>';
	}
	
	/**
	 * Start table row
	 * 
	 * @param String with css class name
	 * @param String with head id
	 */
	public function startOneRow($class = "", $id = "") {
		
		$class = ($class ? ' class="' . $class . '"' : '');
		
		if ($class) {
			$class = ' class="' . $class . '"';
		}
		elseif ($this->trClass == "") {
			$class = '';
			$this->trClass = "sel";		
		}
		else {
			$class = ' class="sel"';
			$this->trClass = "";
		}
		
		$id = ($id ? ' id="' . $id . '"' : '');
		
		$this->returnTable .= '<tr' . $class . $id . '>';
	}
	
	/**
	 * End table row
	 * 
	 * @param int	item id
	 */
	public function endOneRow($id = '') {
		$this->returnTable .= '</tr>';
		if ($id) {
			$this->returnTable .= '<tr id="edit_' . $id . '"></tr>';
		}
	}
	
	/**
	 * Start table
	 * 
	 * @param String with css class name
	 * @param String with head id
	 * @param String of css styles
	 */
	public function startTable($class = 'cms-table', $id = "") {
		
		$class = ($class ? ' class="' . $class . '"' : '');
		$id = ($id ? ' id="' . $id . '"' : '');
		
		$this->returnTable = '<table' . $class . $id . '>';
	}
	
	/**
	 * Add colgroup
	 * 
	 */
	public function addColgroup($info) {
		
		$this->returnTable .= '<colgroup span="1">';
		
		for ($i = 0; $i < count($info); $i++) {
			$this->returnTable .= '<col span="1" width="' . $info[$i] . '"/>';
		}
		
		$this->returnTable .= '</colgroup>';
	}
	
	/**
	 * End table
	 */
	public function endTable() {
		$this->returnTable .= '</table>';
	}
	
	/**
	 * Create cms module table
	 * 
	 * @param array		fields config array
	 * @param array		fields info from mysql
	 * @param bool		Editable with ajax - true/false
	 */
	public function createTable($fields, $info, $editAjax = false, $ajaxField = '') {
		
		$this->returnTable .= '<table class="cms-table">';

		// Create table header
		$this->returnTable .= '<thead><tr>';
		foreach ($fields AS $f => $c) {
			
			if (isset($c['sort']) && $c['sort']) {
				$this->returnTable .= '<th' . ($f == getP("sortField") ? (getP("sortOrder") == "ASC" ? ' class="sort-down"' : ' class="sort-up"') : '') . '> 
										<a href="#" onclick="moduleTable.sort(\'' . $f . '\'); return false;">' . $c['title'] . '</a>
									</th>';
			} else {
				$this->returnTable .= '<th>' . $c['title'] . '</th>';
			}
		}
		$this->returnTable .= '</thead></tr>';
		
		// Create table body
		$this->returnTable .= '<tbody>';
		
		if ($editAjax) {
			$this->returnTable .= '<tr id="edit_"></tr>';
		}
		
		for ($i = 0; $i < count($info); $i++) {
			
			if ($editAjax) {
				$this->returnTable .= '<tr id="tr_' . $info[$i][$ajaxField] . '">';
			} else {
				$this->returnTable .= '<tr>';	
			}
			
			foreach ($fields AS $f => $c) {
				
				if (isset($c['function']) && !empty($c['function']))  {
					
					$params = array();
					$cntFields = count( $c['fields'] );
					for( $jj = 0; $jj < $cntFields; $jj++ ){
						
						$fields_name = $c['fields'][$jj];
						$params[] = isset($info[$i][$fields_name]) ? $info[$i][$fields_name] : '';
						
					}
					
					if (isset($c['params'])) {
						$params = array_merge($params , $c['params']);
					}
					
					$value = call_user_func_array($c['function'], $params);
					
				} else {
					$value = $info[$i][$f];
				}
				
				if (isset($c['sort']) && $c['sort']) {
					$this->returnTable .= '<td' . (getP("sortField") == $f ? ' class="sort"' : '') . '> 
											' . $value . '
										</td>';
				} else {
					$this->returnTable .= '<td>' . $value . '</td>';
				}
			}
			
			$this->returnTable .= '</tr>';
			
			if ($editAjax) {
				$this->returnTable .= '<tr id="edit_' . $info[$i][$ajaxField] . '"></tr>';
			} 	
		}
		$this->returnTable .= '</tbody>';
		
		$this->returnTable .= '</table>';
	}
		
}
?>
