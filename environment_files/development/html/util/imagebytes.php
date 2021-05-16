<?php

$path = "Blue_Creeper.png";
$img = @imagecreatefrompng($path);
$skinbytes = "";
$s = (int)@getimagesize($path)[1];

for($y = 0; $y < $s; $y++) {
	for($x = 0; $x < 64; $x++) {
		$colorat = @imagecolorat($img, $x, $y);
		$a = ((~((int)($colorat >> 24))) << 1) & 0xff;
		$r = ($colorat >> 16) & 0xff;
		$g = ($colorat >> 8) & 0xff;
		$b = $colorat & 0xff;
		$skinbytes .= chr($r) . chr($g) . chr($b) . chr($a);
    }
}

@imagedestroy($img);

file_put_contents("image.js", json_encode(["data" => base64_encode($skinbytes)]));
echo "done";