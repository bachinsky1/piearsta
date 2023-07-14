<?php
 
$path = $_SERVER['DOCUMENT_ROOT'] . "/files/";
$file = str_replace("../", "", $_GET['file']);
$file = str_replace("./", "", $file);

$fullPath = $path . $file;

$arrAllowExt = array( 
						'jpg', 'gif', 'png', 'jpeg', 'bmp',
						'doc', 'docx', 'xls', 'xlsx', 'pdf', 'csv',
						'xml', 'eps'
					);

if (file_exists($fullPath)) {
	if ($fd = fopen ($fullPath, "r")) {
	    $fsize = filesize($fullPath);
	    $path_parts = pathinfo($fullPath);
	    $ext = strtolower($path_parts["extension"]);
		
		if( !in_array( $ext, $arrAllowExt ) ){
			
			die(' :) ');
		}
			
			
	   /* switch ($ext) {
	        case "php":
	        case "js":
	        die("DIE!!!");
	        break;        
	        default:
	       */
	        header("Cache-Control: must-revalidate, post-check=0, pre-check=0"); 
			header("Pragma: public"); 
			//header("X-Download-Options: noopen "); // For IE8
			//header("X-Content-Type-Options: nosniff"); // For IE8
	        
	        header("Content-Disposition: attachment; filename=".basename($fullPath)."");
		    header( "Content-Type: application/octet-stream");
		    header( "Content-Type: application/force-download");
		    header( "Content-Type: application/download");
		    header( "Content-Transfer-Encoding: binary");
		    header("Content-length: $fsize");
		    header("Cache-control: private"); //use this to open files directly

	    //}
	    
	    
	    while(!feof($fd)) {
	        $buffer = fread($fd, 2048);
	        echo $buffer;
	    }
	} else {
		echo 'cannot read file';
	}
	fclose ($fd);
	exit;
} else {
	echo 'not exists';
}
 


?>