<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\utils\Color;

/**
 * @class ImageUtils
 * NOTE: Several of the methods here return resources.
 * Since a resource can cause a memory leak always close them
 * after using them with fclose()
 */
class ImageUtils{
	
	/**
	 * @param Color[] $colors
	 * @return resource
	 */
	public static function colorArrayToImage(array $colors){
		$img = imagecreatetruecolor(128, 128);
		imagefill($img, 0, 0, imagecolorallocate($img, 0, 0, 0));
		imagesavealpha($img, true);
		for($y = 0; $y < 128; $y++){
			for($x = 0; $x < 128; $x++){
				$color = $colors[$y][$x];
				$col = imagecolorallocate($img, $color->getR(), $color->getG(), $color->getB());
				imagefill($img, $x, $y, $col);
			}
		}
		return $img;
	}
	
	/**
	 * Splits the image in $parts.
	 * NOTE: This method can be a memory leak if you don't close the resources.
	 *
	 * @param resource $img_orig
	 * @param int $parts
	 *
	 * @return array
	 */
	public static function splitImage($img_orig, int $parts) : array{
		if(!is_resource($img_orig)){
			throw new \InvalidArgumentException("Argument 1 must be of the type resource, " . gettype($img_orig) . " given");
		}
		
		$img_orig_width = imagesx($img_orig);
		$img_orig_height = imagesy($img_orig);
		$per_img_width = (int) round($img_orig_width / $parts);
		$per_img_height = (int) round($img_orig_height / $parts);
		
		$imgs = [];
		for($x = 0; $x < $img_orig_width; $x += $per_img_width){
			for($y = 0; $y < $img_orig_height; $y += $per_img_height){
				$img = imagecreatetruecolor((int) round($per_img_width), (int) round($per_img_height));
				imagecopy($img, $img_orig, 0, 0, $x, $y, (int) round($per_img_width), (int) round($per_img_height));
				$imgs[] = $img;
			}
		}
		return $imgs;
	}
	
	/**
	 * Returns an array with all the colors of the image.
	 *
	 * @param resource $img
	 * @return Color[]
	 */
	public static function imageToColorArray($img) : array{
		if(!is_resource($img)){
			throw new \InvalidArgumentException("Argument 1 must be of the type resource, " . gettype($img) . " given");
		}
		$colors = [];
		for($x = 0; $x < imagesx($img); $x++){
			for($y = 0; $y < imagesy($img); $y++){
    			$rgba = imagecolorat($img, $x, $y);
				$colors[$y][$x] = new Color(($rgba >> 16) & 0xff, ($rgba >> 8) & 0xff, $rgba & 0xff, (127 - (($rgba >> 24) & 0x7f)) * 2);
				
			}
		}
		return $colors;
	}
	
	/**
	 * Resizes the image in $path.
	 *
	 * @param string $path
	 * @param int $width
	 * @param int $height
	 * @param bool $crop
	 */
	public static function resizeImage(string $path, int $width, int $height, $crop = false){
        list($width, $height) = getimagesize($path);
        $ratio = $width / $height;
        if($crop){
            if($width > $height){
                $width = ceil($width - ($width * abs($ratio - $width / $height)));
            }else{
                $height = ceil($height - ($height * abs($ratio - $width / $height)));
            }
            $new_width = $width;
            $new_height = $height;
        }else{
            if($width / $height > $ratio){
                $new_width = $height * $ratio;
                $new_height = $height;
            }else{
                $new_height = $width / $ratio;
                $new_width = $width;
            }
        }
        $src = imagecreatefrompng($path);
        $dst = imagecreatetruecolor($width, $height);
        imagecolortransparent($dst, imagecolorallocatealpha($dst, 0, 0, 0, 127));
        imagealphablending($dst, false);
        imagesavealpha($dst, true);
        imagecopyresampled($dst, $src, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
		return $dst;
	}
	
}