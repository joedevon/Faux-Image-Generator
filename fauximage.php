<?php
/**
 * Dynamically generate background images for use w/ Faux Columns
 * Note: All variables MUST be in lowercase since we will be caching we want to increase cache hits
 * @author Joe Devon
 * @link http://mysqltalk.wordpress.com/2009/11/01/faux-image-generator/
 */
class Faux_Image
{
	private $_img;
	//Set some vars
	private $_imgType;//valid vals are: png, gif, jpg
	private $_bgColor, $_bgWidth, $_bgHeight;//background
	//border
	private $_bdLoc;//border location (valid vals: top, right, bottom, left)
	private $_bdColor, $_bdSize;//border color, size in pixels
	private $_border;//will be true if all the border params exist and validate
	
	/**
	 * Constructor
	 * @todo Add gradient support
	 * 
	 * @param string $imgType (png || gif || png)
	 * 
	 * Background vals:
	 * @param string $bgColor (background color in Hex, OMIT the #)
	 * @param integer $bgWidth (up to $maxWidthChars defined in constructor)
	 * @param integer $bgHeight (up to $maxHeightChars)
	 * 
	 * Border vals:
	 * @param string $bdLoc (top || right || bottom || left)
	 * @param string $bdColor (border color in Hex, OMIT the #)
	 * @param integer $bdSize (up to $maxBorderChars)
	 * 
	 * @return void
	 */
	public function __construct($imgType, $bgColor, $bgWidth, $bgHeight, $bdLoc = NULL, $bdColor = NULL, $bdSize = NULL) 
	{
		// Change the following if for some crazy reason you need enormous background images
		$maxWidthChars = 4;// max chars in width i.e. 4=9999
		$maxHeightChars = 5;// max chars in height ie 5=99999
		$maxBorderChars = 2;// max chars in border ie 2=99
		
		// validate vals
		if(
			( // there must be an image type and it must have a corresponding image creation ability
				!(preg_match("/^jpg$/", $imgType) AND function_exists("imagejpeg")) AND
				!(preg_match("/^gif$/", $imgType) AND function_exists("imagegif")) AND
				!(preg_match("/^png$/", $imgType) AND function_exists("imagepng")) 
			)
			|| strlen($bgWidth) > $maxWidthChars 
			|| strlen($bgHeight) > $maxHeightChars
		) {
			throw new Exception('Invalid parameters');
		}
		$this->_imgType = $imgType;
		// validate background color
		if(!$this->_bgColor = $this->_validateAndFilterColor($bgColor)) {
			throw new Exception('Invalid color');
		}
		$this->_bgWidth = $bgWidth;
		$this->_bgHeight = $bgHeight;
		// validate border if any
		if(isset($bdLoc) AND isset($bdColor) AND ($bdSize > 0)) {

			if(!preg_match("/^(top|right|bottom|left)$/", $bdLoc)
			OR !($this->_bdColor = $this->_validateAndFilterColor($bdColor))
			OR (strlen($bdSize) > $maxBorderChars) ) {
				throw new Exception('Invalid border values'); //invalid border vals
			}
			$this->_border = 1; //we've got a valid border!
			$this->_bdSize = $bdSize;
			$this->_bdLoc = $bdLoc;
		}
		// Create canvas
		$this->_img = imagecreatetruecolor($bgWidth, $bgHeight);
		// Allocate colors
		list($r, $g, $b) = $this->_hexColor2Dec($this->_bgColor);
		$backgroundColor = imagecolorallocate($this->_img, $r, $g, $b);

		// build image differently depending on border
		if(!(isset($this->_border) AND $this->_border == 1)) {
			imagefilledrectangle($this->_img, 0, 0, $bgWidth -1, $bgHeight - 1, $backgroundColor); // create background image
		} else {
			list($r, $g, $b) = $this->_hexColor2Dec($this->_bdColor);
			$borderColor = imagecolorallocate($this->_img, $r, $g, $b);
			// build image w/ borders
			switch($bdLoc) {
				case "top":
					imagefilledrectangle($this->_img, 0, $this->_bdSize, $this->_bgWidth -1, $this->_bgHeight - 1, $backgroundColor);
					imagefilledrectangle($this->_img, 0, 0, $this->_bgWidth -1, $this->_bdSize, $borderColor);
					break;
				case "right":
					imagefilledrectangle($this->_img, 0, 0, $this->_bgWidth - ($this->_bdSize + 1), $this->_bgHeight - 1, $backgroundColor);
					imagefilledrectangle($this->_img, $this->_bgWidth - ($this->_bdSize + 1), 0, $this->_bgWidth - 1, $this->_bgHeight - 1, $borderColor);
					break;
				case "bottom":
					imagefilledrectangle($this->_img, 0, 0, $this->_bgWidth -1, $this->_bgHeight - ($this->_bdSize + 1), $backgroundColor);
					imagefilledrectangle($this->_img, 0, $this->_bgHeight - ($this->_bdSize + 1), $this->_bgWidth -1, $this->_bgHeight - 1, $borderColor);
					break;
				case "left":
					imagefilledrectangle($this->_img, $this->_bdSize, 0, $this->_bgWidth -1, $this->_bgHeight - 1, $backgroundColor);
					imagefilledrectangle($this->_img, 0, 0, $this->_bdSize, $this->_bgHeight -1, $borderColor);
					break;
			} //end switch($bdLoc)
		} //end if($border ==1)
	}

