<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use LegacyCore\Entities\Slapper;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class NPC extends PluginCommand{
	
	/** @var array */
    public $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Create a slapper");
        $this->setUsage("/npc <spawn|remove>");
        $this->setPermission("core.command.npc");
	    $this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.npc")) {
			LangManager::send("cmd-noperm", $sender);
			return false;
		}
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
			LangManager::send("core-npc-usage", $sender);
			return false;
		}
		if (isset($args[0])) {
		    switch(mb_strtolower($args[0])) {
				case "spawn":
				// Spawned NPC
				if (count($args) < 2) {
                    LangManager::send("core-npc-usage", $sender);
                    return false;
				}
				$this->spawnNPC($sender, $args[1]);
				LangManager::send("core-npc-spawn", $sender, TextFormat::clean($args[1]));
				return true;
				case "remove":
				LangManager::send("core-npc-remove", $sender);
				$this->plugin->deletenpc[$sender->getLowerCaseName()] = $sender;
				return true;
				default:
				LangManager::send("core-npc-usage", $sender);
			    return false;
			}
		}
    }
	
	public function spawnNPC(Player $player, string $name): void{
		//$this->dumpSkinData($player->getSkin(), $this->plugin->getServer()->getDataPath());
		//return;
		
		$nbt = Entity::createBaseNBT($player, null, $player->getYaw(), $player->getPitch());
		$nbt->setTag(new CompoundTag("Skin", [
		    new StringTag("Name", "Slapper"),
		    new ByteArrayTag("Data", $player->getSkin()->getSkinData()),
		    new ByteArrayTag("GeometryData", $player->getSkin()->getGeometryData())
	    ]));
		$npc = new Slapper($player->getLevel(), $nbt);
		$npc->setNameTag($name);
		$npc->setNameTagAlwaysVisible(true);
		$npc->getInventory()->setContents($player->getInventory()->getContents());
		$npc->getArmorInventory()->setContents($player->getArmorInventory()->getContents());
		$npc->getInventory()->setItemInHand($player->getInventory()->getItemInHand());
		$npc->spawnToAll();
	}
	
	/**
	 * Dumps a skin data in multiple files.
	 *
	 * @param Skin $skin
	 */
	public function dumpSkinData(Skin $skin, string $path) : void{
		//file_put_contents($path . "SkinId.txt", $skin->getSkinId());
		file_put_contents($path . "SkinData.txt", $skin->getSkinData());
		file_put_contents($path . "SkinGeometryData.txt", $skin->getGeometryData());
	}
} 