<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Skin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class Seeskin extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Copy a player's skin");
        $this->setUsage("/seeskin <player>");
        $this->setPermission("core.command.seeskin");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender->hasPermission("core.command.seeskin")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
			return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
			return false;
		}
		if (count($args) < 1) {
            $sender->sendMessage(TextFormat::GOLD . "Usage: " . TextFormat::GREEN . "/seeskin <player>");
            return false;
        }
		$name = array_shift($args);
		// Player Not Found
		$target = $this->getPlayer($name);
        if ($target == null) {
            $sender->sendMessage(TextFormat::RED . "That player cannot be found.");
            return false;
        }
        if ($target == true) {
			/*$skinId = $target->getSkin()->getSkinId();
			$skinBytes = $target->getSkin()->getSkinData()->getData();
			$capeBytes = $target->getSkin()->getCapeData();
			$geometryName = $target->getSkin()->getGeometryName();
			$geometryJson = $target->getSkin()->getGeometryData();*/
			// Skin Player
			//$sender->setSkin(new Skin($skinId, $skinBytes, $capeBytes, $geometryName, $geometryJson));
			$sender->setSkin($target->getSkin());
            $sender->sendSkin();
			Command::broadcastCommandMessage($sender, "Copied skin from " . $target->getName());
			return true;
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