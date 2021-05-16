<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use kenygamer\Core\LangManager;

class Vanish extends PluginCommand{
	/** @var Core */
	public $plugin;
	/** @var array */
	public $vanishes = [];
	
	/** @var self|null */
	private static $instance = null;

    /**
     * @param string $name
     * @param Core $plugin
     */
	public function __construct(string $name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Hide from other players");
        $this->setUsage("/vanish <player>");
        $this->setAliases(["v"]);
        $this->setPermission("core.command.vanish");
		$this->plugin = $plugin;
		self::$instance = $this;
    }
    
    /**
     * @return self|null
     */
    public static function getInstance() : ?self{
    	return self::$instance;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.vanish")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
    	$target = $sender;
        if (isset($args[0])) {
            if (!$sender->hasPermission("core.command.vanish.other")) {
                LangManager::send("core-vanish-other-noperm", $sender);
                return true;
            } else {
				$target = $this->getPlayer($args[0]);
			    if ($target == null) {
                    LangManager::send("player-notfound", $sender);
                    return true;
				}
            }
        }
        $vanished = $this->vanishes[$target->getName()][0] ?? false;
        $time = $this->vanishes[$target->getName()][1] ?? -1;
        if(!$vanished && !(time() >= $time)){
        	LangManager::send("in-cooldown", $sender);
        }elseif(!$vanished){
        	$this->vanishes[$target->getName()] = [true, time() + 120];
        	$target->setGenericFlag(Entity::DATA_FLAG_INVISIBLE, true);
        	if($target !== $sender){
        		LangManager::send("core-vanish-other-on", $sender, $target->getName());
        	}else{
        		LangManager::send("core-vanish-on", $sender);
            }
        }elseif($vanished){
        	$this->vanishes[$target->getName()][0] = false;
        	$target->setGenericFlag(Entity::DATA_FLAG_INVISIBLE, false);
        	if($target !== $sender){
        		LangManager::send("core-vanish-other-off", $sender, $target->getName());
        	}else{
        		LangManager::send("core-vanish-off", $sender);
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