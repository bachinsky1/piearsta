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
 * Files uploading class
 *
 */
class Upload {
	
	public $max_size		= 0;
	public $max_width		= 0;
	public $max_height		= 0;
	public $min_width		= 0;
	public $min_height		= 0;
	public $max_filename	= 0;
	public $allowed_types	= array("gif", "jpg", "png", "jpeg", "doc", "pdf", "xls", "docx", "odt", "xlsx", "txt");
	public $file_temp		= "";
	public $file_name		= "";
	public $orig_name		= "";
	public $file_type		= "";
	public $file_size		= "";
	public $file_ext		= "";
	public $upload_path		= AD_SERVER_UPLOAD_FOLDER;
	public $upload_folder	= "";
	public $real_path		= AD_UPLOAD_FOLDER;
	public $overwrite		= false;
	public $encrypt_name	= false;
	public $is_image		= false;
	public $image_width		= '';
	public $image_height	= '';
	public $image_type		= '';
	public $image_size_str	= '';
	public $error_msg		= array();
	public $mimes			= array();
	public $remove_spaces	= true;
	public $xss_clean		= false;
	public $temp_prefix		= "temp_file_";
	public $client_name		= '';
		
	/**
	 * Constructor
	 */
	public function __construct($props = array()) {
		if (count($props) > 0) {
			$this->initialize($props);
		}
	}
	
	/**
	 * Initialize preferences
	 *
	 * @access	public
	 * @param	array
	 * @return	void
	 */	
	public function initialize($config = array()) {
		
		$defaults = array(
			'max_size'			=> 0,
			'max_width'			=> 0,
			'max_height'		=> 0,
			'min_width'			=> 0,
			'min_height'		=> 0,
			'max_filename'		=> 0,
			'allowed_types'		=> array("gif", "jpg", "png", "jpeg", "doc", "pdf", "xls", "docx", "odt", "xlsx", "txt"),
			'file_temp'			=> "",
			'file_name'			=> "",
			'orig_name'			=> "",
			'file_type'			=> "",
			'file_size'			=> "",
			'file_ext'			=> "",
			'upload_path'		=> AD_SERVER_UPLOAD_FOLDER,
			'upload_folder'		=> "",
			'real_path'			=> AD_UPLOAD_FOLDER,
			'overwrite'			=> false,
			'encrypt_name'		=> false,
			'is_image'			=> false,
			'image_width'		=> '',
			'image_height'		=> '',
			'image_type'		=> '',
			'image_size_str'	=> '',
			'error_msg'			=> array(),
			'mimes'				=> array(),
			'remove_spaces'		=> true,
			'xss_clean'			=> false,
			'temp_prefix'		=> "temp_file_",
			'client_name'		=> ''
		);	
	
	
		foreach ($defaults as $key => $val) {
			if (isset($config[$key])) {
				$method = 'set_' . $key;
				if (method_exists($this, $method)) {
					$this->$method($config[$key]);
				} else {
					$this->$key = $config[$key];
				}			
			} else {
				$this->$key = $val;
			}
		}
	}
	
