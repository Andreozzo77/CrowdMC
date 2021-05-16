<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class Warzone extends PluginCommand{

	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Random teleport in warzone area");
        $this->setUsage("/warzone");
        $this->setAliases(["wz"]);
		$this->setPermission("core.command.warzone");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if (!$sender->hasPermission("core.command.warzone")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (!isset($this->plugin->warzone[$sender->getLowerCaseName()]) || time() > $this->plugin->warzone[$sender->getLowerCaseName()] || $sender->hasPermission("core.cooldown.bypass")) {
            $this->plugin->warzone[$sender->getLowerCaseName()] = time() + 30;
            $random = 1;
            switch($random){
	    	    case 1:
				$sender->teleport($this->plugin->getServer()->getLevelByName("warzone")->getSpawnLocation());
		    	
		    	$sender->addTitle(LangManager::translate("core-warzone-title1", $sender), LangManager::translate("core-warzone-title2", $sender), 20, 20, 20);
				$this->War($sender);
			    return true;
			}
		} else {
			LangManager::send("in-cooldown", $sender);
			return false;
		}
	}

	/**
	 * @param War
	 * @param Player $player
     */
	public function War(Player $player) {
        if (isset($this->plugin->warzones[$player->getLowerCaseName()])) {
            if ((time() - $this->plugin->warzones[$player->getLowerCaseName()]) > $this->plugin->wztimer){
                LangManager::send("invulnerable", $player, $this->plugin->wztimer);
            }
        } else {
            LangManager::send("invulnerable", $player, $this->plugin->wztimer);
        }
        $this->plugin->warzones[$player->getLowerCaseName()] = time();
    }
}