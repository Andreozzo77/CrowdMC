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
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class Tell extends PluginCommand{
	/** @var array */
	private $plugin;
	/** @var string */
	public static $reply;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Send a private message");
        $this->setUsage("/tell <player> <message>");
		$this->setAliases(["msg", "w", "whisper"]);
        $this->setPermission("core.command.tell");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender->hasPermission("core.command.tell")){
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if($sender instanceof ConsoleCommandSender){
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if(count($args) < 1){
			LangManager::send("core-tell-usage", $sender);
            return false;
        }
		$name = array_shift($args);
        $target = Main::getInstance()->getPlayer($name);
        if($target === null){
            LangManager::send("player-notfound", $sender);
            return false;
        }
		if($target === $sender){
			LangManager::send("core-tell-other", $sender);
			return false;
		}
		if(Main::getInstance()->isIgnored($sender, $target) === 1){
			LangManager::send("core-tell-ignored", $sender, $target->getName());
			return false;
		}
		self::$reply[$target->getName()] = $sender->getName();
		unset(self::$reply[$sender->getName()]);
		$sender->sendMessage(TextFormat::YELLOW . "[me -> " . TextFormat::clean($target->getDisplayName()) . "]" . TextFormat::RESET . " " . implode(" ", $args));
		$msg = TextFormat::YELLOW . "[" . TextFormat::clean($sender->getDisplayName()) . " -> me]" . TextFormat::RESET . " " . implode(" ", $args);
		if($target instanceof Player){
			$target->sendMessage($msg);
		}
        return true;
    }
	
}