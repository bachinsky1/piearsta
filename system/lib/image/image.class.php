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
 * Images utils class
 * All operations with images only from this class.
 * This is general file images class for cms.
 * 07.05.2009
 */

class Image {
	
	protected $base64 = false;
	
	public function __construct() {
		
		/*		
		    Function returns boolean false on error or boolean true when there is no error.
		    If IR_RETURN_ARRAY is added to $options, it returns array of parameters instead of boolean true.
		
		    $in         - string    - Filename of source image.
		    $out        - string    - Filename of destination (new) image.
		    $width      - integer   - Width of destination image (exact meaning depends on $options).
		    $height     - integer   - Height of destination image (exact meaning depends on $options).
		    $options    - integer   - Combination of constants, for example, "IR_TOUCH_FROM_OUTSIDE | IR_CROP | IR_RETURN_ARRAY".
		    $add_images - array     - Info about images that have to be added to destination image (e.g., watermarks or rounded corners).
		                            - This array consists of other arrays, each defining a single image that has to be added.
		                            - Elements of these arrays are:
		                                    'file'      => string   - Filename of image that has to be added.
		                                    'position'  => const    - One of constants that start with IR_POS_ [optional; default - IR_POS_TOP_LEFT].
		                                    'x'         => integer  - Value that has to be added to 'x' position [optional].
		                                    'y'         => integer  - Value that has to be added to 'y' position [optional].
		                              For example, to add an image that is in the bottom-right corner, 15px from right side, 10px from bottom side, you would use:
		                              array('file' => 'example.png', 'position' => IR_POS_BOTTOM_RIGHT, 'x' => -15, 'y' => -10)
		*/
		
		
		// Combination of these constants may be used in $options.
		define('IR_DONT_RESIZE'                 , 0x00000001);  // only $add_images is processed
		define('IR_STRETCH'                     , 0x00000002);  // uses $width and $height as size of new image
		define('IR_TOUCH_FROM_INSIDE'           , 0x00000004);  // calculates new_width and new_height so that (new_width <= $width && new_height <= $height)
		define('IR_TOUCH_FROM_OUTSIDE'          , 0x00000008);  // calculates new_width and new_height so that (new_width >= $width && new_height >= $height && (new_width == width || new_height == height))
		define('IR_CONST_WIDTH'                 , 0x00000010);  // calculates only new_height, leaves new_width fixed
		define('IR_CONST_HEIGHT'                , 0x00000020);  // calculates only new_width, leaves new_height fixed
		define('IR_DONT_RESIZE_SMALLER'         , 0x00000040);  // don't resize image if (new_width <= width && new_height <= height)
		define('IR_CROP'                        , 0x00000080);  // crop resized image so that it fits into (new_width == width && new_height == height); may be used only with IR_TOUCH_FROM_OUTSIDE, IR_CONST_WIDTH and IR_CONST_HEIGHT
		define('IR_RETURN_ARRAY'                , 0x00000100);  // return array with information about image instead of boolean true
		define('IR_FORCE_IF_EXISTS'             , 0x00000200);  // don't exit function if $out file exists
		
		
		// These constants may be used in $add_images. Depending on constant that is used, function calculates initial position of added image.
		// This position may be adjusted by using 'x' and 'y' elements in arrays in $add_images.
		define('IR_POS_CENTER'          , 0x0080);  // same as IR_POS_MIDDLE_CENTER
		define('IR_POS_TOP_LEFT'        , 0x0001);
		define('IR_POS_TOP_CENTER'      , 0x0002);
		define('IR_POS_TOP_RIGHT'       , 0x0004);
		define('IR_POS_BOTTOM_LEFT'     , 0x0008);
		define('IR_POS_BOTTOM_CENTER'   , 0x0010);
		define('IR_POS_BOTTOM_RIGHT'    , 0x0020);
		define('IR_POS_MIDDLE_LEFT'     , 0x0040);
		define('IR_POS_MIDDLE_CENTER'   , 0x0080);  // same as IR_POS_CENTER
		define('IR_POS_MIDDLE_RIGHT'    , 0x0100);
	}

