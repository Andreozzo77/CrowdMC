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

class Ceremove extends PluginCommand{

    /** @var array */
    public $plugin;

    public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Take out an enchant of the item held");
        $this->setUsage("/ceremove <enchant>");
        $this->setAliases(["ceremove"]);
        $this->setPermission("core.command.ceremove");
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
            LangManager::send("cmd-noperm", $sender);
            return true;
        }
        if (count($args) < 1) {
            LangManager::send("core-ceremove-usage", $sender);
            return false;
		}
        if (!isset($args[0])){
            return false;
        }
        $enchant = $this->getEnchantmentByName(mb_strtolower($args[0]));
        if ($enchant === null){
        	LangManager::send("core-ceremove-enchant", $sender);
        	return true;
        }
		$item = $sender->getInventory()->getItemInHand();
        if ($item->getId() === Item::AIR || $item->getId() === Item::ENCHANTED_BOOK || !$this->isEnchanted($item, false)){
            LangManager::send("core-ceremove-item", $sender);
            return false;
        }
		$cost = 5000;
		if ($sender->getCurrentTotalXp() - $cost <= 0) {
			LangManager::send("exp-needed", $sender, $cost);
			return true;
		}
        $enchants = [$enchant->getId()];
        $level = [];
        $ench = $item->getNamedTagEntry(Item::TAG_ENCH);
        foreach($ench as $key => $entry){
        	if ($entry->getShort("id") === $enchant->getId()) {
        		$level[] = $entry->getShort("lvl");
        		break;
        	}
        }
        if (empty($level)) {
        	LangManager::send("core-ceremove-notfound", $sender, mb_strtolower($args[0]));
        	return true;
        }
        $book = Item::get(340, 0, 1);
        $book = $this->addEnchantment($book, $enchants, $level);
        if(!$sender->getInventory()->canAddItem($book)) {
        	LangManager::send("inventory-nospace", $sender);
        	return true;
        }
		$sender->subtractXp($cost);
        $sender->getInventory()->addItem($book);
        $sender->getInventory()->setItemInHand($this->removeEnchantment($item, $enchant));
        LangManager::send("core-ceremove", $sender, mb_strtolower($args[0]));
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
	 * @param getEnchantmentByName
     */
	public function getEnchantmentByName(string $enchant) : ?CustomEnchants{
        return CustomEnchants::getEnchantmentByName($enchant);
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