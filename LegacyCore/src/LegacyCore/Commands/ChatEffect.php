<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\SimpleForm;

class ChatEffect extends PluginCommand{
	/** @var Core */
	private $plugin; 
	

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Change colour chat");
        $this->setUsage("/chateffect");
        $this->setAliases(["chat"]);
        $this->setPermission("core.command.chateffect");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.chateffect")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		$this->ChatUI($sender);
		return true;
	}
	
	/**
	 * @param ChatUI
	 * @param Player $player
     */
	public function ChatUI(Player $player) : void{
		$form = new SimpleForm(function (Player $player, int $data = null) {
        $result = $data;
        if ($result === null) {
            return;
        }
		switch($result) {
			case 0:
			if ($player->hasPermission("core.chat.rainbow")) {
				$this->plugin->chatprefs->set($player->getName(), "Rainbow");
			    LangManager::send("core-chateffect", $player, "Rainbow");
			} else {
			    LangManager::send("core-chateffect-rainbow", $player);
			}
			break;
			case 1:
			$this->plugin->chatprefs->set($player->getName(), "Gold");
		    LangManager::send("core-chateffect", $player, "Gold");
			break;
			case 2:
			$this->plugin->chatprefs->set($player->getName(), "Yellow");
		    LangManager::send("core-chateffect", $player, "Yellow");
			break;
			case 3:
			$this->plugin->chatprefs->set($player->getName(), "Blue");
		    LangManager::send("core-chateffect", $player, "Blue");
			break;
			case 4:
			$this->plugin->chatprefs->set($player->getName(), "Aqua");
		    LangManager::send("core-chateffect", $player, "Aqua");
			break;
			case 5:
			$this->plugin->chatprefs->set($player->getName(), "Green");
		    LangManager::send("core-chateffect", $player, "Green");
			break;
			case 6:
			$this->plugin->chatprefs->set($player->getName(), "Light Purple");
			LangManager::send("core-chateffect", $player, "Purple");
			break;
			case 7:
			$this->plugin->chatprefs->set($player->getName(), "White");
			LangManager::send("core-chateffect", $player, "White");
			break;
			case 8:
			$this->plugin->chatprefs->remove($player->getName());
		    LangManager::send("core-chateffect-removed", $player);
			break;
		    }
		});
		$rainbow = LangManager::translate("core-chateffect-rainbow2", $player);
		if (!$player->hasPermission("core.chat.rainbow")) {
			$rainbow .= "\n" . LangManager::translate("locked", $player);
		}
		$form->setTitle(LangManager::translate("core-chateffect-title", $player));
		$form->setContent(LangManager::translate("core-chateffect-desc", $player));
		$form->addButton($rainbow);
		$form->addButton(TextFormat::GOLD . "Gold");
		$form->addButton(TextFormat::YELLOW . "Yellow");
		$form->addButton(TextFormat::BLUE . "Blue");
		$form->addButton(TextFormat::AQUA . "Aqua");
		$form->addButton(TextFormat::GREEN . "Green");
		$form->addButton(TextFormat::LIGHT_PURPLE . "Purple");
		$form->addButton(TextFormat::WHITE . "White");
		$form->addButton(TextFormat::RED . "Remove Chat Color");
		$form->sendToPlayer($player);
	}
}