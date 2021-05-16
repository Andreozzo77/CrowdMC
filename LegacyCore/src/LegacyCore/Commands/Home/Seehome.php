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

class Seehome extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("View other players homes");
        $this->setUsage("/seehome <player>");
		$this->setAliases(["checkhome"]);
		$this->setPermission("core.command.seehome");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if (!$sender->hasPermission("core.command.seehome")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if (count($args) < 1) {
            LangManager::send("core-seehome-usage", $sender);
            return false;
        }
		$name = array_shift($args);
		$target = $this->getPlayer($name);
        if ($target == null) {
            LangManager::send("player-notfound", $sender);
            return false;
        }
    	$homes = $this->plugin->homes->getAll()[$target->getName()];
    	if (empty($homes)) {
    		LangManager::send("core-seehome-none", $sender, $target->getName());
    		return true;
    	}
    	$msg = LangManager::translate("core-seehome", $sender, $target->getName());
		foreach($homes as $home) {
			$msg .= "\n" . LangManager::translate("core-homeentry", $sender, $home["name"], $home["x"], $home["y"], $home["z"], $home["world"]);
		}
		$sender->sendMessage($msg);
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