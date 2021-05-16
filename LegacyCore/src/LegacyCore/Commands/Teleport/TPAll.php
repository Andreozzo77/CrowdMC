<?php

namespace LegacyCore\Commands\Teleport;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class TPAll extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport all players to you");
        $this->setUsage("/tpall");
        $this->setPermission("core.command.tpall");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.tpall")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		Command::broadcastCommandMessage($sender, "Teleported all players to you");
		foreach($this->plugin->getServer()->getOnlinePlayers() as $target) {
            if ($target !== $sender) {
                $target->teleport($sender);
            }
        }
		return true;
	}
}