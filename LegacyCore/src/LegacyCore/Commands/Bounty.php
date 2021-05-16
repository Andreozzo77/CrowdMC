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

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;

class Bounty extends PluginCommand{
	/** @var array */
	private $bountycd;
	/** @var Core */
	private $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Add a bounty on an enemy");
        $this->setUsage("/bounty <add|me|see>");
        $this->setPermission("core.command.bounty");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.bounty")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
			LangManager::send("core-bounty-usage", $sender);
            return false;
        }
		if (isset($args[0])) {
		    switch(mb_strtolower($args[0])) {
				case "me":
				if (!$sender->hasPermission("core.command.bounty.me")) {
					LangManager::send("cmd-noperm", $sender);
					return false;
				} 
				$money = Main::getInstance()->getEntry($sender, Main::ENTRY_BOUNTY);
                if ($money !== 0) {
                	LangManager::send("core-bounty-me", $sender, $money);
				} else {
					LangManager::send("core-bounty-me-none", $sender);
				}
				return true;
				case "see":
				if (!$sender->hasPermission("core.command.bounty.see")) {
				    LangManager::send("cmd-noperm", $sender);
					return false;
				} 
				if (count($args) < 2) {
                    LangManager::send("core-bounty-usage", $sender);
                    return false;
				}
				$target = $this->getPlayer($args[1]);
			    if ($target == null) {
                    LangManager::send("player-notfound", $sender);
                    return false;
				}
				if ($target == true) {
			    	$money = Main::getInstance()->getEntry($target, Main::ENTRY_BOUNTY);
                    if ($money !== 0) {
				    	LangManager::send("core-bounty-other", $sender, $target->getName(), $money);
			    	} else {
				    	LangManager::send("core-bounty-other-none", $sender, $target->getName());
			    	}
				}
				return true;
				case "add":
				case "new":
				if (!$sender->hasPermission("core.command.bounty.add")) {
					LangManager::send("cmd-noperm", $sender);
					return false;
				} 
				if (count($args) < 2) {
                    LangManager::send("core-bounty-usage", $sender);
                    return false;
				}
				$target = $this->getPlayer($args[1]);
			    if ($target == null) {
                    LangManager::send("player-notfound", $sender);
                    return false;
				}
				if (count($args) < 3) {
                    LangManager::send("core-bounty-usage", $sender);
                    return false;
				}
				if (!isset($this->plugin->bountycd[$sender->getLowerCaseName()]) || time() > $this->plugin->bountycd[$sender->getLowerCaseName()] || $sender->hasPermission("core.cooldown.bypass")) {
				    if (is_numeric($args[2])) {
                        $amount = (int)$args[2];
		                $bal = Main::getInstance()->myMoney($sender);
			            if ($bal >= $amount) {
					        if ($amount >= 50000 && $amount <= 1000000000) {
			                 	$currentamount = Main::getInstance()->getEntry($target, Main::ENTRY_BOUNTY);
					            Main::getInstance()->reduceMoney($sender, $amount);
                                if ($currentamount != null) {
                                    Main::getInstance()->registerEntry($target, Main::ENTRY_BOUNTY, $currentamount + $amount);
                                } else {
                                    Main::getInstance()->registerEntry($target, Main::ENTRY_BOUNTY, $amount);
				                }
							    $this->plugin->bountycd[$sender->getLowerCaseName()] = time() + 300;
							    LangManager::broadcast("core-bounty-broadcast", $target->getName(), $amount);
					        } else {
						        LangManager::send("core-bounty-outofbounds", $sender);
				                return false;
					        }
				    	} else {
				    		LangManager::send("money-needed", $sender, $amount);
					        return false;
			     	    }
			    	} else {
			            LangManager::send("positive-value", $sender);
			            return false;
					}
				} else {
					$sender->sendMessage(TextFormat::BOLD . TextFormat::RED . "(!)" . TextFormat::RESET . TextFormat::RED . " Bounty command is in cooldown!");
				}
				return true;
				case "remove":
				case "delete":
				if (!$sender->hasPermission("core.command.bounty.remove")) {
					LangManager::send("cmd-noperm", $sender);
					return false;
				} 
				if (count($args) < 2) {
                    LangManager::send("core-bounty-usage", $sender);
                    return false;
				}
				$target = $this->getPlayer($args[1]);
			    if ($target == null) {
                    LangManager::send("player-notfound", $sender);
                    return false;
				}
				if ($target == true) {
			    	Main::getInstance()->resetEntry($target, Main::ENTRY_BOUNTY);
					LangManager::send("core-bounty-reset", $sender, $target->getName());
				}
				return true;
				default:
				LangManager::send("core-bounty-usage", $sender);
			    return false;
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