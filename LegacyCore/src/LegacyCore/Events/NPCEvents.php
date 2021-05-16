<?php

namespace LegacyCore\Events;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Listener;
use pocketmine\level\Position;
use pocketmine\item\Armor;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\math\Vector3;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use CustomEnchants\CustomEnchants\CustomEnchants;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use LegacyCore\Core;
use kenygamer\Core\Main;
use kenygamer\Core\entity\Bandit;
use kenygamer\Core\entity\Goblin;
use kenygamer\Core\entity\Knight;
use LegacyCore\Entities\Slapper;
use kenygamer\Core\entity\Vampire;
use LegacyCore\Tasks\NPCMoveTask;
use LegacyCore\Tasks\NPCSpawnTask;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

class NPCEvents implements Listener{
	/** @var Core */
	private $plugin;
	/** @var array int: Task */
	public $tasks = [];
	
	//TODO
	public const GOBLINS = [
	    "§l§6» §eNPC §rGoblin §l§6«" /*0*/,
	    "§l§6» §eNPC §rGuardian Goblin §l§6«" /*1*/,
	    "§l§6» §6Boss §rKing Goblin §l§6«" /*2*/
	];
	public const BANDITS = [
	    "§l§6» §eNPC §rBandit §l§6«" /*0*/,
	    "§l§6» §6Boss §rLeader Bandit §l§6«" /*1*/
	];
	public const KNIGHTS = [
        "§l§6» §eNPC §rWarrior Knight §l§6«" /*0*/,
        "§l§6» §eNPC §rArcher Knight §l§6«" /*1*/,
        "§l§6» §6Boss §rLord Knight §l§6«" /*2*/
	];
	public const VAMPIRES = [
	    "§l§6» §eNPC §rVampire §l§6«" /*0*/,
	    "§l§6» §6Boss §rMaster Vampire §l§6«" /*1*/
	];
	
	/** @var self|null */
	private static $instance = null;

    public function __construct(Core $plugin){
        $this->plugin = $plugin;
		self::$instance = $this;
	}
	
	public static function getInstance() : ?self{
		return self::$instance;
	}
    
    /**
     * @param EntityDeathEvent $event
     */
    public function onEntityDeath(EntityDeathEvent $event) : void{
    	if(in_array($event->getEntity()->getId(), $this->plugin->aliveBosses)){
    		unset($this->plugin->aliveBosses[array_search($event->getEntity()->getId(), $this->plugin->aliveBosses)]);
    	}
    }
	
