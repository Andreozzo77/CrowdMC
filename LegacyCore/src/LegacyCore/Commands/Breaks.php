<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use LegacyCore\Events\Area;

class Breaks extends PluginCommand{
	
	/** @var array */
	public $plugin;

    public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Breaks the bedrock you are looking at");
        $this->setUsage("/break");
        $this->setAliases(["break"]);
        $this->setPermission("core.command.break");
		$this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if(!$sender->hasPermission("core.command.break")){
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if($sender instanceof ConsoleCommandSender){
			LangManager::send("run-ingame", $sender);
			return false;
		}
        if(($block = $sender->getTargetBlock(5, [Block::AIR])) === null){
            LangManager::send("core-break-unreachable", $sender);
            return false;
        }else{
        	if($block->getId() !== Block::BEDROCK){
        		LangManager::send("core-break-bedrock", $sender);
        		return false;
			}
        }
		$cost = 5000;
		$area_listener = Area::getInstance();
		if(!$area_listener->cmd->canEdit($sender, $block)){
			LangManager::send("core-break-unreachable", $sender);
			return false;
		}
		
        if($sender->getCurrentTotalXp() - $cost < 0){
        	LangManager::send("exp-needed", $sender, $cost);
	    }elseif(!$sender->getInventory()->canAddItem($item = $block->getPickedItem())){
	    	$sender->sendMessage("inventory-nospace");
	    }else{
	    	$sender->getInventory()->addItem($item);
			$sender->subtractXp($cost);
			LangManager::send("core-break", $sender);
            $sender->getLevel()->setBlock($block, new Air(), true, true);
		}
		return true;
    }
} 