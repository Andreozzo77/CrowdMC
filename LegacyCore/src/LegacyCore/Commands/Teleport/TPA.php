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
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class TPA extends PluginCommand{

	private $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Request to teleport to them");
        $this->setUsage("/tpa <player>");
		$this->setPermission("core.command.tpa");
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
    	$cmd = "tpa";
    	if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
            LangManager::send("core-tpa-usage", $sender);
            return false;
        }
    	if ($cmd === "tpa") {
			if (!empty($args[0])) {
                $target = $this->plugin->getServer()->getPlayer($args[0]);
		    	if ($target == null) {
                    LangManager::send("player-notfound", $sender);
				    return false;
		    	}
				
		    	if ($target === $sender) {
			    	LangManager::send("core-tpa-other", $sender);
			        return false;
		    	}
		    	if(Main::getInstance()->isIgnored($sender, $target) === 1){
		    		LangManager::send("core-tpa-ignored", $sender, $target->getName());
		    		return false;
		    	}
				foreach($this->plugin->tpa as $key => $request) {
					if ($request["source"] === $sender->getName() or $request["target"] === $target->getName()) {
						if (!(time() >= $request["expire"])){
							LangManager::send("core-tpa-pending", $sender);
						} else {
							unset($this->plugin->tpa[$key]);
							LangManager::send("timedout", $sender);
			    		}
			    		return true;
			    	}
			    }
			    if ($cmd === "tpa") {
			    	$this->plugin->tpa[] = [
			    	    "source" => $sender->getName(),
			            "target" => $target->getName(),
			    	    "tpahere" => false,
			            "expire" => time() + 60
			    	];
			    } else {
			    	$this->plugin->tpa[] = [
			    	"source" => $sender->getName(),
			    	"target" => $target->getName(),
			    	"tpahere" => true,
			    	"expire" => time()
			        ];
				}
				LangManager::send("core-tpa-target", $target, $sender->getName());
				LangManager::send("core-tpa", $sender, $target->getName());
			    return true;
			}
		}
    }
}