	/**
	 * Perform the file upload
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function do_upload($field = 'uploadFile') {

		// Is $_FILES[$field] set? If not, no reason to continue.
		if (!isset($_FILES[$field])) {
			$this->set_error(gLA('upload_no_file_selected','Upload no file selected!'));
			return false;
		}
		
		// Is the upload path valid?
		if (!$this->validate_upload_path()) {
			// errors will already be set by validate_upload_path() so just return false
			return false;
		}

		// Was the file able to be uploaded? If not, determine the reason why.
		if (!is_uploaded_file($_FILES[$field]['tmp_name'])) {
			$error = (!isset($_FILES[$field]['error']) ? 4 : $_FILES[$field]['error']);

			switch($error) {
				
				case 1:	// UPLOAD_ERR_INI_SIZE
					$this->set_error(gLA('upload_file_exceeds_limit','Upload file exceeds limit!'));
					break;
				case 2: // UPLOAD_ERR_FORM_SIZE
					$this->set_error(gLA('upload_file_exceeds_form_limit','Upload file exceeds form limit!'));
					break;
				case 3: // UPLOAD_ERR_PARTIAL
				   $this->set_error(gLA('upload_file_partial','Upload file partial!'));
					break;
				case 4: // UPLOAD_ERR_NO_FILE
				   $this->set_error(gLA('upload_no_file_selected','Upload no file selected!'));
					break;
				case 6: // UPLOAD_ERR_NO_TMP_DIR
					$this->set_error(gLA('upload_no_temp_directory','Upload no temp directory!'));
					break;
				case 7: // UPLOAD_ERR_CANT_WRITE
					$this->set_error(gLA('upload_unable_to_write_file','Upload unable to write file!'));
					break;
				case 8: // UPLOAD_ERR_EXTENSION
					$this->set_error(gLA('upload_stopped_by_extension','Upload stopped by extension!'));
					break;
				default :  
					$this->set_error(gLA('upload_no_file_selected','Upload no file selected!'));
					break;
			}

			return false;
		}

		// Set the uploaded data as class variables
		$this->file_temp = $_FILES[$field]['tmp_name'];
		$this->file_size = $_FILES[$field]['size'];
		$this->file_type = preg_replace("/^(.+?);.*$/", "\\1", $_FILES[$field]['type']);
		$this->file_type = strtolower(trim(stripslashes($this->file_type), '"'));
		$this->file_name = $this->_prep_filename($_FILES[$field]['name']);
		$this->file_ext	 = $this->get_extension($this->file_name);
		$this->client_name = $this->file_name;

		

		// Is the file type allowed to be uploaded?
		if (!$this->is_allowed_filetype()) {
			$this->set_error(gLA('upload_invalid_filetype','Upload invalid filetype!'));
			return false;
		}
		
		// Convert the file size to kilobytes
		if ($this->file_size > 0) {
			$this->file_size = round($this->file_size / 1024, 2);
		}

		// Is the file size within the allowed maximum?
		if (!$this->is_allowed_filesize()) {
			$this->set_error(gLA('upload_invalid_filesize','Upload invalid filesize!'));
			return false;
		}

		// Are the image dimensions within the allowed size?
		// Note: This can fail if the server has an open_basdir restriction.
		if (!$this->is_allowed_dimensions()) {
			$this->set_error(gLA('upload_invalid_dimensions','Upload invalid dimensions!'));
			return false;
		}

		// Sanitize the file name for security
		$this->file_name = $this->clean_file_name($this->file_name);
		
		// Convert file name
		$this->file_name = $this->convertFilename($this->file_name);

		// Truncate the file name if it's too long
		if ($this->max_filename > 0) {
			$this->file_name = $this->limit_filename_length($this->file_name, $this->max_filename);
		}

		// Remove white spaces in the name
		if ($this->remove_spaces == true) {
			$this->file_name = preg_replace("/\s+/", "_", $this->file_name);
		}

		/*
		 * Validate the file name
		 * This function appends an number onto the end of
		 * the file if one with the same name already exists.
		 * If it returns false there was a problem.
		 */
		$this->orig_name = $this->file_name;

		if ($this->overwrite == false) {
			$this->file_name = $this->set_filename($this->upload_path, $this->file_name);
			
			if ($this->file_name === false) {
				return false;
			}
		}
		
		/*
		 * Run the file through the XSS hacking filter
		 * This helps prevent malicious code from being
		 * embedded within a file.  Scripts can easily
		 * be disguised as images or other file types.
		 */
		if ($this->xss_clean == true) {
			$this->do_xss_clean();
		}

