<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\inventory\Inventory;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

use jojoe77777\FormAPI\ModalForm;
use CustomEnchants\CustomEnchants\CustomEnchants;
use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\LangManager;

class Prestige extends PluginCommand{

	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Reset to rank A");
        $this->setUsage("/prestige");
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
		if(Main::getInstance()->permissionManager->getPlayerPrefix($sender) === "Free"){
			$this->PrestigeUI($sender);
		} else {
	    	LangManager::send("core-prestige-free", $sender);
		}
		return true;
	}
	
	/**
	 * @param PrestigeUI
	 * @param Player $player
     */
	public function PrestigeUI(Player $player) : void{
		$plugin = Main::getInstance();
		$level = $plugin->getEntry($player, Main::ENTRY_PRESTIGE);
		$CE = $plugin->getPlugin("CustomEnchants");
		
		if($level === 20){
			$level = 0; //Reset: interprets current PG level so next level is 1
		}
		
		$api = $plugin->getPlugin("FormAPI");
		
		$items = [];
		$desc = "Prestige Pickaxe, ";
		if($level + 1 > 10){
			$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["explosive" => ($level + 1 - 5)]);
			if($level + 1 > 15){
				$desc .= "Frostbite " . $CE->getRomanNumber($level + 1 - 10) . ", ";
				$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["frostbite" => ($level + 1 - 10)]);
			}else{
				$desc .= "Penetrating " . $CE->getRomanNumber($level + 1 - 10) . ", ";
				$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["penetrating" => ($level + 1 - 10)]);
			}
			$desc .= "Explosive " . $CE->getRomanNumber($level + 1 - 5);
		}elseif($level + 1 > 5){
			$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["explosive" => ($level + 1 - 5)]);
			$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["frostbite" => ($level + 1 - 5)]);
			$desc .= "Explosive " . $CE->getRomanNumber($level + 1 - 5) . ", Frostbite " . $CE->getRomanNumber($level + 1 - 5);
		}else{
			$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["jackhammer" => ($level + 1)]);
			$items[] = ItemUtils::get(Item::ENCHANTED_BOOK, "", [], ["tokenmaster" => ($level + 1)]);
			$desc .= "Jackhammer " . $CE->getRomanNumber($level + 1) . ", Tokenmaster " . $CE->getRomanNumber($level + 1);
        }
        switch($level + 1){
        	case 5:
        	    $items[] = ItemUtils::get("tartarus_gem");
        	    $desc .= "Tartarus Gem, ";
        	    break;
        	case 10:
        	    $items[] = ItemUtils::get("hades_gem");
        	    $desc .= "Hades Gem, ";
        	    break;
        	case 15:
        	    $items[] = ItemUtils::get("poseidon_gem");
        	    $desc .= "Poseidon Gem, ";
        	    break;
        	case 20:
        	    $items[] = ItemUtils::get("zeus_gem");
        	    $desc .= "Zeus Gem, ";
        	    break;
        }
            
		$form = new ModalForm(function (Player $player, $data) use($level, $plugin, $items){
	    $result = $data;
		if ($result != null) {
		}
		switch($result) {
		    case true:
			$item = Item::get(278, 0, 1);
			$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 10));
			$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
			$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(18), 5));
			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(118), 5));
			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 5));
			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(207), 5));
			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(212), 10));
			$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(213), 10));
            $item->setCustomName("§r§l§cPrestige Pickaxe§r\n§6Money Farm X\n§6Grind X\n§eAutorepair V\n§eDriller V\n§bHaste V");
            $items[] = $item;
			if(!ItemUtils::addItems($player->getInventory(), ...$items)){
				LangManager::send("inventory-nospace", $player);
				return;
			}
			$plugin->registerEntry($player, Main::ENTRY_PRESTIGE, $level + 1);
			Main::getInstance()->permissionManager->setPlayerPrefix($player, "A");
			LangManager::send("core-prestige", $player);
			$player->addTitle(LangManager::translate("core-prestige-title", $player, $level + 1), LangManager::translate("core-prestige", $player));
			return;
			break;
		    }
		});
		$form->setTitle(LangManager::translate("core-prestige-title", $player, $level + 1));
        $form->setContent(LangManager::translate("core-prestige-downgrade", $player, $level + 1, $desc));
        $form->setButton1(LangManager::translate("continue", $player), 1);
        $form->setButton2(LangManager::translate("cancel", $player), 2);
        $form->sendToPlayer($player);
	}
}