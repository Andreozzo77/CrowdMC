<?php

namespace LegacyCore\Commands\Home;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;
use pocketmine\level\Position;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class Adminhome extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport to other player home");
        $this->setUsage("/adminhome <player> <name>");
		$this->setPermission("core.command.adminhome");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if (!$sender->hasPermission("core.command.adminhome")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-adminhome-usage", $sender);
            return false;
        }
		$name = array_shift($args);
		$home = array_shift($args);
		// Player Not Found
		$target = $this->getPlayer($name);
        if ($target == null) {
            LangManager::send("player-notfound", $sender);
            return false;
        }
    	$name = preg_replace("/[^a-z0-9]/", "", (isset($home) ? mb_strtolower($home) : "home"));
    	foreach($this->plugin->homes->getAll()[$target->getName()] as $home) {
    		if ($home["name"] === $name) {
    			$sender->teleport(new Position($home["x"], $home["y"], $home["z"], $this->plugin->getServer()->getLevelByName($home["world"])));
    			LangManager::send("teleporting", $sender);
    			return true;
    		}
    	}
    	LangManager::send("core-home-notfound", $name);
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