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
use pocketmine\plugin\Plugin;

use kenygamer\Core\LangManager;

class Givexp extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Give EXP to player");
        $this->setUsage("/givexp <amount> <player>");
        $this->setPermission("core.command.givexp");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.givexp")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
			LangManager::send("core-givexp-usage", $sender);
            return false;
        }
        if (!empty($args[0])) {
            if (!empty($args[1])) {
                $target = $this->plugin->getServer()->getPlayer($args[1]);
                if ($target == true) {
                    if (is_numeric($args[0])) {
                        if ($args[0] >= 0 && $args[0] <= 1000000) {
                            $target->addXp($args[0]);
                            LangManager::send("core-givexp", $sender, $args[0], $target->getName());
							LangManager::send("core-givexp-target", $target, $args[0]);
                        } else {
                            LangManager::send("core-givexp-outofbounds", $sender);
						}
					}
				}
                if ($target == null) {
                    LangManager::translate("player-notfound", $sender);
				}
			}
            if (empty($args[1])) {
                if (is_numeric($args[0])) {
                    if ($args[0] >= 0 && $args[0] <= 1000000) {
                        $sender->addXp($args[0]);
						$sender->sendMessage("You have earned XP " . $args[0]);
                    } else {
                        LangManager::send("core-givexp-outofbounds", $sender);
					}
				}
			}
        } else {
            LangManager::send("core-givexp-usage", $sender);
        }
        return true;
	}
}