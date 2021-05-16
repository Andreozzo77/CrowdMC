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
use pocketmine\plugin\Plugin;

class World extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport between worlds or List worlds");
        $this->setUsage("/world <list|world name>");
        $this->setPermission("core.command.world");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.world")) {
			$sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
		}
		if ($sender instanceof ConsoleCommandSender) {
			$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
			return false;
		}
		if (count($args) < 1) {
            $sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /world <list|world name>");
            return false;
        }
		// World List
		if (mb_strtolower($args[0]) == "list") {
			$levels = [];
            foreach(scandir($this->plugin->getServer()->getDataPath() . "worlds") as $file) {
                if ($this->plugin->getServer()->isLevelGenerated($file)) {
                    $isLoaded = $this->plugin->getServer()->isLevelLoaded($file);
                    $players = 0;
                    if ($isLoaded) {
                        $players = count($this->plugin->getServer()->getLevelByName($file)->getPlayers());
					}
                    $levels[$file] = [$isLoaded, $players];
				}
			}
			$sender->sendMessage(TextFormat::GREEN . "§l»§r" . TextFormat::GOLD . " World list " . TextFormat::GREEN . "§l«");
            foreach($levels as $level => [$loaded, $players]) {
                $loaded = $loaded ? TextFormat::BLUE . "Loaded" : TextFormat::RED . "Unloaded";
				$sender->sendMessage(TextFormat::GREEN . "World: " . TextFormat::AQUA . $level . TextFormat::GRAY . " -> " . $loaded . TextFormat::GREEN . " Player: " . TextFormat::AQUA . $players);
			}
			return true;
		}
		// World Teleport
		if (!$sender->getServer()->isLevelGenerated($args[0])) {
            $sender->sendMessage(TextFormat::RED . "World doesn't exist");
            return false;
		}
		if (!$sender->getServer()->isLevelLoaded($args[0])) {
            $sender->sendMessage(TextFormat::YELLOW . "Level is not loaded yet. Loading...");
			return false;
		}
        if (!$sender->getServer()->loadLevel($args[0])) {
            $sender->sendMessage(TextFormat::RED . "The level couldn't be loaded");
            return false;
        }
		$sender->teleport($this->plugin->getServer()->getLevelByName($args[0])->getSpawnLocation(), 0, 0);
        $sender->sendMessage(TextFormat::GREEN . "Teleporting...");
		return true;
	}
}