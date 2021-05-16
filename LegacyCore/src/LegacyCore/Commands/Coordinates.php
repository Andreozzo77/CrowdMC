<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class Coordinates extends PluginCommand{
	/** @var Core */
	private $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Show/hide XYZ");
        $this->setUsage("/coordinates");
        $this->setAliases(["coords"]);
		$this->setPermission("core.command.coordinates");
		$this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$sender->hasPermission("core.command.coordinates")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if($sender->getLevel()->getFolderName() === "maze"){
			LangManager::send("tpa-disallowed", $sender);
			return false;
		}
		// Coordinates
        $status = Main::getInstance()->getEntry($sender, Main::ENTRY_COORDINATES) ?? false;
        if(!$status || (isset($args[0]) && $args[0] === "on")){
			$pk = new GameRulesChangedPacket();
            $pk->gameRules = ["showcoordinates" => [1, true]];
            $sender->dataPacket($pk);
			LangManager::send("core-coords-on", $sender);
		}else{
			$pk = new GameRulesChangedPacket();
            $pk->gameRules = ["showcoordinates" => [1, false]];
            $sender->dataPacket($pk);
			LangManager::send("core-coords-off", $sender);
		}
		return true;
    }
}