	/**
	 * Resize image
	 * 
	 * @param string	in image source
	 * @param string	out image source
	 * @param int	 	new image width
	 * @param int		new image height
	 * @param cons		resize options
	 * @param array		watermark images
	 */
	public function resizeImg($in, $out, $width, $height, $options, $add_images = null) {
		return $this->resizeImage($in, $out, $width, $height, $options, $add_images);
	}
	
	public function setBase64($bool)
	{
		$this->base64 = $bool;
	}
	
	/**
	 * Resize image
	 * 
	 * @param string	in image source
	 * @param string	out image source
	 * @param int	 	new image width
	 * @param int		new image height
	 * @param cons		resize options
	 * @param array		watermark images
	 */
    public function resizeImage($in, $out, $width, $height, $options, $add_images = null) {

		if (!$this->base64 && !is_file($in)) {
			// input file does not exist
			die("File: " . $in . " not exists!");
			return false;
		}
        
		if ($options & IR_FORCE_IF_EXISTS !== IR_FORCE_IF_EXISTS && file_exists($out)) {
			// output file exists and it doesn't have to be rewritten
			return false;
		}

		// get extension of input and output files; needed to know how to load and save image
		$src_ext = strtolower(strrchr($in , '.'));
		$dst_ext = strtolower(strrchr($out, '.'));
		if ($dst_ext === '' || strpos($dst_ext, '/') !== false || strpos($dst_ext, '\\') !== false) {
			$dst_ext = $src_ext;
		}
		
		if ($this->base64) {
			
			$src = imagecreatefromstring(base64_decode($in));
			
		} else {
			// load image
			switch ($src_ext) {
				case '.jpeg':
					$src = imagecreatefromjpeg($in);
					break;
				case '.jpg':
					$src = imagecreatefromjpeg($in);
					break;
				case '.gif':
					$src = imagecreatefromgif ($in);
					break;
				case '.png':
					$src = imagecreatefrompng ($in);
					break;
				case '.bmp':
					$src = $this->convertBmp($in);
					break;
			}
		}

		
        
		if (empty($src)) {
			// unsupported extension or wrong structure of input file
			return false;
		}
        
		// get dimensions of input image
		$src_width  = imagesx($src);
		$src_height = imagesy($src);
		
		// calculate new dimensions
		if ($options & IR_DONT_RESIZE) {
		
			$dst_width = $src_width;
			$dst_height = $src_height;
			$dst = $src;
			unset($src);	    
		} 
		else {
		
			// default - stretch
			$dst_width  = $width;
			$dst_height = $height;
		        
			if ($options & IR_STRETCH) {
				$dst_width  = $width;
				$dst_height = $height;
			} 
			else
		
			if ($options & IR_TOUCH_FROM_INSIDE) {
				if ($src_width / $src_height > $width / $height) {
					$dst_width = $width;
					$dst_height = (int)($src_height / ($src_width / $width));
				} 
				else {
					$dst_height = $height;
					$dst_width = (int)($src_width / ($src_height / $height));
				}
			} 
			else
		
			if ($options & IR_TOUCH_FROM_OUTSIDE) {
				if ($src_width / $src_height < $width / $height) {
					$dst_width = $width;
					$dst_height = (int)($src_height / ($src_width / $width));
		        } 
		        else {
					$dst_height = $height;
					$dst_width = (int)($src_width / ($src_height / $height));
				}
			} 
		    else
		
			if ($options & IR_CONST_WIDTH) {
				$dst_width = $width;
				$dst_height = (int)($src_height / ($src_width / $width));
			} 
			else
		
			if ($options & IR_CONST_HEIGHT) {
				$dst_height = $height;
				$dst_width = (int)($src_width / ($src_height / $height));
			}
		
			// create resized image
			if ($options & IR_DONT_RESIZE_SMALLER && $src_width <= $dst_width && $src_height <= $dst_height) {
				$dst = $src;
			} 
			else {
				$dst = imagecreatetruecolor($dst_width, $dst_height);
                imagealphablending($dst, false); 
                imagesavealpha($dst, true);
				imagecopyresampled($dst, $src, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);
				imagedestroy($src);
			}
			unset($src);
		
			// crop image
			if ($options & IR_CROP && $options & (IR_TOUCH_FROM_OUTSIDE | IR_CONST_WIDTH | IR_CONST_HEIGHT)) {
				$dst_width  = $width  < $dst_width  ? $width  : $dst_width;
				$dst_height = $height < $dst_height ? $height : $dst_height;
				$tmp = imagecreatetruecolor($dst_width, $dst_height);
                imagealphablending($tmp, false); 
                imagesavealpha($tmp, true);
				imagecopy($tmp, $dst, 0, 0, 0, 0, $dst_width, $dst_height);
				imagedestroy($dst);
				$dst = $tmp;
				unset($tmp);
			}
		    
		}
		
		// process $add_images
		if (is_array($add_images)) {
			foreach ($add_images as $img_data) {

				$ext = strtolower(strrchr($img_data['file'], '.'));
				switch ($ext) {
					case '.jpeg': 
						$img = imagecreatefromjpeg($img_data['file']); 
						break;
					case '.jpg': 
						$img = imagecreatefromjpeg($img_data['file']); 
						break;
					case '.gif': 
						$img = imagecreatefromgif ($img_data['file']); 
						break;
					case '.png': 
						$img = imagecreatefrompng ($img_data['file']); 
						break;
				}
				if (empty($img)) {
					continue;
				}
				$img_width  = imagesx($img);
				$img_height = imagesy($img);
				$x = 0;
				$y = 0;
				if (isset($img_data['position'])) {
					switch ($img_data['position']) {
						case IR_POS_TOP_LEFT:
							$x = 0;
							$y = 0;
						break;
						case IR_POS_TOP_CENTER:
							$x = (int)(($dst_width - $img_width) / 2);
							$y = 0;
						break;
						case IR_POS_TOP_RIGHT:
							$x = $dst_width - $img_width;
							$y = 0;
						break;
						case IR_POS_MIDDLE_LEFT:
							$x = 0;
							$y = (int)(($dst_height - $img_height) / 2);
						break;
						case IR_POS_MIDDLE_CENTER:
							$x = (int)(($dst_width - $img_width) / 2);
							$y = (int)(($dst_height - $img_height) / 2);
						break;
						case IR_POS_MIDDLE_RIGHT:
							$x = $dst_width - $img_width;
							$y = (int)(($dst_height - $img_height) / 2);
						break;
						case IR_POS_BOTTOM_LEFT:
							$x = 0;
							$y = $dst_height - $img_height;
						break;
						case IR_POS_BOTTOM_CENTER:
							$x = (int)(($dst_width - $img_width) / 2);
							$y = $dst_height - $img_height;
						break;
						case IR_POS_BOTTOM_RIGHT:
							$x = $dst_width - $img_width;
							$y = $dst_height - $img_height;
						break;
					}
				}
				if (isset($img_data['x'])) { 
					$x += $img_data['x'];
				}
				if (isset($img_data['y'])) { 
					$y += $img_data['y'];
				}
				imagecopy($dst, $img, $x, $y, 0, 0, $img_width, $img_height);
				imagedestroy($img);
			}
		}
		
		// save image
		switch ($dst_ext) {
			case '.jpeg': 
				$ok = imagejpeg($dst, $out, 90); 
				break;
			case '.jpg': 
				$ok = imagejpeg($dst, $out, 90); 
				break;
			case '.gif': 
				$ok = imagegif ($dst, $out); 
				break;
			case '.png': 
				$ok = imagepng ($dst, $out, 9);
				break;
		}
		imagedestroy($dst);
		unset($dst);
		
		// return
		if (empty($ok)) {
			// could not create image
			return false;
		}
		if ($options & IR_RETURN_ARRAY) {
			return array (
				'in'         => $in,
				'out'        => $out,
				'src_width'  => $src_width,
				'src_height' => $src_height,
				'src_ext'    => $src_ext,
				'dst_width'  => $dst_width,
				'dst_height' => $dst_height,
				'dst_ext'    => $dst_ext
			);
		} 
		else {
			return true;
		}      
	}
	
