<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\math\Vector3;
use pocketmine\scheduler\Task;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class DropTask extends Task{
	/** @var Core */
	private $plugin;
	/** @var int */
	private $dp = 0;
	/** @var int */
	private $run = -1;
    
    /**
     * DropTask constructor.
     * @param Core $plugin
     * @param Player $player
     */
    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
	}
    
	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
    	$this->run++;
    	if($this->run < 1800){
    		if($this->run % 300 === 0){
    			LangManager::broadcast("core-dp-broadcast", Main::getInstance()->formatTime(Main::getInstance()->getTimeLeft(time() + (1800 - $this->run)), TextFormat::AQUA, TextFormat::AQUA));
    		}
    	}elseif($this->run === 1){
    		$this->run = 1800;
    		
    		// - - - S T A R T  D R O P S - - -
    		$drops[] = ItemFactory::get(455, 0, \kenygamer\Core\Main::mt_rand(0, 1));
    		$drops[] = ItemFactory::get(311, 0, \kenygamer\Core\Main::mt_rand(0, 4));
    		$drops[] = ItemFactory::get(57, 0, \kenygamer\Core\Main::mt_rand(0, 2));
    		$drops[] = ItemFactory::get(264, 0, \kenygamer\Core\Main::mt_rand(0, 3));
    		$drops[] = ItemFactory::get(399, 0, \kenygamer\Core\Main::mt_rand(0, 1));
    		$drops[] = ItemFactory::get(Item::BEDROCK, 0, \kenygamer\Core\Main::mt_rand(1, 3));
    		$drops[] = ItemUtils::get("diamond_apple");
            $drops[] = ItemUtils::get("experience_bottle2(101)")->setCount(\kenygamer\Core\Main::mt_rand(0, 1)); 
            $drops[] = ItemUtils::get("mythic_note(51)")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
            
            $drops[] = ItemUtils::get(Item::IRON_SWORD, "&bIron Sword", [], [
                "deathbringer" => \kenygamer\Core\Main::mt_rand(1, 5),
                "blessed" => \kenygamer\Core\Main::mt_rand(1, 3),
                "lifesteal" => \kenygamer\Core\Main::mt_rand(1, 5)
            ]);
            
            $drops[] = ItemUtils::get(Item::DIAMOND_SWORD, "&bDiamond Sword", [], [
                "killermoney" => \kenygamer\Core\Main::mt_rand(5, 10),
                "blessed" => \kenygamer\Core\Main::mt_rand(2, 3),
                "lifesteal" => \kenygamer\Core\Main::mt_rand(3, 5)
            ]);
            $drops[] = ItemUtils::get("green_crystal")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
            $drops[] = ItemUtils::get("yellow_crystal")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
            
            $drops[] = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(0, 4));
            $drops[] = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
            $drops[] = ItemUtils::get("common_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
            $drops[] = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
            $drops[] = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 2));
            $drops[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["insanity" => \kenygamer\Core\Main::mt_rand(1, 2)]);
            $drops[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["blind" => \kenygamer\Core\Main::mt_rand(1, 5)]);
            $drops[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["overload" => \kenygamer\Core\Main::mt_rand(1, 5)]);
            $drops[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["soulbound" => \kenygamer\Core\Main::mt_rand(1, 3)]);
            $drops[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["longbow" => \kenygamer\Core\Main::mt_rand(1, 5)]);
            //- - - E N D  D R O P S - - -
            
            shuffle($drops);
            $drops = array_slice($drops, 0, 4);
            
            $world = "warzone";
            $pos = new Vector3(-5, 20, 17);
            
            $level = $this->plugin->getServer()->getLevelByName($world);
            if($level !== null){
            	$level->loadChunk($pos->getX() >> 4, $pos->getZ() >> 4);
            	foreach($drops as $drop){
            		$level->dropItem($pos, $drop);
            	}
            }else{
            	$this->plugin->getServer()->getLogger()->error("[LegacyCore] World {$world} not loaded");
            	return;
            }
            LangManager::broadcast("core-dp-start", ++$this->dp);
    	}
    }
    
}