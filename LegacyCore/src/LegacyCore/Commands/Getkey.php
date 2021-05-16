<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use pocketmine\Server;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\inventory\Inventory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\level\Level;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

class Getkey extends PluginCommand{
	
	/** @var array */
	public $plugin;

    public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Give a prison key to the specified player");
        $this->setUsage("/getkey");
        $this->setAliases(["givekey", "getkey"]);
        $this->setPermission("core.command.getkey");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
        if (!$sender->hasPermission("core.command.getkey")) {
            $sender->sendMessage(TextFormat::RED . "You don't have permission to use this command!");
            return false;
        }
        if (count($args) < 1) {
            $sender->sendMessage("§l§e» §r§aCustom CratesKeys §l§e«");
            $sender->sendMessage("§e/getkey Common §7- §bGet Common key");
            $sender->sendMessage("§e/getkey Vote §7- §bGet Vote key");
            $sender->sendMessage("§e/getkey Rare §7- §bGet Rare key");
            $sender->sendMessage("§e/getkey Ultra §7- §bGet Ultra key");
            $sender->sendMessage("§e/getkey Mythic §7- §bGet Mythic key");
            $sender->sendMessage("§e/getkey Legendary §7- §bGet Legendary key");
            return false;
        }
        switch ($args[0]){
            case "common":
            case "Common":
            if (!$sender->hasPermission("core.command.getkey")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                return false;
            }
            if (count($args) < 2) {
                $sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey common <player> <amount>");
                return false;
            }
            if (isset($args[1])) {
                $player = $this->plugin->getServer()->getPlayer($args[1]);
            }
            $player = $this->plugin->getServer()->getPlayer($args[1]);
            if (!$player instanceof Player) {
                if ($player instanceof ConsoleCommandSender) {
                    $sender->sendMessage(TextFormat::RED . "Please enter a player name");
                    return false;
                }
                $sender->sendMessage(TextFormat::RED . "That player cannot be found");
                return false;
            }
            if (count($args) < 3) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey common <player> <amount>");
                return false;
            }
            if (isset($args[2])) {
                $amount = intval($args[2]);
            }
            $amount = intval($args[2]);
            $common = Item::get(341, 1, $amount);
            $common->addEnchantment(new EnchantmentInstance(new Enchantment(255 , "" , Enchantment::RARITY_COMMON , Enchantment::SLOT_ALL , Enchantment::SLOT_NONE , 1)));
            $common->setCustomName("§r§aCommon Key");
            $common->setLore([
			'§rUse Crates Keys On chest',
			'§rCrates Keys Open Get loot stuff',
			'',
            '§r§6Tier Level: §eI'
			]);
            $player->getInventory()->addItem($common);
            break;
            case "vote":
            case "Vote":
            if (!$sender->hasPermission("core.command.getkey")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                return false;
            }
            if (count($args) < 2) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey vote <player> <amount>");
                return false;
            }
            if (isset($args[1])) {
                $player = $this->plugin->getServer()->getPlayer($args[1]);
            }
            $player = $this->plugin->getServer()->getPlayer($args[1]);
            if (!$player instanceof Player) {
                if ($player instanceof ConsoleCommandSender) {
                    $sender->sendMessage(TextFormat::RED . "Please enter a player name");
                    return false;
                }
                $sender->sendMessage(TextFormat::RED . "That player cannot be found");
                return false;
            }
            if (count($args) < 3) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey vote <player> <amount>");
                return false;
            }
            if (isset($args[2])) {
                $amount = intval($args[2]);
            }
            $amount = intval($args[2]);
            $vote = Item::get(341, 2, $amount);
            $vote->addEnchantment(new EnchantmentInstance(new Enchantment(255 , "" , Enchantment::RARITY_COMMON , Enchantment::SLOT_ALL , Enchantment::SLOT_NONE , 1)));
            $vote->setCustomName("§r§cVote Key");
            $vote->setLore([
			'§rUse Crates Keys On chest',
			'§rCrates Keys Open Get loot stuff',
			'',
            '§r§6Tier Level: §eII'
			]);
            $player->getInventory()->addItem($vote);
            break;
            case "rare":
            case "Rare":
            if (!$sender->hasPermission("core.command.getkey")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                return false;
            }
            if (count($args) < 2) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey rare <player> <amount>");
                return false;
            }
            if (isset($args[1])) {
                $player = $this->plugin->getServer()->getPlayer($args[1]);
            }
            $player = $this->plugin->getServer()->getPlayer($args[1]);
            if (!$player instanceof Player) {
                if ($player instanceof ConsoleCommandSender) {
                    $sender->sendMessage(TextFormat::RED . "Please enter a player name");
                    return false;
                }
                $sender->sendMessage(TextFormat::RED . "That player cannot be found");
                return false;
            }
            if (count($args) < 3) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey rare <player> <amount>");
                return false;
            }
            if (isset($args[2])) {
                $amount = intval($args[2]);
            }
            $amount = intval($args[2]);
            $rare = Item::get(341, 3, $amount);
            $rare->addEnchantment(new EnchantmentInstance(new Enchantment(255 , "" , Enchantment::RARITY_COMMON , Enchantment::SLOT_ALL , Enchantment::SLOT_NONE , 1)));
            $rare->setCustomName("§r§6Rare Key");
            $rare->setLore([
			'§rUse Crates Keys On chest',
			'§rCrates Keys Open Get loot stuff',
			'',
            '§r§6Tier Level: §eIII'
			]);
            $player->getInventory()->addItem($rare);
            break;
            case "ultra":
            case "Ultra":
            if (!$sender->hasPermission("core.command.getkey")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                return false;
            }
            if (count($args) < 2) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey ultra <player> <amount>");
                return false;
            }
            if (isset($args[1])) {
                $player = $this->plugin->getServer()->getPlayer($args[1]);
            }
            $player = $this->plugin->getServer()->getPlayer($args[1]);
            if (!$player instanceof Player) {
                if ($player instanceof ConsoleCommandSender) {
                    $sender->sendMessage(TextFormat::RED . "Please enter a player name");
                    return false;
                }
                $sender->sendMessage("§cThat player cannot be found");
                return false;
            }
            if (count($args) < 3) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey ultra <player> <amount>");
                return false;
            }
            if (isset($args[2])) {
                $amount = intval($args[2]);
            }
            $amount = intval($args[2]);
            $ultra = Item::get(341, 4, $amount);
            $ultra->addEnchantment(new EnchantmentInstance(new Enchantment(255 , "" , Enchantment::RARITY_COMMON , Enchantment::SLOT_ALL , Enchantment::SLOT_NONE , 1)));
            $ultra->setCustomName("§r§l§dUltra Key");
            $ultra->setLore([
			'§rUse Crates Keys On chest',
			'§rCrates Keys Open Get loot stuff',
			'',
            '§r§6Tier Level: §eIV'
			]);
            $player->getInventory()->addItem($ultra);
            break;
            case "mythic":
            case "Mythic":
            if (!$sender->hasPermission("core.command.getkey")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                return false;
            }
            if (count($args) < 2) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey mythic <player> <amount>");
                return false;
            }
            if (isset($args[1])) {
                $player = $this->plugin->getServer()->getPlayer($args[1]);
            }
            $player = $this->plugin->getServer()->getPlayer($args[1]);
            if (!$player instanceof Player) {
                if ($player instanceof ConsoleCommandSender) {
                    $sender->sendMessage(TextFormat::RED . "Please enter a player name");
                    return false;
                }
                $sender->sendMessage(TextFormat::RED . "That player cannot be found");
                return false;
            }
            if (count($args) < 3) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey mythic <player> <amount>");
                return false;
            }
            if (isset($args[2])) {
                $amount = intval($args[2]);
            }
            $amount = intval($args[2]);
            $mythic = Item::get(341, 5, $amount);
            $mythic->addEnchantment(new EnchantmentInstance(new Enchantment(255 , "" , Enchantment::RARITY_COMMON , Enchantment::SLOT_ALL , Enchantment::SLOT_NONE , 1)));
            $mythic->setCustomName("§r§l§5Mythic Key");
            $mythic->setLore([
			'§rUse Crates Keys On chest',
			'§rCrates Keys Open Get loot stuff',
			'',
            '§r§6Tier Level: §eV'
			]);
            $player->getInventory()->addItem($mythic);
            break;
            case "legendary":
            case "Legendary":
            if (!$sender->hasPermission("core.command.getkey")) {
                $sender->sendMessage(TextFormat::RED . "You do not have permission to use this command");
                return false;
            }
            if (count($args) < 2) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey legendary <player> <amount>");
                return false;
            }
            if (isset($args[1])) {
                $player = $this->getPlugin()->getServer()->getPlayer($args[1]);
            }
            $player = $this->getPlugin()->getServer()->getPlayer($args[1]);
            if (!$player instanceof Player) {
                if ($player instanceof ConsoleCommandSender) {
                    $sender->sendMessage(TextFormat::RED . "Please enter a player name");
                    return false;
                }
                $sender->sendMessage(TextFormat::RED . "That player cannot be found");
                return false;
            }
            if (count($args) < 3) {
				$sender->sendMessage(TextFormat::GOLD . "Usage:" . TextFormat::GREEN . " /getkey legendary <player> <amount>");
                return false;
            }
            if (isset($args[2])) {
                $amount = intval($args[2]);
            }
            $amount = intval($args[2]);
            $legendary = Item::get(341, 6, $amount);
            $legendary->addEnchantment(new EnchantmentInstance(new Enchantment(255 , "" , Enchantment::RARITY_COMMON , Enchantment::SLOT_ALL , Enchantment::SLOT_NONE , 1)));
            $legendary->setCustomName("§r§l§9Legendary Key");
            $legendary->setLore([
			'§rUse Crates Keys On chest',
			'§rCrates Keys Open Get loot stuff',
			'',
               '§r§6Tier Level: §eVI'
			]);
            $player->getInventory()->addItem($legendary);
            break;
            default:
            $sender->sendMessage("§6Usage: §a/getkey <key> <player> <amount>");
            break;
		}
		return true;
	}
}