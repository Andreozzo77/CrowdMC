<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use LegacyCore\Events\PlayerEvents;

class PlayerStats extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("View a player stats");
        $this->setUsage("/playerstats <player>");
        $this->setAliases(["stats"]);
        $this->setPermission("core.command.stats");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.stats")) {
			$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
			return true;
		}
		if ($sender instanceof ConsoleCommandSender) {
			$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
			return true;
		}
		if(!isset($args[0])){
			$sender->sendMessage(TextFormat::colorize("&fUsage: &b/playerstats <player>"));
			return true;
		}
		$this->StatsUI($sender, $player = mb_strtolower($args[0]));
		return true;
	}
	
	/**
	 * @param StatsUI
	 * @param Player $sender
	 * @param string $player
     */
	public function StatsUI(Player $sender, string $player) : void{
		$pl = $this->plugin->getServer()->getPlayer($player);
		if(!($pl instanceof Player)){
			LangManager::send("player-notfound", $sender);
			return;
		}
	    $form = new CustomForm(null);
		$name = $pl->getName();
		$ping = $pl->getPing();
		$exp = $pl->getCurrentTotalXp();
		
		$plugin = Main::getInstance();
		$kills = $plugin->getEntry($pl, Main::ENTRY_KILLS);
		$deaths = $plugin->getEntry($pl, Main::ENTRY_DEATHS);
		$blocksPlaced = $plugin->getEntry($pl, Main::ENTRY_BLOCKS_PLACED);
		$blocksBroken = $plugin->getEntry($pl, Main::ENTRY_BLOCKS_BROKEN);
		$streak = $plugin->getEntry($pl, Main::ENTRY_KILL_STREAK);
		$tokens = $plugin->getTokens($pl);
		
		$economy = $plugin->myMoney($pl);
		$faction = $plugin->getPlugin("FactionsPro")->getPlayerFaction($pl->getName());
		
		$manager = Main::getInstance()->permissionManager;
		$group = $manager->getPlayerGroup($pl)->getName();
		$prefix = $manager->getPlayerPrefix($pl);
		$prestige = intval($plugin->getEntry($pl, Main::ENTRY_PRESTIGE));
		$kdr = $plugin->getKDR($pl);
		
		$form->setTitle(LangManager::translate("core-stats-title", $sender, $pl->getName()));
		$form->addLabel(LangManager::translate("core-stats-desc", $sender, $economy, $exp, $tokens, $group, $prefix, $prestige, $ping, $kdr, $kills, $deaths, $blocksPlaced, $blocksBroken, $streak));
		if(isset(PlayerEvents::$PlayerData[$pl->getName()])){
			$form->addLabel(LangManager::translate("core-stats-device", $sender, PlayerEvents::OS[PlayerEvents::getPlayerData($pl)["DeviceOS"]], PlayerEvents::CONTROLS[PlayerEvents::getPlayerData($pl)["CurrentInputMode"]]));
		}
	    $sender->sendForm($form);
	}
}