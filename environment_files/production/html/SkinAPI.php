<?php

define("SERVER_PATH", "/home/crowdmc");
define("SKIN_DEFAULT", __DIR__ . "/steve_skin.png");

file_put_contents("GET.txt", $_GET["player"] ?? "");
if(isset($_GET["player"]) && file_exists($path = SERVER_PATH . "/plugin_data/LegacyCore/player/" . mb_strtolower($_GET["player"]) . ".yml")){
	$config = yaml_parse(file_get_contents($path));
	if(base64_decode($config["Skin"], true)){
		$skin = base64_decode($config["Skin"]);
	    $len = strlen($skin);
		if($len === 64 * 32 * 4){
			$width = 64;
			$height = 32;
		}elseif($len === 64 * 64 * 4){
			$width = 64;
			$height = 64;
		}elseif($len === 128 * 128 * 4){
			$width = 128;
			$height = 128;
		}
		$img = imagecreatetruecolor($width, $height);
		imagecolortransparent($img, imagecolorallocate($img, 0, 0, 0));
		for($x = 1; $x < $width; $x++){
			for($y = 1; $y < $height; $y++){
				$pos = ($y * $width + $x) * 4;
				list($red, $green, $blue, $alpha) = array_values(unpack("C4", substr($skin, $pos, $pos + 4)));
				imagesetpixel($img, $x, $y, imagecolorallocate($img, $red, $green, $blue));
			}
		}
		header("Content-Type: image/png");
		$head = imagecreatetruecolor($width, $height);
		$src = $width / 8;
		imagecopyresampled($head, $img, 0, 0, $src, $src, $width, $height, $src, $src);
		$head = imagescale($head, 128, 128);
		imagepng($head);
		imagepng($img);
		@imagedestroy($head);
		exit;
	}
}