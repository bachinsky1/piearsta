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
 * CMS file manager module main class
 * Admin path. Add/Delete/Edit actions with files in upload folder.
 * This is general file manager module for cms.
 * 08.02.2009
 */

class filemanagerData extends Module_cms {
	
	/**
	 * Constructor
	 */
	public function __construct() {
		parent :: __construct();
		$this->name = "filemanager";
	}
	
	/**
	* Get all folders and files
	* And create cms simple list
	* 
	* $folder - String, root folder
	* $mainFolder - Boolean, main folder or not
	*/
	public function showFolderWithFiles($folder, $mainFolder = false, $fm = 0) {
		header("Content-type:text/html");
		$returnHtml = "";
		
		$this->fm = $fm;
		
		if ($mainFolder) {
			$returnHtml .= '<ul id="filemanagerTree" class="list">';
			$returnHtml .= '<li class="folder" id="fm' . $this->fm . '">';
			$returnHtml .= '<div style="display: none;" id="path' . $this->fm . '">' . $folder . '</div>';
			$returnHtml .= '<span class="title">' . basename($folder) . '</span>';
			$returnHtml .= '<ul>';
		}
		else {
			$returnHtml .= '<ul>';
		}
		
		
		// Getting all folders
		$folders = $this->fileUtils->getFolders($folder);
		for ($i = 0; $i < count($folders); $i++) {
			$this->fm++;
			$returnHtml .= '<li class="folder" id="fm' . $this->fm . '">';
			
			$returnHtml .= $this->moduleDeleteLink($folder . $folders[$i] . "/");
			$returnHtml .= $this->moduleEditLink($folder . $folders[$i]);
			
			$returnHtml .= '<div style="display: none;" id="path' . $this->fm . '">' . $folder . $folders[$i] . '</div>';
			$returnHtml .= '<span class="title" id="fl' . $this->fm . '" onclick="changeUploadFolder(\'' . $folder . $folders[$i] . '/\', \'' . $this->fm . '\');">' . $folders[$i] . '</span>';
			
			$returnHtml .= ($this->fileUtils->isEmptyFolder($folder . $folders[$i]) ? $this->showFolderWithFiles($folder . $folders[$i] . "/", false, $this->fm) : '');
			$returnHtml .= '</li>';
			
		}
		
		// Getting all folders
		$files = $this->sortFileArray($this->fileUtils->getFiles($folder), $folder);	
		foreach ($files as $file) {
			$this->fm++;
			
			$returnHtml .= '<li class="file ' . $file["ext"] . '" id="fm' . $this->fm . '">';
			
			$returnHtml .= $this->moduleDeleteLink($folder . $file["name"]);
			$returnHtml .= $this->moduleEditLink($folder . $file["name"]);
			$returnHtml .= $this->modulePriviewLink($folder . $file["name"]);	
			
			$returnHtml .= '<div style="display: none;" id="path' . $this->fm . '">' . $folder . $file["name"] . '</div>';
			//$returnHtml .= '<img src="/' . AD_CMS_IMAGE_FOLDER . 'fileIcons/icon_' . $file["ext"] . '.gif" alt="' . $file["ext"] . '" />';
			$returnHtml .= '<span class="title" onclick="selectFileFromManager(\'' . $folder . $file["name"] . '\')">' . $file["name"] . '  |  ' . date("d.m.Y H:i:s", $file["date"]) . '  |  ' . round($file["size"] / 1024, 2) . '&nbsp;KB</span>';
			
			$returnHtml .= '</li>';
		}
		
		if ($mainFolder) {
			$returnHtml .= '</li>';
			$returnHtml .= '</ul>';  
		}
		else {
			$returnHtml .= '</ul>'; 
		}
		

		return $returnHtml;
	}
	
	/**
	 * upload files to file manager
	 * 
	 */
	public function uploadFiles() {
		$data = array();
		
		$config = array('upload_folder' => getG('folder'));
		
		$data = jsonDecode(getG('config'));
		if (is_array($data) && count($data) > 0) {
			foreach ($data AS $key => $value) {
				$config[$key] = $value;
			}
		}
		
		$this->upload = &loadLibClass('upload', true, $config);
		
		if ($this->upload->do_upload()) {
			$data['info'] = $this->upload->data();
			return $data;
		} else {
			$data['error'] = true;
			$data['errorMsg'] = $this->upload->display_errors('','');
			return $data;
		}
	
	}
}
?>