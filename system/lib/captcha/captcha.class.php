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
 * Captcha class
 * 10.06.2010
 */

class Captcha {
	
	private $type = 'web';
	private $word = '';
	
	public function __construct($type = 'web') {
		$this->type = $type;
		
	}
	
	/**
	 * Returns keystring
	 * 
	 */
	public function getKeyString(){
		return $this->word;
	}
	
    /**
	 * Create captcha
	 * 
	 * @param string	text
	 * @param string	img path
	 * @param string	img url
	 * @param string	font path
	 */
	public function create($data = '', $img_path = '', $img_url = '', $font_path = '') {		
		
		$defaults = array('word' => '', 'img_path' => '', 'img_url' => '', 'img_width' => '133', 'img_height' => '40', 'font_path' => '', 'expiration' => 7200);		
		
		foreach ($defaults AS $key => $val) {
			if (!is_array($data)) {
				if (!isset($$key) OR $$key == '') {
					$$key = $val;
				}
			} else {			
				$$key = (!isset($data[$key])) ? $val : $data[$key];
			}
		}

		if (!extension_loaded('gd')) {
			return false;
		}

		/**
		 * Do we have a "word" yet?
		 */	
		if ($word == '') {
			$pool = '23456789abcdeghkmnpqsuvxyz';
	
			$str = '';
			for ($i = 0; $i < 5; $i++) {
				$str .= substr($pool, mt_rand(0, strlen($pool) -1), 1);
			}
			
			$word = $str;
	   }
	   
	   $this->word = $word;

		/**
		 * Determine angle and position	
		 */	
		$length	= strlen($word);
		$angle	= ($length >= 6) ? rand( - ($length - 6), ($length - 6)) : 0;
		$x_axis	= rand(6, (360 / $length) - 16);			
		$y_axis = ($angle >= 0 ) ? rand($img_height, $img_width) : rand(6, $img_height);
		
		/**
		 * Create image	
		 * PHP.net recommends imagecreatetruecolor(), but it isn't always available
		 */			
		if (function_exists('imagecreatetruecolor')) {
			$im = imagecreatetruecolor($img_width, $img_height);
		} else {
			$im = imagecreate($img_width, $img_height);
		}

		/**
		 * Assign colors
		 */	
		$bg_color		= imagecolorallocate ($im, 255, 255, 255);
		$border_color	= imagecolorallocate ($im, 153, 102, 102);
		$text_color		= imagecolorallocate ($im, 204, 153, 153);
		$grid_color		= imagecolorallocate ($im, 255, 182, 182);
		$shadow_color	= imagecolorallocate ($im, 255, 240, 240);
	
		/**
		 * Create the rectangle
		 */	
		ImageFilledRectangle($im, 0, 0, $img_width, $img_height, $bg_color);
	
		/**
		 * Create the spiral pattern
		 */	
		$theta		= 1;
		$thetac		= 7;
		$radius		= 16;
		$circles	= 20;
		$points		= 32;
	
		for ($i = 0; $i < ($circles * $points) - 1; $i++) {
			
			$theta = $theta + $thetac;
			$rad = $radius * ($i / $points );
			$x = ($rad * cos($theta)) + $x_axis;
			$y = ($rad * sin($theta)) + $y_axis;
			$theta = $theta + $thetac;
			$rad1 = $radius * (($i + 1) / $points);
			$x1 = ($rad1 * cos($theta)) + $x_axis;
			$y1 = ($rad1 * sin($theta )) + $y_axis;
			imageline($im, $x, $y, $x1, $y1, $grid_color);
			$theta = $theta - $thetac;
		}
	
		/**
		 * Write the text
		 */	
		$use_font = ($font_path != '' && file_exists($font_path) && function_exists('imagettftext')) ? true : false;
			
		if ($use_font == false) {
			$font_size = 12;

 			$x = ceil(($img_width - (ImageFontWidth($font_size) * strlen($word))) / 2) - 30;  
			$y = 0;
		} else {
			$font_size	= 16;
			$x = rand(0, $img_width / ($length / 1.5));
			$y = $font_size + 2;
		}
	
		for ($i = 0; $i < strlen($word); $i++) {
			
			if ($use_font == false) {
				$y = rand(3 , $img_height / 4);
				imagestring($im, $font_size, $x, $y, substr($word, $i, 1), $text_color);
				$x += ($font_size * 2);
			} else {		
				$y = rand($img_height / 2, $img_height - 3);
				imagettftext($im, $font_size, $angle, $x, $y, $text_color, $font_path, substr($word, $i, 1));
				$x += $font_size;
			}
		}
		
		/**
		 * Create the border
		 */
		imagerectangle($im, 0, 0, $img_width - 1, $img_height - 1, $border_color);		

		/**
		 * Generate the image
		 */		
		if ($this->type == 'web') {
			header("Expires: Wed, 1 Jan 1997 00:00:00 GMT");
			header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");
			header("Cache-Control: no-store, no-cache, must-revalidate");
			header("Cache-Control: post-check=0, pre-check=0", false);
			header("Pragma: no-cache");

			header("Content-Type: image/jpeg");
			ImageJPEG($im);
			
		} else {
			
			if ($img_path == '' OR $img_url == '') {
				return false;
			}
		
			if (!@is_dir($img_path)) {
				return false;
			}
			
			if (!is_really_writable($img_path)) {
				return false;
			}		
			
			/**
			 * Remove old images
			 */		
			list($usec, $sec) = explode(" ", microtime());
			$now = ((float)$usec + (float)$sec);
					
			$current_dir = @opendir($img_path);
			
			while($filename = @readdir($current_dir)) {
				if ($filename != "." && $filename != ".." && $filename != "index.html") {
					$name = str_replace(".jpg", "", $filename);
				
					if (($name + $expiration) < $now) {
						@unlink($img_path . $filename);
					}
				}
			}
			
			@closedir($current_dir);
			
			$img_name = $now.'.jpg';
			
			ImageJPEG($im, $img_path . $img_name);
		
			$img = '<img src=' . $img_url . $img_name . '" width=' . $img_width . '" height="' . $img_height . '" style="border:0;" alt="" />';
			
			ImageDestroy($im);
				
			return array('word' => $word, 'time' => $now, 'image' => $img);
		}
		
	}
}