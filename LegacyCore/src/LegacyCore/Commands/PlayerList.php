<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\TextFormat;
use pocketmine\plugin\Plugin;
 
use kenygamer\Core\LangManager;

class PlayerList extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Retrieve the player list");
        $this->setUsage("/playerlist");
        $this->setAliases(["list", "playerlist"]);
        $this->setPermission("core.command.list");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.list")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		$online = count($this->plugin->getServer()->getOnlinePlayers());
        $max = $this->plugin->getServer()->getMaxPlayers();
		$list = array_map(function(Player $player) {
			return TextFormat::clean($player->getDisplayName());
		}, array_filter($sender->getServer()->getOnlinePlayers(), function(Player $player) use ($sender){
			return $player->isOnline() and (!($sender instanceof Player) or $sender->canSee($player));
		}));
		LangManager::send("core-list", $sender, $online, $max, implode(", ", $list));
        return true;
	}
}