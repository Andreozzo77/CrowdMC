<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Durable;
use pocketmine\item\Item;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\TextFormat;

use CustomEnchants\CustomEnchants\CustomEnchants;
use kenygamer\Core\LangManager;

class Ceremoveall extends PluginCommand{

    /** @var array */
    public $plugin;

    public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Remove all enchants of the item held");
        $this->setUsage("/ceremoveall");
        $this->setAliases(["ceremoveall"]);
        $this->setPermission("core.command.ceremoveall");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if (!$sender instanceof Player){
            LangManager::send("run-ingame", $sender);
            return true;
        }
        $item = $sender->getInventory()->getItemInHand();
        if ($item->isNull() || $item->getId() === Item::ENCHANTED_BOOK || !$this->isEnchanted($item)){
            LangManager::send("core-ceremove-item", $sender);
            return false;
        }
        $ench = $item->getNamedTagEntry(Item::TAG_ENCH);
        
        $cost = 0;
        $enchant_books = [];
        $enchants = $item->getEnchantments();
        foreach($enchants as $enchant){
        	$ce = CustomEnchants::getEnchantment($enchant->getType()->getId());
        	if($ce instanceof CustomEnchants){
        		$enchant_book = Item::get(340, 0, 1);
        		$enchant_books[] = $this->addEnchantment($enchant_book, [$enchant->getType()->getId()], [$enchant->getLevel()]);
        		$item = $this->removeEnchantment($item, $ce);
        		$cost += 5000;
        	}else{
        		//Vanilla enchant
        	}
        }
        
        if(count($enchant_books) > 0){
        	
        	$fakeinv = new \pocketmine\inventory\PlayerInventory($sender);
        	$fakeinv->setContents($sender->getInventory()->getContents());
        	foreach($enchant_books as $book){
        		if(!$fakeinv->canAddItem($book)){
        			LangManager::send("inventory-nospace", $sender);
        			return true;
        		}else{
        			$fakeinv->addItem($book);
        		}
        	}
        	
        	if($sender->getCurrentTotalXp() - $cost <= 0){
        		LangManager::send("exp-needed", $cost);
        		return true;
        	}
        	$sender->subtractXp($cost);
        	
        	
        	$sender->getInventory()->setContents($fakeinv->getContents()); //Now 100% sure player has slot so we add up the ces.
        	$sender->getInventory()->setItemInHand($item);
        	LangManager::send("core-ceremoveall", $sender, count($enchant_books));
        }else{
        	//$vanilla = count($enchants) - count($enchant_books);
        	LangManager::send("core-ceremoveall-none", $sender);
        }
        return true;
	}

	/**
	 * @param addEnchantment
     */
	public function addEnchantment(Item $item, array $enchants, array $levels) : Item{
        return $this->plugin->getServer()->getPluginManager()->getPlugin("CustomEnchants")->addEnchantment($item, $enchants, $levels);
    }
	
	/**
	 * @param removeEnchantment
     */
	public function removeEnchantment(Item $item, CustomEnchants $enchant){
        return $this->plugin->getServer()->getPluginManager()->getPlugin("CustomEnchants")->removeEnchantment($item, $enchant);
    }
	
	/**
	 * @param isEnchanted
     */
	public function isEnchanted(Item $item, bool $checkBook = false) : bool{
        if ($checkBook){
            if ($item->getId() !== Item::BOOK){
                return false;
            }
        }
        return $item->hasEnchantments() && $item->getNamedTagEntry(Item::TAG_ENCH) instanceof ListTag;
    }
    
}