	/**
	 * 
	 * Crop center of the image
	 * Use it, when need crop image to specific design
	 * 
	 * @param string	in image source
	 * @param int	 	new image width
	 * @param int		new image height
	 */
	public function cropCenter($in, $w, $h) {
		
		if (!is_file($in)) {
			logWarn("File: " . $in . " not exists!");
			return false;
		}
		
		// get extension of input and output files; needed to know how to load and save image
		$src_ext = strtolower(strrchr($in , '.'));

		// load image
		switch ($src_ext) {
			case '.jpeg': 
				$src = imagecreatefromjpeg($in); 
				break;
			case '.jpg': 
				$src = imagecreatefromjpeg($in); 
				break;
			case '.gif': 
				$src = imagecreatefromgif ($in); 
				break;
			case '.png': 
				$src = imagecreatefrompng ($in); 
				break;
			case '.bmp':
				$src = $this->convertBmp($in); 
				break;	
		}
        
		if (empty($src)) {
			// unsupported extension or wrong structure of input file
			return false;
		}
		
		// get dimensions of input image
		$src_width  = imagesx($src);
		$src_height = imagesy($src);
		
		if ($src_width > $src_height) {
			
			$new_width = $src_height * $w / $h;
			$from_width = round(($src_width - $new_width) / 2);
			
			$dst = imagecreatetruecolor($new_width, $src_height);
			imagecopyresampled($dst, $src, 0, 0, $from_width, 0, $new_width, $src_height, $new_width, $src_height);

			
		} else {
			
			$new_height = $src_width * $h / $w;
			$from_height = round(($src_height - $new_height) / 2);
			
			$dst = imagecreatetruecolor($src_width, $new_height);
			imagecopyresampled($dst, $src, 0, 0, 0, $from_height, $src_width, $new_height, $src_width, $new_height);

		}
		
		// save image
		switch ($src_ext) {
			case '.jpeg': 
				imagejpeg($dst, $in, 10); 
				break;
			case '.jpg': 
				imagejpeg($dst, $in, 10); 
				break;
			case '.gif': 
				imagegif ($dst, $in); 
				break;
			case '.png': 
				imagepng ($dst, $in, 10);
				break;
		}
		
		imagedestroy($dst);

	}
	
