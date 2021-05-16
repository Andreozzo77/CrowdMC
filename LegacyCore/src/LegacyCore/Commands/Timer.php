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

class Timer extends PluginCommand{
	
	/** @var array */
	public $timer;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Change the time of the world");
        $this->setUsage("/time <day/night/start/stop>");
        $this->setAliases(["time"]);
        $this->setPermission("core.command.time");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.time")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-time-usage", $sender);
            return false;
        }
		if (!isset($this->plugin->timer[$sender->getLowerCaseName()]) || time() > $this->plugin->timer[$sender->getLowerCaseName()] || $sender->hasPermission("core.cooldown.bypass")) {
            $this->plugin->timer[$sender->getLowerCaseName()] = time() + 60;
	    	if (isset($args[0])) {
		        switch(mb_strtolower($args[0])) {
			        case "day":
				    $sender->getLevel()->setTime(0);
					Command::broadcastCommandMessage($sender, "Set the time to day.");
				    return true;
				    case "night":
				    $sender->getLevel()->setTime(14000);
					Command::broadcastCommandMessage($sender, "Set the time to night.");
                    return true;
					case "stop":
				    $sender->getLevel()->stopTime();
					Command::broadcastCommandMessage($sender, "Stopped the time.");
				    return true;
					case "start":
				    $sender->getLevel()->startTime();
					Command::broadcastCommandMessage($sender, "Restarted the time.");
				    return true;
			        default:
				    LangManager::send("core-time-usage", $sender);
			        return false;
				}
			}
		} else {
			LangManager::send("in-cooldown", $sender);
			return false;
		}
	}
}