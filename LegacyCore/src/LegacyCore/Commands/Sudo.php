<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class Sudo extends PluginCommand{
	
	/** @var array */
	public $plugin;

    public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Force a player to chat or run a command");
        $this->setUsage("/sudo <player> <message or command..>");
        $this->setPermission("core.command.sudo");
		$this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if (!$sender->hasPermission("core.command.sudo")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if (count($args) < 1) {
            LangManager::send("core-sudo-usage", $sender);
            return false;
        }
		$name = array_shift($args);
		$msg = trim(implode(" ", $args));
		// Player Not Found
        $target = $this->getPlayer($name);
        if ($target == null) {
            LangManager::send("player-notfound", $sender);
            return false;
        }
		LangManager::send("core-sudo", $sender, $target->getName(), $msg);
		$target->chat($msg);
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