	/**
	 * Create security image: capthca
	 */
	public function createSecurityImage() {

		// CONFIGURATION ----
		$font = "fonts/gara.ttf";
		$imageSize = array(90, 25); //x,y
		$bgroundColor = array(255, 255, 255); //rgb
		// END CONFIGURATION ----
		
		$im  = imagecreate($imageSize[0], $imageSize[1]);
		$bgc = imagecolorallocate($im, $bgroundColor[0], $bgroundColor[1], $bgroundColor[2]);
		imagefilledrectangle($im, 0, 0, $imageSize[0], $imageSize[1], $bgc);
		
		$chars = array("a","A","b","B","c","C","d","D","e","E","f","F","g",
		"G","h","H","i","I","j","J","k",
		"K","l","L","m","M","n","N","o","O","p","P","q","Q",
		"r","R","s","S","t","T","u","U","v",
		"V","w","W","x","X","y","Y","z","Z","1","2","3","4",
		"5","6","7","8","9");
		$length = 5;
		$letter_spacing = (int) $imageSize[0] / ($length + 1);
		$code = "";
		
		// Create random size, and dark color
		$font_size = rand($letter_spacing - 1, $letter_spacing + 1);
		$color = imagecolorallocate($im, rand(0, 30), rand(0, 30), rand(0, 30));
		$x = ($imageSize[0] - $font_size - $letter_spacing) / $length;
		$y = ($imageSize[1] / 2) + ($font_size / 4);
		
		for ($i = 0; $i < $length; $i++) {
		  //random angle
		  $angle = rand(-25, 25);
		  //random y
		  $y = rand($y - 1, $y + 1);
		  //char
		  $text = $chars[rand(0, count($chars) - 1)];
		  
		  imagettftext($im, $font_size, $angle, $x, $y, $color, $font, $text);
		
		  $x += $letter_spacing;
		  $code .= $text;
		}  
		// Creating session with code
		$_SESSION['authcode'] = $code;
		
		//header("Content-Type: image/png");
		$image = imagepng($im);	
		
		//Destroy the image to free memory
		imagedestroy($im);
		
		return $image;
	}
	
