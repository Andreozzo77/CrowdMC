<?php

namespace LegacyCore\Commands\Teleport;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

//TODO rewrite
class TP extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport to player");
        $this->setUsage("/teleport <player>");
        $this->setAliases(["tp"]);
        $this->setPermission("core.command.teleport");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.teleport")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-tp-usage", $sender);
            return false;
        }
		if (!empty($args[0])) {
            $target = $this->plugin->getServer()->getPlayer($args[0]);
			if ($target == null) {
                LangManager::send("player-notfound", $sender);
				return false;
			}
			if ($target === $sender) {
				LangManager::send("core-tp-other", $sender);
			    return false;
			}
			/*if($sender->getGamemode() === Player::SPECTATOR){
				LangManager::send("core-tp-spectator", $sender);
				return true;
			}*/
            if ($target == true) {
			    $sender->teleport($target);
				Command::broadcastCommandMessage($sender, "Teleported " . $sender->getName() . " to " . $target->getName());
				return true;
            }
		}
		return true;
	}
}