	/**
	 * Validate color and convert 3 colors to 6 (6ef to 66eeff for example)
	 * @param string $color
	 * @return string
	 */
	private function _validateAndFilterColor($color) 
	{
		if(preg_match("/^([0-9a-f]{3}|[0-9a-f]{6})$/i", $color)) {
			return (strlen($color) == 3) //e.g. #f0c
			? $color[0] . $color[0] . $color[1] . $color[1] . $color[2] . $color[2] //convert to #ff00cc
			: $color;//we have 6 vals already
		} else {
			return FALSE;
		}
	}

	/**
	 * Convert hexadecimal color to decimal e.g. list ($r, $g, $b) = color_hex2dec ('FFEECC');
	 * @param string $color
	 * @return array
	 */
	private function _hexColor2Dec($color) 
	{
		return array (hexdec (substr ($color, 0, 2)), hexdec (substr ($color, 2, 2)), hexdec (substr ($color, 4, 2)));
	}

	/**
	 * Output image
	 * @param string $file Optional (/path/to/img)
	 * return void
	 */
	public function outputImage($file = NULL) 
	{
		if(!isset($file)) {
			header('Content-type: image/' . $this->_imgType);
		} else {
			//we're going to have to refactor and put back all those protect $this again SIGH
			$file = ($this->_border == 1) 
			? strtolower($file . $this->_bgColor . $this->_bgWidth . 'x' . $this->_bgHeight . $this->_bdLoc . $this->_bdColor 
			. $this->_bdSize . '.' . $this->_imgType)
			: strtolower($file . $this->_bgColor . $this->_bgWidth . 'x' . $this->_bgHeight . '.' . $this->_imgType);
		}
		switch($this->_imgType) {
			case "png":
				imagepng($this->_img, $file);
				break;
			case "jpg":
				imagejpeg($this->_img, $file);
				break;
			case "gif":
				imagegif($this->_img, $file);
				break;
			default:
				throw new Exception('Invalid image');
		}
	}

	/**
	 * Destroy image
	 * @return void
	 */
	public function destroyImage()
	{
		imagedestroy($this->_img);
		return;
	}
}
//try to Instantiate object:
try {
	//do we have a border or just a background?
	$bgImage = (isset($_GET['bdLoc']) AND isset($_GET['bdColor']) AND isset($_GET['bdSize']))
	? new Faux_Image($_GET['imgType'], $_GET['bgColor'], $_GET['bgWidth'], $_GET['bgHeight'], $_GET['bdLoc'], $_GET['bdColor'], $_GET['bdSize'])
	: new Faux_Image($_GET['imgType'], $_GET['bgColor'], $_GET['bgWidth'], $_GET['bgHeight']);
	//save to file, then output, use your own path of course
	//ALERT DANGER WILL ROBINSON, DO NOT PASS THIS IN FROM USER INPUT
	//ALERT make sure this is a safe path to the directory you are keeping your images.
	//And to make sure you don't mistakenly overwrite images, I'd suggest a directory only for imgs created by this program
	$bgImage->outputImage('/path/to/fauximages/');
	$bgImage->outputImage();
	$bgImage->destoryImage();//free up the memory
} catch (Exception $e) {
	header("HTTP/1.0 404 Not Found");//issue a 404 for an invalid color	
}