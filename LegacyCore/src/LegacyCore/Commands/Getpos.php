<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;

class Getpos extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Get your/other's position!");
        $this->setUsage("/getpos <player>");
        $this->setAliases(["xyz"]);
		$this->setPermission("core.command.getpos");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$sender->hasPermission("core.command.getpos")) {
	      	$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
			return false;
		}
        $target = $sender;
        if (isset($args[0])) {
            if (!$sender->hasPermission("core.command.getpos.other")) {
                $sender->sendMessage(TextFormat::RED . "You don't have permission to get XYZ of other players!");
                return false;
            } else {
				$target = $this->getPlayer($args[0]);
			    if ($target == null) {
                    $sender->sendMessage(TextFormat::RED . "That player cannot be found.");
                    return false;
				}
            }
        }
        $sender->sendMessage(TextFormat::GREEN . ($target === $sender ? "You're" : $target->getDisplayName() . TextFormat::GREEN . " is ") . "in world: " . TextFormat::AQUA . $target->getLevel()->getName() . "\n" . TextFormat::GREEN . "Coordinates: " . TextFormat::YELLOW . "X: " . TextFormat::AQUA . $target->getFloorX() . TextFormat::GREEN . ", " . TextFormat::YELLOW . "Y: " . TextFormat::AQUA . $target->getFloorY() . TextFormat::GREEN . ", " . TextFormat::YELLOW . "Z: " . TextFormat::AQUA . $target->getFloorZ());
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
