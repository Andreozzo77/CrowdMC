<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\utils\TextFormat;
use pocketmine\utils\Color;
use pocketmine\utils\Utils;
use kenygamer\Core\util\ImageUtils;
use kenygamer\Core\util\MapImageEngine;
use kenygamer\Core\listener\MiscListener2;

class PlaceImageCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"placeimage",
			"Place image",
			"/placeimage <path> <scale>",
			["placeimg"],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$path = array_shift($args);
		if(!($info = @getimagesize($path)) || $info["mime"] !== "image/png"){
			$sender->sendMessage(TextFormat::GRAY . "Invalid PNG " . $path);
			return true;
		}
		$size = (int) array_shift($args);
		if($size < 0){
			$sender->sendMessage("positive-value");
			return true;
		}
		
		$A_POWER_OF_TWO = 2 << ($bits = 7); //Left-hand argument / binary representation (2) is shifted 7 bits (right-hand argument)
		//2 << 7 = 2 ^ 8 = 256
		
		if($size > 3){ //> $A_POWER_OF_TWO = 512, 1024, etc, but we'll set 3 max because larger exhausts memory for 128*128 image
			$sender->sendMessage("range-error", 0, $bits);
			return true;
		}
		$scale = 2 << $size; //Powers of 2
		
		$img = imagecreatefrompng($path);
		imagescale($img, $A_POWER_OF_TWO, $A_POWER_OF_TWO, IMG_NEAREST_NEIGHBOUR);
		
		//is_float(imagesx($img) / $size) || is_float(imagesy($img) / $size)
		$images = [];
		var_dump($scale);
		foreach(ImageUtils::splitImage($img, $scale) as $part){
			$part = imagescale($part, MapImageEngine::MAP_WIDTH, MapImageEngine::MAP_HEIGHT, IMG_NEAREST_NEIGHBOUR);
			$array = ImageUtils::imageToColorArray($part);
			if(count($array) > 0){
				$images[] = $array;
			}
			imagedestroy($part);
		}
		imagedestroy($img);
		//count($images) < 1
		$sender->sendMessage(TextFormat::GRAY . "Place the image clicking item frames from the top left downwards. (" . sqrt(count($images)) . " * " . sqrt(count($images)) . ")");
		MiscListener2::$placeImage[$sender->getName()] = [
			$images, 0
		];
		return true;
	}
	
}