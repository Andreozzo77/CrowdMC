<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\plugin\Plugin;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use kenygamer\Core\LangManager;

class Wild extends PluginCommand{
    private $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport you in a random place");
        $this->setUsage("/wild");
        $this->setAliases(["wild"]);
		$this->setPermission("core.command.wild");
		$this->plugin = $plugin;
		
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if (!$sender->hasPermission("core.command.wild")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
	    if (!isset($this->plugin->wild[$sender->getLowerCaseName()]) || time() > $this->plugin->wild[$sender->getLowerCaseName()] || $sender->hasPermission("core.cooldown.bypass")) {
            $this->plugin->wild[$sender->getLowerCaseName()] = time() + 15;
	        $level = $this->getPlugin()->getServer()->getLevelByName("wild");
            $x = \kenygamer\Core\Main::mt_rand(-10000, 10000);
		    $y = \kenygamer\Core\Main::mt_rand(100, 128);
            $z = \kenygamer\Core\Main::mt_rand(-10000, 10000);
            $sender->teleport(new Position($x, $y, $z, $level));
			$this->Wilderness($sender);
			$sender->addTitle(LangManager::translate("core-wild-title1", $sender), LangManager::translate("core-wild-title2", $sender), 20, 20, 20);
            return true;
	    } else {
			LangManager::send("in-cooldown", $sender);
			return false;
		}
    }

	/**
     * @public wilderness
     */
	public function Wilderness(Player $player) : void{
        if (isset($this->plugin->suvwild[$player->getLowerCaseName()])) {
            if ((time() - $this->plugin->suvwild[$player->getLowerCaseName()]) > $this->plugin->wildtime) {  
			    # No Need Message
            }
        } else {
			# No Need Message
        }
        $this->plugin->suvwild[$player->getLowerCaseName()] = time();
    }
}
