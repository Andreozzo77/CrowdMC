<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Human;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Internet;
use pocketmine\lang\TranslationContainer;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class Stop extends PluginCommand{
	private $plugin;

    public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Stops the server");
        $this->setUsage("/stop");
        $this->setPermission("core.command.stop");
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if(!$sender->hasPermission("core.command.stop")){
            LangManager::send("cmd-noperm", $sender);
            return true;
        }
		$plugin = Main::getInstance();
		list($ip, $port) = $plugin->getConfig()->get("transfer-server");
		
        Command::broadcastCommandMessage($sender, new TranslationContainer("commands.stop.start"));
        
        foreach($sender->getServer()->getOnlinePlayers() as $player){
            $player->addTitle(LangManager::translate("core-stop-title", $player), "", 15, 15, 15);
        }
        
        $this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($ip, $port) : void{
        	Server::getInstance()->shutdown();
        	foreach(Server::getInstance()->getOnlinePlayers() as $player){
        		$player->transfer($ip, $port);
        	}
        	foreach(Server::getInstance()->getLevelByName("warzone")->getEntities() as $entity){
        		if($entity instanceof Human){
        			$entity->close();
        		}
        	}
        }), 35);
        return true;
    }
    
}