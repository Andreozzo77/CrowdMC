<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\Durable;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\plugin\Plugin;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use CustomEnchants\CustomEnchants\CustomEnchants;
use jojoe77777\FormAPI\SimpleForm;
use kenygamer\Core\LangManager;

class Vip extends PluginCommand{
	
	/** @var array */
	public $vip;

	/** @var array */
	private $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("VIP Command");
        $this->setUsage("/vip");
        $this->setAliases(["vip"]);
        $this->setPermission("core.command.vip");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if(!$sender->hasPermission("core.command.vip")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
	    $this->VipUI($sender);
		return true;
	}
	
	/**
	 * @param VipUI
	 * @param Player $player
     */
	public function VipUI(Player $player) : void{
		$form = new SimpleForm(function (Player $player, int $data = null) {
        $result = $data;
        if ($result === null) {
            return;
        }
		switch($result) {
		    case 0:
		    $player->teleport($this->plugin->getServer()->getLevelByName("vipworld")->getSafeSpawn());
            $player->addTitle(LangManager::translate("core-premiumworld-title1", $player), LangManager::translate("core-premiumworld-title2", $player), 20, 20, 20);
		    break;
			case 1:
			$level = $this->plugin->getServer()->getLevelByName("vipworld");
            $x = \kenygamer\Core\Main::mt_rand(-10000, 10000);
			$y = \kenygamer\Core\Main::mt_rand(100, 128);
            $z = \kenygamer\Core\Main::mt_rand(-10000, 10000);
            $player->teleport(new Position($x, $y, $z, $level));
		    $this->WildVip($player);
            $player->addTitle(LangManager::translate("core-premiumwild-title1", $player), LangManager::translate("core-premiumwild-title2", $player), 20, 20, 20);
			break;
			case 2:
			$level = $this->plugin->getServer()->getLevelByName("prison");
            $x = 118;
            $y = 34;
            $z = -19;
            $player->teleport(new Position($x, $y, $z, $level));
            $player->addTitle(LangManager::translate("core-premiummine-title1", $player), LangManager::translate("core-premiummine-title2", $player), 20, 20, 20);
			break;
		    }
		});
		$form->setTitle(LangManager::translate("core-vip-title", $player));
	    $form->setContent(LangManager::translate("core-warp-desc", $player));
	    $form->addButton(LangManager::translate("core-vip-1", $player));
		$form->addButton(LangManager::translate("core-vip-2", $player));
		$form->addButton(LangManager::translate("core-vip-3", $player));
		$form->sendToPlayer($player);
	}

	/**
	 * @param WildVip
	 * @param Player $player
     */
	public function WildVip(Player $player) : void{
        $this->plugin->suvwild[$player->getLowerCaseName()] = time();
    }
	
}