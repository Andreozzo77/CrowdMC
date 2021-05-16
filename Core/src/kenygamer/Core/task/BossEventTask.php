<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\nbt\tag\{
	CompoundTag, StringTag, IntTag, ByteArrayTag, ListTag, ByteTag
};
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\entity\Bandit;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\math\Vector3;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;

use kenygamer\Core\LangManager;

class BossEventTask extends Task{
	/** @var Bandit|null */
	private $npc;
	/** @var int */
	private $run = 0;
	
	private $axe, $pickaxe, $helmet, $chestplate, $leggings, $boots;
	
	public static $boss_killer = "";
	public static $boss_damages = [];
	
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		if($this->npc === null){
			$vector = new Vector3(6, 57, -1);
			$level = $plugin->getServer()->getLevelByName("warzone");
			$level->loadChunk(6 >> 4, -1 >> 4);
			$nbt = Entity::createBaseNBT($vector->add(0, -10, 0), null, 300, 0);
			//TODO
			$nbt->setTag(new CompoundTag("Skin", [
			    new StringTag("Name", "EventBoss"),
                new ByteArrayTag("Data", file_get_contents($plugin->getServer()->getDataPath() . "SkinData.txt")),
			    new ByteArrayTag("GeometryData", file_get_contents($plugin->getServer()->getDataPath() . "SkinGeometryData.txt"))
		    ]));
		    $nbt->setInt("EventBoss", 1);
		    $this->npc = $npc = new Bandit($level, $nbt);
		    $npc->setNameTag(TextFormat::colorize("&a&l» &5E&fa&cs&dt&8e&2r &5B&fu&cn&dn&2y &5B&fo&cs&ds &l&a«&r"));
            $npc->setNameTagAlwaysVisible(true);
		    $npc->setMaxHealth(100000);
		    $npc->setHealth($npc->getMaxHealth());
            
            //Axe
            $enchants = [
                "hex" => 3,
                "trickster" => 2,
                "hellforged" => 2,
                "corrupt" => 5,
                "disarmor" => 10,
                "insanity" => 8,
                "gravity" => 5,
                "disarmprotection" => 1,
                "antitheft" => 3,
                "freeze" => 5,
                "soulbound" => 5,
                "blessed" => 3,
                "rage" => 5,
                "autorepair" => 6,
                "disarming" => 10
            ];
            $this->axe = ItemUtils::get(Item::DIAMOND_AXE, "&r&l&5E&fa&cs&dt&8e&2r &bAxe", [], $enchants);
            
            //Pickaxe
            $enchants = [
                "soulbound" => 5,
                "minerluck" => 10,
                "keyplus" => 8,
                "moneyfarm" => 16,
                "grind" => 12,
                "treasurehunter" => 8,
                "driller" => 5,
                "autorepair" => 8,
                "haste" => 5
            ];
            $this->pickaxe = ItemUtils::get(Item::DIAMOND_PICKAXE, "&r&l&5E&fa&cs&dt&8e&2r &bPickaxe", [], $enchants);
            
            //Helmet
            $enchants = [
                "doomed" => 3,
                "divine" => 3,
                "antiknockback" => 1,
                "soulbound" => 5,
                "implants" => 5,
                "overload" => 10,
                "obsidianshield" => 1,
                "shrink" => 3,
                "autorepair" => 6,
                "glowing" => 1,
                "angel" => 3
            ];
            $this->helmet = ItemUtils::get(Item::DIAMOND_HELMET, "&r&l&5E&fa&cs&dt&8e&2r &bHelmet", [], $enchants);
            
            //Chestplate
            $enchants = [
                "remedy" => 1,
                "flamecircle" => 2,
                "doomed" => 3,
                "adhesive" => 1,
                "antiknockback" => 1,
                "soulbound" => 5,
                "overload" => 10,
                "evasion" => 5,
                "obsidianshield" => 1,
                "shrink" => 3,
                "autorepair" => 6,
                "angel" => 3
            ];
            $this->chestplate = ItemUtils::get(Item::DIAMOND_CHESTPLATE, "&r&l&5E&fa&cs&dt&8e&2r &bChestplate", [], $enchants);
            
            //Leggings
            $enchants = [
                "naturewrath" => 2,
                "doomed" => 3,
                "antiknockback" => 1,
                "soulbound" => 5,
                "overload" => 10,
                "obsidianshield" => 1,
                "shrink" => 3,
                "autorepair" => 6,
                "angel" => 3
            ];
            $this->leggings = ItemUtils::get(Item::DIAMOND_LEGGINGS, "&r&l&5E&fa&cs&dt&8e&2r &bLeggings", [], $enchants);
            
            //Boots
            $enchants = [
                "doomed" => 3,
                "antiknockback" => 1,
                "soulbound" => 5,
                "overload" => 10,
                "heavy" => 5,
                "tank" => 5,
                "armored" => 5,
                "obsidianshield" => 1,
                "shrink" => 3,
                "autorepair" => 6,
                "angel" => 3,
                "springs" => 3,
                "gears" => 3
            ];
            $this->boots = ItemUtils::get(Item::DIAMOND_BOOTS, "&r&l&5E&fa&cs&dt&8e&2r &bBoots", [], $enchants);
            $npc->getArmorInventory()->setHelmet($this->helmet);
            $npc->getArmorInventory()->setChestplate($this->chestplate);
            $npc->getArmorInventory()->setLeggings($this->leggings);
            $npc->getArmorInventory()->setBoots($this->boots);
            $npc->getInventory()->setItem(0, $this->axe);
            $npc->getInventory()->setItem(1, $this->pickaxe);
            $effect = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 0, false);
            $npc->addEffect($effect);
            $npc->spawnToAll();
            return;
        }
		if(++$this->run === 60){ //Do every minute
		    $this->run = 0;
		    if($this->npc->getHealth() !== 0){
		    	LangManager::broadcast("bossevent-broadcast", $this->npc->getHealth(), number_format($this->npc->getMaxHealth()));
		    }
		}
		if(self::$boss_killer !== ""){
			LangManager::broadcast("bossevent-broadcast-kill", self::$boss_killer);
			$killer = $plugin->getServer()->getPlayerExact(self::$boss_killer);
			if($killer !== null){
				$items = [
				    $this->axe, $this->pickaxe, $this->helmet, $this->chestplate, $this->leggings, $this->boots 
				];
				$killer->getInventory()->addItem($items[array_rand($items)]);
			}
			$plugin->getScheduler()->cancelTask($this->getTaskId());
			$i = 0;
			asort(self::$boss_damages);
			foreach(array_reverse(self::$boss_damages) as $player => $damage){
				if($i === 5){
					break;
				}
				$money = $damage * 30000;
				Main::getInstance()->addMoney($player, $money);
				LangManager::broadcast("bossevent-broadcast-rank", ++$i, $player, $money);
			}
		}
	}
	
}
	