	/**
     * @param EntitySpawnEvent $event
     */
	public function onEntitySpawn(EntitySpawnEvent $event) : void{
		$entity = $event->getEntity();
        if($entity instanceof Goblin){
			switch($entity->getNameTag()){
				case self::GOBLINS[0]:
				    $health = \kenygamer\Core\Main::mt_rand(20, 40);
				    $entity->setMaxHealth($health);
				    $entity->setHealth($health);
				    $entity->setScale(0.7);
				    break;
				case self::GOBLINS[1]:
				    $health = \kenygamer\Core\Main::mt_rand(20, 60);
				    $entity->setMaxHealth($health);
				    $entity->setHealth($health);
				    break;
			    case self::GOBLINS[2]:
			        $health = \kenygamer\Core\Main::mt_rand(100, 200);
			        $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), INT32_MAX, 0, false);
			        $entity->setMaxHealth($health);
			        $entity->setHealth($health);
			        $entity->addEffect($effect);
			        $entity->setScale(1.2);
			}
		}elseif($entity instanceof Bandit){
			switch($entity->getNameTag()){
				case self::BANDITS[0]:
				    $health = \kenygamer\Core\Main::mt_rand(180, 300);
				    $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 300*20, 1, false);
				    $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 0, false);
				    $entity->setMaxHealth($health);
				    $entity->setHealth($health);
				    $entity->addEffect($effect);
				    $entity->addEffect($effect2);
				    break;
				case self::BANDITS[1]:
				    $health = \kenygamer\Core\Main::mt_rand(500, 800);
				    $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), INT32_MAX, 2, false);
				    $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 2, false);
				    $entity->setMaxHealth($health);$entity->setHealth($health);
				    $entity->addEffect($effect);
				    $entity->addEffect($effect2);
			}
		}elseif($entity instanceof Knight){
			switch($entity->getNameTag()){
			    case self::KNIGHTS[0]:
			    case self::KNIGHTS[1]:
			        $health = \kenygamer\Core\Main::mt_rand(300, 500);
			        $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 300*20, 2, false);
			        $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 1, false);
			        $entity->setMaxHealth($health);
			        $entity->setHealth($health);
			        $entity->addEffect($effect);
			        $entity->addEffect($effect2);
			    case self::KNIGHTS[2]:
			        $health = \kenygamer\Core\Main::mt_rand(800, 1000);
			        $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), INT32_MAX, 3, false);
			        $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 3, false);
			        $entity->setMaxHealth($health);
			        $entity->setHealth($health);
			        $entity->addEffect($effect);
			        $entity->addEffect($effect2);
			        $entity->setScale(1.2);
			}
		}elseif($entity instanceof Vampire){
			switch($entity->getNameTag()){
			    case self::VAMPIRES[0]:
			        $health = \kenygamer\Core\Main::mt_rand(750, 900);
			        $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 600*20, 3, false);
			        $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 2, false);
			        $entity->setMaxHealth($health);
			        $entity->setHealth($health);
			        $entity->addEffect($effect);
			        $entity->addEffect($effect2);
			        break;
			    case self::VAMPIRES[1]:
			        $health = \kenygamer\Core\Main::mt_rand(2000, 5000);
			        $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), INT32_MAX, 4, false);
			        $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 4, false);
			        $entity->setMaxHealth($health);
			        $entity->setHealth($health);
			        $entity->addEffect($effect);
			        $entity->addEffect($effect2);
			}
		}
	}
	
	/**
     * @param EntityDamageByEntityEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		/** @var Player */
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		if($damager instanceof Goblin || $damager instanceof Bandit || $damager instanceof Knight || $damager instanceof Vampire){
			if(!isset($this->tasks[$damager->getId()])){
				$this->tasks[$damager->getId()] = $this->plugin->getScheduler()->scheduleRepeatingTask(new NPCMoveTask($this->plugin, $damager, $damager->getLevel()), 3);
			}
		}
		if($damager instanceof Goblin){
			$cost = 0;
			switch($damager->getName()){
				case self::GOBLINS[0]:
				    $cost = \kenygamer\Core\Main::mt_rand(0, 2500);
				    break;
				case self::GOBLINS[1]:
				    $cost = \kenygamer\Core\Main::mt_rand(0, 3000);
				    break;
				case self::GOBLINS[2]:
				    $cost = \kenygamer\Core\Main::mt_rand(0, 4500);
				    break;
			}
			Main::getInstance()->reduceMoney($entity, $cost);
		}elseif($damager instanceof Bandit){
			switch($damager->getName()){
				case self::BANDITS[0]:
				    if(\kenygamer\Core\Main::mt_rand(0, 250) <= 5){
				    	$effect = new EffectInstance(Effect::getEffect(Effect::POISON), 10*20, 0, true);
				    	$entity->addEffect($effect);
				    }
				    if(\kenygamer\Core\Main::mt_rand(0, 400) <= 5){
				    	$effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 5*20, 0, true);
				    	$entity->addEffect($effect);
				    }
				    break;
				case self::BANDITS[1]:
				    if(\kenygamer\Core\Main::mt_rand(0, 200) <= 5){
				    	$effect = new EffectInstance(Effect::getEffect(Effect::POISON), 60*20, 1, true);
				    	$entity->addEffect($effect);
				    }
				    if(\kenygamer\Core\Main::mt_rand(0, 350) <= 5){
				    	$effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 30*20, 0, true);
				    	$entity->addEffect($effect);
				    }
				    break;
			}
		}elseif($damager instanceof Knight){
			switch($damager->getName()){
				case self::KNIGHTS[0]:
				case self::KNIGHTS[1]:
				    if(\kenygamer\Core\Main::mt_rand(0, 300) <= 5){
				    	$effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 120*20, \kenygamer\Core\Main::mt_rand(1, 2), true);
				    	$entity->addEffect($effect);
				    }
				    break;
				case self::KNIGHTS[2]:
				    if(\kenygamer\Core\Main::mt_rand(0, 200) <= 5){
				    	$effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 300*20, \kenygamer\Core\Main::mt_rand(3, 4), true);
				    	$entity->addEffect($effect);
				    }
				    break;
			}
		}elseif($damager instanceof Vampire){
			switch($damager->getName()){
				case self::VAMPIRES[0]:
				    $cost = \kenygamer\Core\Main::mt_rand(100, 500);
				    if(!($entity->getCurrentTotalXp() - $cost <= 0)){
				    	$entity->subtractXp($cost);
				    }
				    $item = $entity->getInventory()->getItemInHand();
				    if($item instanceof Durable){
				    	$item->applyDamage(\kenygamer\Core\Main::mt_rand(20, 40));
				    	$entity->getInventory()->setItemInHand($item);
				    }
				    foreach($entity->getArmorInventory()->getContents() as $slot => $armor){
				    	if($armor instanceof Armor){
				    		$armor->applyDamage(\kenygamer\Core\Main::mt_rand(5, 10));
				    		$entity->getArmorInventory()->setItem($slot, $armor);
				    	}
					}
				case self::VAMPIRES[1]:
				    $cost = \kenygamer\Core\Main::mt_rand(1000, 5000);
				    if(!($entity->getCurrentTotalXp() - $cost <= 0)){
				    	$entity->subtractXp($cost);
				    }
				    $item = $entity->getInventory()->getItemInHand();
				    if($item instanceof Durable){
				    	$item->applyDamage(\kenygamer\Core\Main::mt_rand(50, 100));
				    	$entity->getInventory()->setItemInHand($item);
				    }
				    foreach($entity->getArmorInventory()->getContents() as $slot => $armor){
				    	if($armor instanceof Armor){
				    		$armor->applyDamage(\kenygamer\Core\Main::mt_rand(10, 20));
				    		$entity->getArmorInventory()->setItem($slot, $armor);
				    	}
					}
					break;
			}
		}
	}

	/**
     * @param EntityDamageEvent $event
     *
     * @priority NORMAL
     * @ignoreCancelled false
     */
	public function onDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Slapper){
			$event->setCancelled();
		}
		if($event instanceof EntityDamageByEntityEvent){
	    	$damager = $event->getDamager();
			if(($entity instanceof Slapper || $entity instanceof Goblin || $entity instanceof Bandit || $entity instanceof Knight || $entity instanceof Vampire) && $damager instanceof Player){
				if(isset($this->plugin->deletenpc[$damager->getLowerCaseName()])){
					unset($this->plugin->deletenpc[$damager->getLowerCaseName()]);
					$entity->flagForDespawn();
					$damager->sendMessage(TextFormat::GREEN . "NPC has been removed.");
				}else{
					switch($entity->getName()){
					    case "§l§9Duels":
						    $damager->chat("/duel");
						    break;
						case "§l§eAuction House":
						    $damager->chat("/ah");
						    break;
						case "§l§aWarp":
						    $damager->chat("/warp");
						    break;
						case "§l§bSkyWars":
						    $damager->chat("/sw");
						    break;
					}
				}
			}
			if(($entity instanceof Goblin && in_array($entity->getName(), self::GOBLINS)) || ($entity instanceof Bandit && in_array($entity->getName(), self::BANDITS)) || ($entity instanceof Knight && in_array($entity->getName(), self::KNIGHTS)) || ($entity instanceof Vampire && in_array($entity->getName(), self::VAMPIRES))){
				if(!isset($this->tasks[$entity->getId()])){
					$this->plugin->getScheduler()->scheduleRepeatingTask(new NPCMoveTask($this->plugin, $entity, $entity->getLevel()), 3);
				}
			}
		}
	}
	
	/**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
	public function onItems(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
	    if($item->equals(ItemUtils::get("king_goblin_egg"))){
			if ($player->getLevel()->getName() == "wild") {
				if (!isset($this->plugin->bosses[$player->getLowerCaseName()]) || time() > $this->plugin->bosses[$player->getLowerCaseName()] || $player->hasPermission("core.cooldown.bypass")) {
                    $this->plugin->bosses[$player->getLowerCaseName()] = time() + 600;
					// Spawn NPC Bosses Level Tier 1
					$block = $event->getBlock();
					$block->getLevel()->loadChunk($block->x >> 4, $block->z >> 4);
					
	             	$nbt = Entity::createBaseNBT($block, null, 300, 0);
		    	    $nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", "Boss"),
                        new ByteArrayTag("Data", NPCSpawnTask::$skinData),
		                new ByteArrayTag("GeometryData", NPCSpawnTask::$geometryData)
		            ]));
					$sword = Item::get(267, 0, 1);
				    $sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), \kenygamer\Core\Main::mt_rand(5, 8)));
				    $sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(101), 3));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 4));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(120), 1));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
				    $sword->setCustomName("§r§l§6Rare Goblin Sword§r\n§6Deathbringer IV\n§6Disarm Protection I\n§eAutorepair II\n§bDemonforged I\n§bBlind III");
				    $helmet = Item::get(306, 0, 1);
				    $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
				    $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $helmet->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$helmet->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 1));
				    $helmet->setCustomName("§r§bIron Helmet\n§6Tank I\n§eAutorepair I");
				    $item = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$item2 = Item::get(41, 0, \kenygamer\Core\Main::mt_rand(0, 5));
					$item3 = ItemUtils::get("uncommon_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$item4 = Item::get(310, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 5));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
					$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item4->setCustomName("§r§bDiamond Helmet\n§6Overload V\n§6Implants III\n§6Tank III");
					$item5 = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 15));
					$item6 = ItemUtils::get("mythic_note(05)")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
					$item7 = ItemUtils::get("green_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$item8 = ItemUtils::get("experience_bottle2(102)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
					$item9 = ItemUtils::get("mining_mask(2)");
				    $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), INT32_MAX, 0, false);
				    $health = \kenygamer\Core\Main::mt_rand(500, 1000);
			        $npc = new Goblin($player->getLevel(), $nbt);
			        $npc->setNameTag(self::GOBLINS[2]);
			        $npc->setNameTagAlwaysVisible(true);
				    $npc->addEffect($effect);
				    $npc->setScale(1.2);
				    $npc->setMaxHealth($health);
	                $npc->setHealth($health);
				    $npc->getArmorInventory()->setHelmet($helmet);
				    $npc->getInventory()->setItem(0, $sword);
				    $npc->getInventory()->setItem(1, $item);
			    	$npc->getInventory()->setItem(2, $item2);
			    	$npc->getInventory()->setItem(3, $item3);
			    	$npc->getInventory()->setItem(4, $item4);
			    	$npc->getInventory()->setItem(5, $item5);
			    	$npc->getInventory()->setItem(6, $item6);
			    	$npc->getInventory()->setItem(7, $item7);
					$npc->getInventory()->setItem(8, $item8);
					$npc->getInventory()->setItem(9, $item9);
			    	$npc->spawnToAll();
			    	try{
						$npc->spawnToAll();
					}catch(\InvalidStateException $e){ //unloaded chunks
					    LangManager::send("core-spawnboss-failed", $player);
					    $event->setCancelled(true);
					    return;
					}
					LangManager::send("core-spawnboss", $player);
					$player->getInventory()->removeItem(Item::get(383, 1, 1));
					$event->setCancelled(true);
				} else {
					LangManager::send("in-cooldown", $player);
				}
			} else {
				LangManager::send("core-spawnboss-wild", $player);
			}
		}
		if($item->equals(ItemUtils::get("lord_knight_egg"))){
			if ($player->getLevel()->getName() == "wild") {
				if (!isset($this->plugin->bosses[$player->getLowerCaseName()]) || time() > $this->plugin->bosses[$player->getLowerCaseName()] || $player->hasPermission("core.cooldown.bypass")) {
                    $this->plugin->bosses[$player->getLowerCaseName()] = time() + 600;
					// Spawn NPC Bosses Level Tier 4
	             	$nbt = Entity::createBaseNBT($player, null, 100, 0);
			    	$nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", "Boss"),
                        new ByteArrayTag("Data", NPCSpawnTask::$skinData),
		                new ByteArrayTag("GeometryData", NPCSpawnTask::$geometryData)
		            ]));
					$sword = Item::get(276, 0, 1);
				    $sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
				    $sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(101), 5));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 6));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(120), 5));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
				    $sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(144), 2));
				    $sword->setCustomName("§r§l§aDiamond Sword§r\n§6Deathbringer VI\n§6Disarm Protection I\n§eRage III\n§eBlessed III\n§eAutorepair V\n§bDemonforged V\n§bBlind V\n§6Accuracy II");
				    $helmet = Item::get(306, 0, 1);
				    $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 10));
				    $helmet->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $helmet->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 3));
					$helmet->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 5));
				    $helmet->setCustomName("§r§bIron Helmet\n§6Tank V\n§eAutorepair III");
					$chest = Item::get(307, 0, 1);
				    $chest->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 10));
				    $chest->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $chest->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 3));
					$chest->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 5));
				    $chest->setCustomName("§r§bIron Chestplate\n§6Armored V\n§eAutorepair III");
					$legg = Item::get(308, 0, 1);
				    $legg->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 10));
				    $legg->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $legg->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 3));
					$legg->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 5));
				    $legg->setCustomName("§r§bIron Leggings\n§6Heavy V\n§eAutorepair III");
					$boots = Item::get(309, 0, 1);
				    $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 10));
				    $boots->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
				    $boots->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 3));
					$boots->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 5));
					$boots->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 2));
		           	$boots->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(701), 2));
				    $boots->setCustomName("§r§bIron Boots\n§eAutorepair III\n§bGears II\n§bSprings II");
				    $item = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
					$item2 = Item::get(57, 0, \kenygamer\Core\Main::mt_rand(0, 5));
					$item3 = ItemUtils::get("uncommon_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$item4 = ItemUtils::get("atlas_gem");
					$item5 = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(1, 20));
					$item6 = ItemUtils::get("mythic_note(52)")->setCount(\kenygamer\Core\Main::mt_rand(1, 30));
					$item7 = ItemUtils::get("green_crystal")->setCount(\kenygamer\Core\Main::mt_rand(1, 6));
					$item8 = ItemUtils::get("experience_bottle2(107)")->setCount(\kenygamer\Core\Main::mt_rand(1, 4));
					$item9 = ItemUtils::get("mining_mask(3)");
					$effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), INT32_MAX, 3, false);
				    $effect2 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), INT32_MAX, 3, false);
				    $health = \kenygamer\Core\Main::mt_rand(2500, 5000);
			        $npc = new Knight($player->getLevel(), $nbt);
			        $npc->setNameTag("§l§6» §6Boss §rLord Knight §l§6«");
			        $npc->setNameTagAlwaysVisible(true);
				    $npc->addEffect($effect);
					$npc->addEffect($effect2);
				    $npc->setScale(1.2);
				    $npc->setMaxHealth($health);
	                $npc->setHealth($health);
				    $npc->getArmorInventory()->setHelmet($helmet);
					$npc->getArmorInventory()->setChestplate($chest);
					$npc->getArmorInventory()->setLeggings($legg);
					$npc->getArmorInventory()->setBoots($boots);
				    $npc->getInventory()->setItem(0, $sword);
				    $npc->getInventory()->setItem(1, $item);
			    	$npc->getInventory()->setItem(2, $item2);
			    	$npc->getInventory()->setItem(3, $item3);
			    	$npc->getInventory()->setItem(4, $item4);
			    	$npc->getInventory()->setItem(5, $item5);
			    	$npc->getInventory()->setItem(6, $item6);
			    	$npc->getInventory()->setItem(7, $item7);
					$npc->getInventory()->setItem(8, $item8);
					$npc->getInventory()->setItem(9, $item9);
					try{
						$npc->spawnToAll();
					}catch(\InvalidStateException $e){ //unloaded chunks
					    LangManager::send("core-spawnboss-failed", $player);
					    $event->setCancelled(true);
					    return;
					}
					LangManager::send("core-spawnboss", $player);
					$player->getInventory()->removeItem(Item::get(383, 1, 1));
					$event->setCancelled(true);
				} else {
					LangManager::send("in-cooldown", $player);
				}
			} else {
				LangManager::send("core-spawnboss-wild", $player);
			}
		}
	}
	
}