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

class Fly extends PluginCommand{
	public static $hasFliedInHub = [];

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Fly in survival or adventure mode");
        $this->setUsage("/fly <player>");
        $this->setPermission("core.command.fly");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.fly")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		$target = $sender;
        if (isset($args[0])) {
            if (!$sender->hasPermission("core.command.fly.other")) {
            	LangManager::send("core-fly-other-noperm", $sender);
                return false;
            } else {
				$target = $this->getPlayer($args[0]);
			    if ($target == null) {
			    	LangManager::send("player-notfound", $sender);
                    return false;
				}
            }
        }
		if (!$target->getAllowFlight()){
            $target->setAllowFlight(true);
			$target->setFlying(true);
            LangManager::send("core-fly-on", $target);
            self::$hasFliedInHub[$target->getName()] = true;
        } else {
        	if($target->getGamemode() % 2 === 0){
        		$target->setAllowFlight(false);
        		$target->setFlying(false);
        	}
        	LangManager::send("core-fly-off", $target);
		}
		if ($target !== $sender) {
			if ($target->getAllowFlight()) {
				LangManager::send("core-fly-other-on", $sender, $target->getName());
				self::$hasFliedInHub[$target->getName()] = true;
			} else {
				LangManager::send("core-fly-other-off", $sender, $target->getName());
			}
		}
		return true;
	}
	
	/**
     * @param string $player
     * @return null|Player
     */
    public function getPlayer($player): ?Player{
        if (!Player::isValidUserName($player)) {
            return null;
        }
        $player = mb_strtolower($player);
        $found = null;
        foreach($this->plugin->getServer()->getOnlinePlayers() as $target) {
            if (mb_strtolower(TextFormat::clean($target->getDisplayName(), true)) === $player || mb_strtolower($target->getName()) === $player) {
                $found = $target;
                break;
            }
        }
        if (!$found) {
            $found = ($f = $this->plugin->getServer()->getPlayer($player)) === null ? null : $f;
        }
        if (!$found) {
            $delta = PHP_INT_MAX;
            foreach($this->plugin->getServer()->getOnlinePlayers() as $target) {
                if (stripos(($name = TextFormat::clean($target->getDisplayName(), true)), $player) === 0) {
                    $curDelta = strlen($name) - strlen($player);
                    if ($curDelta < $delta) {
                        $found = $target;
                        $delta = $curDelta;
                    }
                    if ($curDelta === 0) {
                        break;
                    }
                }
            }
        }
        return $found;
    }
}