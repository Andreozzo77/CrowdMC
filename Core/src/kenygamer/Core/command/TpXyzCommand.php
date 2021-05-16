<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\level\Level;
use pocketmine\level\Position;

class TpXyzCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"tpxyz",
			"Teleport to coordinates in the given world",
			"/tpxyz <x> <y> <z> [world]",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$x = array_shift($args);
		$y = array_shift($args);
		$z = array_shift($args);
		$world = array_shift($args) ?? $sender->getLevel()->getFolderName();
		
		if(trim($world) !== ""){
			if(!$this->getPlugin()->getServer()->isLevelLoaded($world)){
				if(!$this->getPlugin()->getServer()->isLevelGenerated($world)){
					$sender->sendMessage("world-notfound");
					return true;
				}
				$this->getPlugin()->getServer()->loadLevel($world);
			}
		}
		$level = $this->getPlugin()->getServer()->getLevelByName($world);
		$spawn = $level->getSafeSpawn();
		
		if(!is_numeric($x)){
			if(stripos($x, "+") !== false){
				$x = str_replace("+", "", $x);
				$x += $sender->getX();
			}elseif(stripos($x, "-") !== false){
				$garbage = preg_replace("/([0-9]*)+(-)/", "", $x);
				$x = str_replace($garbage, "", $x);
				$x = $sender->getY() - $x;
			}elseif(substr($x, 0, 1) === "~"){
				$x = $sender->getX();
			}else{
				$x = $level->getSpawnLocation()->getX();
			}
		}
		if(!is_numeric($y)){
			if(stripos($y, "+") !== false){
				$y = str_replace("+", "", $y);
				$y += $sender->getY();
			}elseif(stripos($y, "-") !== false){
				$garbage = preg_replace("/([0-9]*)+(-)/", "", $y);
				$y = str_replace($garbage, "", $y);
				$y = $sender->getY() - $y;
			}elseif(substr($y, 0, 1) === "~"){
				$y = $sender->getY();
			}else{
				$y = $level->getSpawnLocation()->getY();
			}
		}
		if(!is_numeric($z)){
			if(stripos($z, "+") !== false){
				$z = str_replace("+", "", $z);
				$z += $sender->getZ();
			}elseif(stripos($z, "-") !== false){
				$garbage = preg_replace("/([0-9]*)+(-)/", "", $z);
				$z = str_replace($garbage, "", $z);
				$z = $sender->getZ() - $z;
			}elseif(substr($z, 0, 1) === "~"){
				$z = $sender->getZ();
			}else{
				$z = $level->getSpawnLocation()->getZ();
			}
		}
		$sender->teleport(new Position((float) $x, (float) $y, (float) $z, $level));
	    $sender->sendMessage("tpxyz-teleported", $x, $y, $z, $level->getFolderName());
	    return true;
	}
	
}