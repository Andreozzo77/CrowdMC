<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class Back extends PluginCommand{
	
	/** @var array */
	public $plugin;
	/** @var array */
	public $back;

    public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport to your previous position");
        $this->setUsage("/back");
        $this->setPermission("core.command.back");
		$this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if (!$sender->hasPermission("core.command.back")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (!isset($this->plugin->back[$sender->getName()]) || ($pos = $this->plugin->back[$sender->getName()])->getLevel() === null || !in_array($pos->getLevel()->getFolderName(), Main::TP_WORLDS)){
            LangManager::send("core-back-error", $sender);
            return true;
        }
        $sender->teleport($this->plugin->back[$sender->getName()]);
        unset($this->plugin->back[$sender->getName()]);
        LangManager::send("teleporting", $sender);
		return true;
    }
} 