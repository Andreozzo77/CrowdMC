<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class God extends PluginCommand{
	/** @var Core */
	private $plugin;
	/** @var string[] */
	public $god = [];
	
	/** @var self|null */
	private static $instance = null;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Prevent you from taking any damage");
        $this->setUsage("/god <player>");
        $this->setPermission("core.command.god");
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
     * @param string $label
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
		if (!$sender->hasPermission("core.command.god")){
			$sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
			return true;
		}
		if($sender instanceof ConsoleCommandSender){
			$sender->sendMessage(TextFormat::RED . "This command can be only used in-game.");
			return true;
		}
		$target = $sender;
        if(isset($args[0])){
            if(!$sender->hasPermission("core.command.god.other")){
                $sender->sendMessage(TextFormat::RED . "You don't have permission to god other players!");
                return true;
            }
            $target = $this->getPlayer($args[0]);
            if($target === null){
            	$sender->sendMessage(TextFormat::RED . "That player cannot be found.");
            	return true;
            }
        }
		if(in_array($target->getName(), $this->god)){
			unset($this->god[array_search($target->getName(), $this->god)]);
			if($target !== $sender){
				$target->sendMessage("God mode disabled for " . $target->getName());
			}else{
				$target->sendMessage("God mode disabled");
			}
        }else{
            $this->god[] = $target->getName();
            if($target !== $sender){
				$target->sendMessage("God mode enabled for " . $target->getName());
			}else{
				$target->sendMessage("God mode enabled");
			}
		}
		return true;
	}
	
	/**
     * @param string $player
     *
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