	/**
	 * BMP convertor
	 */
	public function convertBmp($fName) {
	 
		if (!$f1 = fopen($fName,"rb")) return false;
		
		$file = unpack("vfile_type/Vfile_size/Vreserved/Vbitmap_offset", fread($f1, 14));
		if ($file['file_type'] != 19778) return false;
	
		$bmp = unpack('Vheader_size/Vwidth/Vheight/vplanes/vbits_per_pixel' .
	                 '/Vcompression/Vsize_bitmap/Vhoriz_resolution' .
	                 '/Vvert_resolution/Vcolors_used/Vcolors_important', fread($f1, 40));
		$bmp['colors'] = pow(2, $bmp['bits_per_pixel']);
		if ($bmp['size_bitmap'] == 0) {
			$bmp['size_bitmap'] = $file['file_size'] - $file['bitmap_offset'];
		}
		$bmp['bytes_per_pixel'] = $bmp['bits_per_pixel'] / 8;
		$bmp['bytes_per_pixel2'] = ceil($bmp['bytes_per_pixel']);
		$bmp['decal'] = ($bmp['width'] * $bmp['bytes_per_pixel'] / 4);
		$bmp['decal'] -= floor($bmp['width'] * $bmp['bytes_per_pixel'] / 4);
		$bmp['decal'] = 4 - (4 * $bmp['decal']);
		if ($bmp['decal'] == 4) {
			$bmp['decal'] = 0;
		}
	
		$PALETTE = array();
		if ($bmp['colors'] < 16777216) {
			$PALETTE = unpack('V' . $bmp['colors'], fread($f1, $bmp['colors'] * 4));
		}
	
		$IMG = fread($f1, $bmp['size_bitmap']);
		$VIDE = chr(0);
	
		$res = imagecreatetruecolor($bmp['width'], $bmp['height']);
		$P = 0;
		$Y = $bmp['height'] - 1;
		while ($Y >= 0) {
			$X = 0;
			while ($X < $bmp['width']) {
				if ($bmp['bits_per_pixel'] == 24) {
					$COLOR = unpack("V", substr($IMG, $P, 3) . $VIDE);
				}
				elseif ($bmp['bits_per_pixel'] == 16) { 
					$COLOR = unpack("n", substr($IMG, $P, 2));
					$COLOR[1] = $PALETTE[$COLOR[1] + 1];
				}
				elseif ($bmp['bits_per_pixel'] == 8) { 
					$COLOR = unpack("n", $VIDE . substr($IMG, $P, 1));
					$COLOR[1] = $PALETTE[$COLOR[1] + 1];
				}
				elseif ($bmp['bits_per_pixel'] == 4) {
					$COLOR = unpack("n",$VIDE.substr($IMG,floor($P),1));
					if (($P * 2) % 2 == 0) {
						$COLOR[1] = ($COLOR[1] >> 4) ; 
					}
					else {
						$COLOR[1] = ($COLOR[1] & 0x0F);
					}
					$COLOR[1] = $PALETTE[$COLOR[1]+1];
				}
				elseif ($bmp['bits_per_pixel'] == 1) {
					$COLOR = unpack("n", $VIDE . substr($IMG, floor($P), 1));
					if (($P * 8) % 8 == 0) {
						$COLOR[1] = $COLOR[1] >> 7;
					}
					elseif (($P * 8) % 8 == 1) {
						$COLOR[1] = ($COLOR[1] & 0x40)>>6;
					}
					elseif (($P * 8) % 8 == 2) {
						$COLOR[1] = ($COLOR[1] & 0x20) >> 5;
					}
					elseif (($P * 8) % 8 == 3) {
						$COLOR[1] = ($COLOR[1] & 0x10) >> 4;
					}
					elseif (($P * 8) % 8 == 4) {
						$COLOR[1] = ($COLOR[1] & 0x8) >> 3;
					}
					elseif (($P * 8) % 8 == 5) {
						$COLOR[1] = ($COLOR[1] & 0x4) >> 2;
					}
					elseif (($P * 8) % 8 == 6) {
						$COLOR[1] = ($COLOR[1] & 0x2) >> 1;
					}
					elseif (($P * 8) % 8 == 7) {
						$COLOR[1] = ($COLOR[1] & 0x1);
					}
					$COLOR[1] = $PALETTE[$COLOR[1] + 1];
				}
				else {
					return false;
				}
				
				imagesetpixel($res, $X, $Y, $COLOR[1]);
				$X++;
				$P += $bmp['bytes_per_pixel'];
	    	}
	    	
			$Y--;
			$P += $bmp['decal'];
		}

		fclose($f1);
	
		return $res;
	}
}
?>