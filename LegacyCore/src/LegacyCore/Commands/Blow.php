<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\level\Explosion;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class Blow extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Explode a player");
        $this->setUsage("/blow <player> <yield>");
		$this->setAliases(["explode"]);
        $this->setPermission("core.command.blow");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.blow")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-blow-usage", $sender);
            return false;
        }
		$name = array_shift($args);
        $yield = array_shift($args);
		// Player Not Found
        $target = $this->getPlayer($name);
        if ($target == null) {
            LangManager::send("player-notfound", $sender);
            return false;
        }
	    if (!is_numeric($yield) || $yield < 0.1){
			LangManager::send("positive-value", $sender);
			return false;
		}
		// Blow UP Player
		$this->Boom($target, $yield);
		Command::broadcastCommandMessage($sender, "Exploded " . $target->getName());
        return true;
	}
	
	/**
	 * @param Boom
	 * @param Player $player
	 * @param Yield $yield
     */
	public function Boom($player, $yield) {
		$this->plugin->getServer()->getPluginManager()->callEvent($event = new ExplosionPrimeEvent($player, $yield));
		if ($event->isCancelled()) return false;
		$explosion = new Explosion($player, $yield);
		$explosion->explodeB();
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