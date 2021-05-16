<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\level\Position;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use LegacyCore\Events\Area;
use kenygamer\Core\Main2;
use kenygamer\Core\clipboard\ClipboardManager;

class PersonalMineCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"personalmine",
			"Teleport to your personal mine",
			"/personalmine",
			["pmine"],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset(Main2::$pmineGenerationLock[$sender->getName()])){
	    	$sender->sendMessage("pmine-error");
	    	return true;
	    }
	    
	    /** @var Position */
	    $pos = Position::fromObject(new Vector3(Main2::PMINE_STARTX, 10, Main2::PMINE_STARTZ), $sender->getServer()->getLevelByName(Main2::PMINE_WORLD));
	    if(!($pos->getLevel() instanceof Level)){
	    	$sender->sendMessage("world-notfound");
	    	return true;
	    }
	    
	    $mineArea = false;
	    foreach(Area::getInstance()->cmd->areas as $area){
	    	if($area->getName() === str_replace("{player}", mb_strtolower($sender->getName()), Main2::PMINE_AREA)){
	    		$mineArea = $area;
	    		break;
	    	}
	    }
	    if(!Main2::$pmines->exists($sender->getName()) || $mineArea === false){
	    	Main2::$pmineGenerationLock[$sender->getName()] = true;
	    	Main2::$pmines->remove($sender->getName());
	    	$all = Main2::$pmines->getAll();
	    	if(!empty($all)){
	    		$last = end($all);
	    		$pos->x = $last[3] + 1;
	    		$pos->z = $last[5] + 1;
	    	}
	    	$manager = ClipboardManager::getInstance();
	    	$info = $manager->getClipboardInfo(Main2::PMINE_CLIPBOARD);
	    	list($minX, $minY, $minZ, $maxX, $maxY, $maxZ) = $info;
	    	Main2::$pmines->set($sender->getName(), [$pos->getX(), $pos->getY(), $pos->getZ(), $pos->getX() + ($maxX - $minX), $pos->getY() + ($maxY - $minY), $pos->getZ() + ($maxZ - $minZ)]);
	    	$manager->pasteClipboard($pos, Main2::PMINE_CLIPBOARD, 0, [Main2::class, "personalMineGenerationCallback", $sender->getName()]);
	    	$sender->sendMessage("pmine-gen");
	    	return true;
	    }
	    
	    list($minX, $minY, $minZ, $maxX, $maxY, $maxZ) = Main2::$pmines->get($sender->getName());
	    $pos->setComponents(($minX + $maxX) / 2, Level::Y_MAX, ($minZ + $maxZ) / 2);
	    $sender->teleport($pos->getLevel()->getSafeSpawn($pos));
	    $sender->sendMessage("pmine-tp");
	    return true;
	}

}