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
use pocketmine\utils\Config;
use pocketmine\plugin\Plugin;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class Nickname extends PluginCommand{

	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Change your in-game name");
        $this->setUsage("/nickname <new-name|remove> [player]");
        $this->setAliases(["nick"]);
		$this->setPermission("core.command.nick");
		$this->plugin = $plugin;
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if (!$sender->hasPermission("core.command.nick")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
        if (count($args) < 1) {
			LangManager::send("core-nickname-usage", $sender);
            return false;
        }
		$target = $sender;
        if (isset($args[1])) {
            if (!$sender->hasPermission("core.command.nick.other")) {
                LangManager::send("core-nickname-other-noperm", $sender);
                return false;
            } else {
				$target = $this->getPlayer($args[1]);
			    if ($target == null) {
                    LangManager::send("cmd-noperm", $sender);
                    return false;
				}
            }
        }
        $cleanNick = TextFormat::clean($args[0]);
        $maxLen = Main::getInstance()->rankCompare($target, "Universe") >= 0 ? 24 : 16;
		if (strlen($cleanNick) > $maxLen xor strlen($cleanNick) < 3){
            LangManager::send("core-nickname-outofbounds", $sender);
            return false;
        }
		if (mb_strtolower($args[0]) == "off" || mb_strtolower($args[0]) == "remove") {
			Main::getInstance()->resetEntry($target, Main::ENTRY_NICKNAME);
            $target->setDisplayName($target->getName());
            $target->setNameTag($target->getName());
            LangManager::send("core-nickname-removed", $target);
			if ($target !== $sender) {
				LangManager::send("core-nickname-removed-other", $sender, $target->getName());
			}
            return true;
        }
		if (!isset($this->plugin->nickname[$sender->getLowerCaseName()]) || time() > $this->plugin->nickname[$sender->getLowerCaseName()] || $sender->hasPermission("core.cooldown.bypass")) {
            if ($sender->hasPermission("core.cooldown.low")) {
                $this->plugin->nickname[$sender->getLowerCaseName()] = time() + 10;
			} else {
				$this->plugin->nickname[$sender->getLowerCaseName()] = time() + 300;
			}
			Main::getInstance()->registerEntry($target, Main::ENTRY_NICKNAME, $args[0]);
			$target->setDisplayName(str_replace($target->getName(), $args[0], $target->getDisplayName()) . "~");
			$target->setNameTag(str_replace($target->getName(), $args[0], $target->getNameTag()) . "~");
			LangManager::send("core-nickname-set", $target, $args[0]);
		    if ($target !== $sender) {
		    	LangManager::send("core-nickname-set-other", $sender, $target->getName(), $args[0]);
		    }
		} else {
			LangManager::send("in-cooldown", $sender);
			return false;
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