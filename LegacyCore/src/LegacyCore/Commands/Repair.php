<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\item\Tool;
use pocketmine\item\Armor;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class Repair extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Repair your item");
        $this->setUsage("/repair <hand|all>");
        $this->setAliases(["fix"]);
        $this->setPermission("core.command.repair");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender->hasPermission("core.command.repair")) {
            LangManager::send("cmd-noperm", $sender);
			return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
			LangManager::send("core-repair-usage", $sender);
            return false;
		}
		if (isset($args[0])) {
		    switch(mb_strtolower($args[0])) {
                case "hand":
				// Repair In Hand
                if (!$sender->getInventory()->getItemInHand() instanceof Tool and !$sender->getInventory()->getItemInHand() instanceof Armor) {
                    LangManager::send("core-repair-error", $sender);
                    return true;
                }
                    $cost = 1000000;
                    if (!Main::getInstance()->reduceMoney($sender, $cost)) {
                    	LangManager::send("money-needed", $sender, $cost);
                        return true;
                    }
                $item = $sender->getInventory()->getItemInHand();
                $item->setDamage(0);
                $sender->getInventory()->setItemInHand($item);
                LangManager::send("core-repair-hand", $sender);
                return true;
                case "all": 
                    $cost = 50000000;
                    if(!Main::getInstance()->reduceMoney($sender, $cost)) {
                        LangManager::send("money-needed", $sender, $cost);
                        return true;
                    }
                foreach($sender->getInventory()->getContents() as $index => $item) {
                    if ($item instanceof Tool || $item instanceof Armor) {
                        $item = $item->setDamage(0);
                        $sender->getInventory()->setItem($index, $item);
                    }
                }
			    foreach($sender->getArmorInventory()->getContents() as $index => $item) {
                    if ($item instanceof Tool || $item instanceof Armor) {
                        $item = $item->setDamage(0);
                        $sender->getArmorInventory()->setItem($index, $item);
			    	}
				}
                LangManager::send("core-repair-all", $sender);
                return true;
                break;
                default:
			    LangManager::send("core-repair-usage", $sender);
                return false;
			}
        }
        return true;
    }
}