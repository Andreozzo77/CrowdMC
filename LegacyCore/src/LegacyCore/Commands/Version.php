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
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use jojoe77777\FormAPI\CustomForm;
use kenygamer\Core\LangManager; 

class Version extends PluginCommand{
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Check the server version");
        $this->setUsage("/version");
        $this->setAliases(["ver"]);
        $this->setPermission("core.command.version");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.version")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			$sender->sendMessage("This server is running " . $sender->getServer()->getName() . " " . $sender->getServer()->getPocketMineVersion() . " for Minecraft: Bedrock Edition " . $sender->getServer()->getVersion() . " (protocol version " . ProtocolInfo::CURRENT_PROTOCOL . ")");
			return false;
		}
		$this->VersionUI($sender);
		return true;
	}
	
	/**
	 * @param VersionUI
	 * @param Player $player
     */
	public function VersionUI(Player $player) : void{
		$api = $this->plugin->getServer()->getPluginManager()->getPlugin("FormAPI");
	    $form = new CustomForm(null);
		
		$runing = $player->getServer()->getName();
		$pmmpver = $player->getServer()->getPocketMineVersion();
		$mcpever = $player->getServer()->getVersion();
		$form->setTitle(LangManager::translate("core-version-title", $player));
		$form->addLabel(LangManager::translate("core-version-desc", $player, $runing, $pmmpver, $mcpever, ProtocolInfo::CURRENT_PROTOCOL, Core::$snapshot));
	    $form->sendToPlayer($player);
	}
}