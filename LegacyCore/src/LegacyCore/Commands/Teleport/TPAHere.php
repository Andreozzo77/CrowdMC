<?php

namespace LegacyCore\Commands\Teleport;

use LegacyCore\Core;

use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\command\PluginCommand;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class TPAHere extends PluginCommand{

	private $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Request them to teleport to you");
        $this->setUsage("/tpahere <player>");
		$this->setPermission("core.command.tpahere");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
    	if ($sender instanceof ConsoleCommandSender) {
		    LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-tpahere-usage", $sender);
            return false;
        }
        $target = $this->plugin->getServer()->getPlayer($args[0]);
        if ($target == null) {
        	LangManager::translate("player-notfound", $sender);
        	return false;
        }
        if(Main::getInstance()->isIgnored($sender, $target) === 1){
        	LangManager::send("core-tpa-ignored", $sender, $target->getName());
        	return false;
        }
        if ($target === $sender) {
        	LangManager::translate("core-tpa-other", $sender);
        	return false;
        }
        foreach($this->plugin->tpa as $key => $request) {
        	if ($request["source"] === $sender->getName() or $request["target"] === $target->getName()) {
        		if (!time() > $request["expire"]) {
        			LangManager::send("core-tpa-pending", $sender);
        	    } else {
        	    	unset($this->plugin->tpa[$key]);
        	    	LangManager::send("timedout", $sender);
        	    }
        	    return true;
        	}
        }
        $this->plugin->tpa[] = [
           "source" => $sender->getName(),
           "target" => $target->getName(),
           "tpahere" => true,
           "expire" => time() + 60
        ];
        LangManager::send("core-tpahere-target", $target, $sender->getName());
        LangManager::send("core-tpa", $sender, $target->getName());
        return true;
    }
}