		/*
		 * Move the file to the final destination
		 * To deal with different server configurations
		 * we'll attempt to use copy() first.  If that fails
		 * we'll use move_uploaded_file().  One of the two should
		 * reliably work in most environments
		 */
		if (!@copy($this->file_temp, $this->upload_path . $this->file_name)) {
			if (!@move_uploaded_file($this->file_temp, $this->upload_path . $this->file_name)) {
				 $this->set_error(gLA('upload_destination_error','Upload destination error!'));
				 return false;
			}
		}	

		/*
		 * Set the finalized image dimensions
		 * This sets the image width/height (assuming the
		 * file was an image).  We use this information
		 * in the "data" function.
		 */
		$this->set_image_properties($this->upload_path . $this->file_name);

		return true;
	}
	
	/**
	 * Finalized Data Array
	 *	
	 * Returns an associative array containing all of the information
	 * related to the upload, allowing the developer easy access in one array.
	 *
	 * @access	public
	 * @return	array
	 */	
	public function data() {
		
		return array (
						'file_name'			=> $this->file_name,
						'file_type'			=> $this->file_type,
						'file_path'			=> $this->upload_path,
						'full_path'			=> $this->upload_path . $this->file_name,
						'real_path'			=> $this->real_path . $this->upload_folder . $this->file_name,
						'raw_name'			=> str_replace($this->file_ext, '', $this->file_name),
						'orig_name'			=> $this->orig_name,
						'file_ext'			=> $this->file_ext,
						'file_size'			=> $this->file_size,
						'is_image'			=> $this->is_image(),
						'image_width'		=> $this->image_width,
						'image_height'		=> $this->image_height,
						'image_type'		=> $this->image_type,
						'image_size_str'	=> $this->image_size_str,
					);
	}
	
	/**
	 * Set Upload Path
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */	
	public function set_upload_path($path) {
		// Make sure it has a trailing slash
		$this->upload_path = rtrim($path, '/').'/';
	}
	
	// --------------------------------------------------------------------
	
	/**
	 * Set the file name
	 *
	 * This function takes a filename/path as input and looks for the
	 * existence of a file with the same name. If found, it will append a
	 * number to the end of the filename to avoid overwriting a pre-existing file.
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	public function set_filename($path, $filename) {
		
		if ($this->encrypt_name == true) {		
			mt_srand();
			$filename = md5(uniqid(mt_rand())) . $this->file_ext;	
		}
	
		if (!file_exists($path . $filename)) {
			return $filename;
		}
	
		$filename = str_replace($this->file_ext, '', $filename);
		
		$new_filename = '';
		for ($i = 1; $i < 100; $i++) {			
			if (!file_exists($path . $filename . $i . $this->file_ext)) {
				$new_filename = $filename . $i . $this->file_ext;
				break;
			}
		}

		if ($new_filename == '') {
			$this->set_error(gLA('upload_bad_filename','Upload bad filename!'));
			return false;
		} else {
			return $new_filename;
		}
	}
	
	/**
	 * Set Maximum File Size
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */	
	public function set_max_filesize($n) {
		$this->max_size = ((int)$n < 0) ? 0 : (int)$n;
	}
	
	/**
	 * Set Maximum File Name Length
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */	
	public function set_max_filename($n) {
		$this->max_filename = ((int)$n < 0) ? 0 : (int)$n;
	}
	
	/**
	 * Set Maximum Image Width
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */	
	public function set_max_width($n) {
		$this->max_width = ((int)$n < 0) ? 0 : (int)$n;
	}
	
	/**
	 * Set Maximum Image Height
	 *
	 * @access	public
	 * @param	integer
	 * @return	void
	 */	
	public function set_max_height($n) {
		$this->max_height = ((int)$n < 0) ? 0 : (int)$n;
	}
	
	/**
	 * Set Allowed File Types
	 *
	 * @param	string
	 * @return	void
	 */
	public function set_allowed_types($types) {
		
		if (!is_array($types) && $types == '*') {
			$this->allowed_types = '*';
			return;
		}
		
		$this->allowed_types = explode('|', $types);
	}
	
	/**
	 * Set Image Properties
	 *
	 * Uses GD to determine the width/height/type of image
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */	
	public function set_image_properties($path = '') {
		
		if (!$this->is_image()) {
			return;
		}

		if (function_exists('getimagesize')) {
			if (false !== ($D = @getimagesize($path))) {	
				$types = array(1 => 'gif', 2 => 'jpeg', 3 => 'png');

				$this->image_width		= $D['0'];
				$this->image_height		= $D['1'];
				$this->image_type		= (!isset($types[$D['2']])) ? 'unknown' : $types[$D['2']];
				$this->image_size_str	= $D['3'];  // string containing height and width
			}
		}
	}
	
	/**
	 * Set XSS Clean
	 *
	 * Enables the XSS flag so that the file that was uploaded
	 * will be run through the XSS filter.
	 *
	 * @access	public
	 * @param	bool
	 * @return	void
	 */
	public function set_xss_clean($flag = false) {
		$this->xss_clean = ($flag == true) ? true : false;
	}
	
	/**
	 * Validate the image
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function is_image() {
		// IE will sometimes return odd mime-types during upload, so here we just standardize all
		// jpegs or pngs to the same file type.

		$png_mimes  = array('image/x-png');
		$jpeg_mimes = array('image/jpg', 'image/jpe', 'image/jpeg', 'image/pjpeg');
		
		if (in_array($this->file_type, $png_mimes)) {
			$this->file_type = 'image/png';
		}
		
		if (in_array($this->file_type, $jpeg_mimes)) {
			$this->file_type = 'image/jpeg';
		}

		$img_mimes = array(
							'image/gif',
							'image/jpeg',
							'image/png',
						   );

		return (in_array($this->file_type, $img_mimes, true)) ? true : false;
	}
	
	/**
	 * Verify that the filetype is allowed
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function is_allowed_filetype($ignore_mime = true) {
		
		if ($this->allowed_types == '*') {
			return true;
		}

		if (count($this->allowed_types) == 0 OR !is_array($this->allowed_types)) {
			$this->set_error(gLA('upload_no_file_types','Upload no file types!'));
			return false;
		}

		$ext = strtolower(ltrim($this->file_ext, '.'));

		if (!in_array($ext, $this->allowed_types)) {
			return false;
		}

		// Images get some additional checks
		$image_types = array('gif', 'jpg', 'jpeg', 'png', 'jpe');

		if (in_array($ext, $image_types)) {
			if (getimagesize($this->file_temp) === false) {
				return false;
			}
		}

		if ($ignore_mime === true) {
			return true;
		}

		$mime = $this->mimes_types($ext);

		if (is_array($mime)) {
			if (in_array($this->file_type, $mime, true)) {
				return true;
			}
		} elseif ($mime == $this->file_type) {
				return true;
		}

		return false;
	}
	
	/**
	 * Verify that the file is within the allowed size
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function is_allowed_filesize() {
		if ($this->max_size != 0  AND  $this->file_size > $this->max_size) {
			return false;
		} else {
			return true;
		}
	}
	
	/**
	 * Verify that the image is within the allowed width/height
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function is_allowed_dimensions() {
		if (!$this->is_image()) {
			return true;
		}

		if (function_exists('getimagesize')) {
			$D = @getimagesize($this->file_temp);

			if ($this->max_width > 0 AND $D['0'] > $this->max_width) {
				return false;
			}
			
			if ($this->min_width > 0 AND $D['0'] < $this->min_width) {
				return false;
			}

			if ($this->max_height > 0 AND $D['1'] > $this->max_height) {
				return false;
			}
			
			if ($this->min_height > 0 AND $D['1'] < $this->min_height) {
				return false;
			}

			return true;
		}

		return true;
	}
	
	/**
	 * Validate Upload Path
	 *
	 * Verifies that it is a valid upload path with proper permissions.
	 *
	 *
	 * @access	public
	 * @return	bool
	 */	
	public function validate_upload_path() {

		if (isset($this->upload_folder) && $this->upload_folder) {
			
			$this->upload_folder = str_replace(array("../", "./"), array("", ""), $this->upload_folder);
			$this->upload_path .= $this->upload_folder;
		}
		
		if ($this->upload_path == '') {
			$this->set_error(gLA('upload_no_filepath','Upload no filepath!'));
			return false;
		}
		
		if (function_exists('realpath') AND @realpath($this->upload_path) !== false) {
			$this->upload_path = str_replace("\\", "/", realpath($this->upload_path));
		}

		if (!@is_dir($this->upload_path)) {
			$this->set_error(gLA('upload_no_filepath','Upload no filepath!'));
			return false;
		}

		if (!isWritable($this->upload_path)) {
			$this->set_error(gLA('upload_not_writable','Upload not writable!'));
			return false;
		}

		$this->upload_path = preg_replace("/(.+?)\/*$/", "\\1/",  $this->upload_path);
		return true;
	}
	
	/**
	 * Extract the file extension
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	public function get_extension($filename) {
		$x = explode('.', $filename);
		return '.' . end($x);
	}
	
	/**
	 * Clean the file name for security
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */		
	public function clean_file_name($filename) {
		$bad = array(
						"<!--",
						"-->",
						"'",
						"<",
						">",
						'"',
						'&',
						'$',
						'=',
						';',
						'?',
						'/',
						"%20",
						"%22",
						"%3c",		// <
						"%253c", 	// <
						"%3e", 		// >
						"%0e", 		// >
						"%28", 		// (
						"%29", 		// )
						"%2528", 	// (
						"%26", 		// &
						"%24", 		// $
						"%3f", 		// ?
						"%3b", 		// ;
						"%3d"		// =
					);
					
		$filename = str_replace($bad, '', $filename);

		return stripslashes($filename);
	}
	
	/**
	 * Check and return correct file name
	 * 
	 * @param string	file name
	 */
	public function convertFilename($filename) {
	  	
		$lvCollation = array('ā','č','ē','ģ','ī','ķ','ļ','ņ','š','ū','ž','Ā','Č','Ē','Ģ','Ī','Ķ','Ļ','Ņ','Š','Ū','Ž');
		$asciCollation = array('a','c','e','g','i','k','l','n','s','u','z','A','C','E','G','I','K','L','N','S','U','Z');
		
		$filename = str_replace($lvCollation, $asciCollation, $filename);
		$filename = preg_replace("/[^a-zA-Z0-9.-_\-]/", "_", $filename);
		$filename = strtolower(str_replace("__", "_", $filename));

		return $filename;
	}
	
	/**
	 * Limit the File Name Length
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */		
	public function limit_filename_length($filename, $length) {
		if (strlen($filename) < $length) {
			return $filename;
		}
	
		$ext = '';
		if (strpos($filename, '.') !== false) {
			$parts		= explode('.', $filename);
			$ext		= '.' . array_pop($parts);
			$filename	= implode('.', $parts);
		}
	
		return substr($filename, 0, ($length - strlen($ext))) . $ext;
	}
	
	/**
	 * Runs the file through the XSS clean function
	 *
	 * This prevents people from embedding malicious code in their files.
	 * I'm not sure that it won't negatively affect certain files in unexpected ways,
	 * but so far I haven't found that it causes trouble.
	 *
	 * @access	public
	 * @return	void
	 */	
	public function do_xss_clean() {		
		
		$file = $this->file_temp;

		if (filesize($file) == 0) {
			return false;
		}

		if (function_exists('memory_get_usage') && memory_get_usage() && ini_get('memory_limit') != '') {
			$current = ini_get('memory_limit') * 1024 * 1024;

			// There was a bug/behavioural change in PHP 5.2, where numbers over one million get output
			// into scientific notation.  number_format() ensures this number is an integer
			// http://bugs.php.net/bug.php?id=43053

			$new_memory = number_format(ceil(filesize($file) + $current), 0, '.', '');

			ini_set('memory_limit', $new_memory); // When an integer is used, the value is measured in bytes. - PHP.net
		}

		// If the file being uploaded is an image, then we should have no problem with XSS attacks (in theory), but
		// IE can be fooled into mime-type detecting a malformed image as an html file, thus executing an XSS attack on anyone
		// using IE who looks at the image.  It does this by inspecting the first 255 bytes of an image.  To get around this
		// CMS will itself look at the first 255 bytes of an image to determine its relative safety.  This can save a lot of
		// processor power and time if it is actually a clean image, as it will be in nearly all instances _except_ an
		// attempted XSS attack.

		if (function_exists('getimagesize') && @getimagesize($file) !== false) {
			if (($file = @fopen($file, 'rb')) === false) { // "b" to force binary
				return false; // Couldn't open the file, return FALSE
			}

			$opening_bytes = fread($file, 256);
			fclose($file);

			// These are known to throw IE into mime-type detection chaos
			// <a, <body, <head, <html, <img, <plaintext, <pre, <script, <table, <title
			// title is basically just in SVG, but we filter it anyhow

			if (!preg_match('/<(a|body|head|html|img|plaintext|pre|script|table|title)[\s>]/i', $opening_bytes)) {
				return true; // its an image, no "triggers" detected in the first 256 bytes, we're good
			}
		}

		if (($data = @file_get_contents($file)) === false) {
			return false;
		}

		$security = loadLibClass('security');

		return $security->xss_clean($data, true);
	}
	
	/**
	 * Set an error message
	 *
	 * @access	public
	 * @param	string
	 * @return	void
	 */	
	public function set_error($msg) {
		
		if (is_array($msg)) {
			foreach ($msg as $val) {			
				$this->error_msg[] = $msg;
				logInfo($msg);
			}		
		} else {
			$this->error_msg[] = $msg;
			logInfo($msg);
		}
	}
	
	/**
	 * Display the error message
	 *
	 * @access	public
	 * @param	string
	 * @param	string
	 * @return	string
	 */	
	public function display_errors($open = '<p>', $close = '</p>') {
		
		$str = '';
		foreach ($this->error_msg as $val) {
			$str .= $open . $val . $close;
		}
	
		return $str;
	}
	
	/**
	 * List of Mime Types
	 *
	 * This is a list of mime types.  We use it to validate
	 * the "allowed types" set by the developer
	 *
	 * @access	public
	 * @param	string
	 * @return	string
	 */	
	public function mimes_types($mime) {
		$this->cfg = &loadLibClass('config');
		$this->mimes = $this->cfg->get('mimes');
		
		return (!isset($this->mimes[$mime])) ? false : $this->mimes[$mime];
	}
	
	/**
	 * Prep Filename
	 *
	 * Prevents possible script execution from Apache's handling of files multiple extensions
	 * http://httpd.apache.org/docs/1.3/mod/mod_mime.html#multipleext
	 *
	 * @access	private
	 * @param	string
	 * @return	string
	 */
	public function _prep_filename($filename) {
		
		if (strpos($filename, '.') === false) {
			return $filename;
		}

		$parts		= explode('.', $filename);
		$ext		= array_pop($parts);
		$filename	= array_shift($parts);

		foreach ($parts as $part) {
			if ($this->mimes_types(strtolower($part)) === false) {
				$filename .= '.' . $part . '_';
			} else {
				$filename .= '.'.$part;
			}
		}
		
		$filename = convertUrl($filename);

		// file name override, since the exact name is provided, no need to
		// run it through a $this->mimes check.
		if ($this->file_name != '') {
			$filename = $this->file_name;
		}

		$filename .= '.' . $ext;
		
		return $filename;
	}

}