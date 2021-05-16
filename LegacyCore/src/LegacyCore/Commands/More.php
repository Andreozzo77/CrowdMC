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
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;

use kenygamer\Core\LangManager;

class More extends PluginCommand{

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Get a stack of the item in hand");
        $this->setUsage("/more");
        $this->setPermission("core.command.more");
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.more")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		$item = $sender->getInventory()->getItemInHand();
        if ($item->getId() === Item::AIR) {
            LangManager::send("hold-item", $sender);
            return false;
        }
        $item->setCount(64);
        $sender->getInventory()->setItemInHand($item);
        LangManager::send("core-more", $sender, $item->getName());
        return true;
	}
}