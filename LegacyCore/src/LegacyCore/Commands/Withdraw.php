<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class Withdraw extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Withdraw money or experience");
        $this->setUsage("/withdraw <money|exp> <amount>");
        $this->setAliases(["wt"]);
        $this->setPermission("core.command.withdraw");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
            return false;
        }
        if (count($args) < 1) {
            LangManager::send("core-withdraw-usage", $sender);
            return false;
        }
		if (isset($args[0])) {
		    switch(mb_strtolower($args[0])) {
		    	case "token":
		    	case "tokens":
		    	    if(count($args) !== 2){
		    	    	LangManager::send("core-withdraw-usage", $sender);
		    	    	return true;
		    	    }
		    	    $tokens = intval($args[1]);
		    	    if($tokens < 1 xor $tokens > 0x7fffffff){
		    	    	LangManager::send("core-withdraw-outofbounds", $sender, 1);
		    	    	return true;
		    	    }
		    	    $item = ItemUtils::get("token_note({$tokens})");
		    	    if($sender->getInventory()->canAddItem($item)){
		    	    	if(Main::getInstance()->subtractTokens($sender, $tokens)){
		    	    		$sender->getInventory()->addItem(ItemUtils::get("token_note({$tokens})"));
		    	    		LangManager::send("core-withdraw-tokens", $sender, $tokens);
		    	    	}else{
		    	    		LangManager::send("tokens-needed", $sender, $tokens);
		    	    	}
		    	    }else{
		    	    	$sender->sendMessage("inventory-nospace");
		    	    }
		    	    return true;
		    	    break;
				case "economy":
				case "money":
				if (count($args) < 2) {
                    LangManager::send("core-withdraw-usage", $sender);
                    return false;
				}
				if (is_numeric($args[1])) {
                    $amount = (int) $args[1];
		            $bal = Main::getInstance()->myMoney($sender);
		         	if ($bal >= $amount) {
			            if ($amount >= 1000 && $amount <= 0x7fffffff){
				          	$item = ItemUtils::get("bank_note({$amount})");
				          	if($sender->getInventory()->canAddItem($item)){
				          		$sender->getInventory()->addItem($item);
				          		$sender->reduceMoney($amount);
				          		LangManager::send("core-withdraw-money", $sender, $amount);
				          	}else{
				          		$sender->sendMessage("inventory-nospace");
				          	}
				        } else {
				        	LangManager::send("core-withdraw-outofbounds", $sender, 1000);
						}
		          	} else {
		          		LangManager::send("money-needed-more", $sender, $bal - $amount);
					}
	         	} else {
		          	LangManager::send("positive-value", $sender);
				}
				return true;
				case "experience":
				case "exp":
				case "xp":
				if (count($args) < 2) {
                    LangManager::send("core-withdraw-usage", $sender);
                    return false;
				}
				if (is_numeric($args[1])) {
                    $amount = (int)$args[1];
		         	if ($amount >= 500 && $amount <= 0x7fffffff) {
		               	if ($sender->getCurrentTotalXp() - $amount <= 0) {
		               		LangManager::send("exp-needed-more", $amount - $sender->getCurrentTotalXp());
		             	} else {
				            $item = ItemUtils::get("experience_bottle({$amount})");
				            if($sender->getInventory()->canAddItem($item)){
				            	$sender->getInventory()->addItem($item);
				            	$sender->subtractXp($amount);
				          		LangManager::send("core-withdraw-exp", $sender, $amount);
				          	}else{
				          		$sender->sendMessage("inventory-nospace");
				          	}
				        }
			        } else {
			        	LangManager::send("core-withdraw-outofbounds", $sender, 500);
			        }
		        } else {
			        LangManager::send("positive-value", $sender);
				}
				return true;
			    default:
				LangManager::send("core-withdraw-usage", $sender);
			    return false;
			}
		}
	}
}
