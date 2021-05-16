<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use kenygamer\Core\entity\Bandit;
use kenygamer\Core\entity\Goblin;

use LegacyCore\Tasks\GuardianTask;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Human;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Durable;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\utils\Random;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteArrayTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use CustomEnchants\CustomEnchants\CustomEnchants;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\LangManager;
use LegacyCore\Tasks\NPCSpawnTask;
use kenygamer\Core\entity\FireworksEntity;

final class OtherEvents implements Listener{
	/** @var Core */
	private $plugin = null;
	/** @var array */
	private $bookChances = [];
	/** @var array */
	private $minChanceIndex = [];
	/** @var array */
	private	$maxChanceIndex = [];
	/** @var int[] */
	private $reservedEnchants = [];

    public function __construct(Core $plugin){
        $this->plugin = $plugin;
		
		
		$reservedEnchants = Main::getInstance()->getConfig()->get("reserved-enchants", []);
		$reservedEnchants = ItemUtils::getEnchantments(array_combine($reservedEnchants, array_fill(0, count($reservedEnchants), 1)));
		$this->reservedEnchants = [];
		foreach($reservedEnchants as $instance){
			$this->reservedEnchants[] = $instance->getType()->getId();
		}
		
		$this->loadChances();
	}
	
	private function loadChances() : void{
		if(($plugin = Main::getInstance()) === null){
			return;
		}
		$enchants = $plugin->getPlugin("CustomEnchants")->enchants;
		
		/** @var array $types string => string[] */
		$types = [];
		foreach($enchants as $id => $data){
			list($name, $_, $_, $rarity, $maxLevel) = $data;
			$types[$rarity][] = $name;
		}
		
		$rarities = ["Common", "Uncommon", "Rare", "Mythic"];
		
		load:
		foreach($rarities as $rarity){
			$filename = mb_strtolower($rarity) . "_chances.js";
			$chances = (array) json_decode(@file_get_contents($this->plugin->getDataFolder() . $filename), true);
			
			$needChances = count($types[$rarity]);
			$haveChances = count($chances);
			$missingChances = max(0, $needChances - $haveChances); //verbose
			if($missingChances > 0){
				$this->plugin->getLogger()->warning($missingChances . " gaps found in " . $filename . ", regenerating all chance tables...");
				$sum = $lastRarityHighestChance = 0;
				foreach($rarities as $rar){
					$needChances = count($types[$rar]);
					$chances = range($lastRarityHighestChance, $lastRarityHighestChance = ($needChances + $sum));
					shuffle($chances);
					file_put_contents($this->plugin->getDataFolder() . mb_strtolower($rar) . "_chances.js", json_encode($chances));
					$sum += $needChances;
				}
				goto load;
				break;
			}
		}
		
		//Sort in ascending rarity
		$types = [
		   "Common" => $types["Common"],
		   "Uncommon" => $types["Uncommon"],
		   "Rare" => $types["Rare"],
		   "Mythic" => $types["Mythic"]
		];
		
		$add = 0;
		foreach($types as $rarity => $names){
			$this->minChanceIndex[$rarity] = $add;
			$this->maxChanceIndex[$rarity] = count($names) + $add - 1; //-1 since this is index
			$add += count($names);
		}
		
		foreach($types as $rarity => $names){
		    foreach($names as $i => $name){
		    	$types[$rarity][$i] = [$name, $this->minChanceIndex[$rarity] + $i];
		    }
		}
		$this->bookChances = $types;
	}
	
	/**
	 * @param string $rarity
	 * @param int $calls Recursion tracker
	 * @return Item
	 */
	private function getRandomBook(string $rarity, int $calls = 0) : Item{
		$chanceThrown = \kenygamer\Core\Main::mt_rand($this->minChanceIndex[$rarity] ?? -1, $this->maxChanceIndex[$rarity] ?? -1);
		foreach($this->bookChances as $rarity => $books){
			foreach($books as $data){
				list($name, $chance) = $data;
				if($chanceThrown === $chance){
				    //Level chance
				    $enchantment = CustomEnchants::getEnchantmentByName($name);
				    if($enchantment === null){
				    	throw new \RuntimeException("Enchantment " . $name . " not found");
				    }
				    
				    if(in_array($enchantment->getId(), $this->reservedEnchants)){
				    	if(++$calls >= 254){
							return ItemFactory::get(Item::AIR);
				    	}
				    	return $this->getRandomBook($rarity, $calls);
				    } 
				    $levels = range(1, $enchantment->getMaxLevel());
				    foreach(array_reverse($levels) as $i => $level){
				    	if(\kenygamer\Core\Main::mt_rand(1, min(0xfffffff, pow(10, $level))) <= 10 * $level){
				    	   return ItemUtils::get(Item::ENCHANTED_BOOK, "", [], [$name => $level]);
				    	}
				    }
				}
			}
		}
		throw new \RuntimeException("Rarity " . $rarity . " not found");
	}
	
	/**
     * @param EntityDamageEvent $event
     */
	public function onDamage(EntityDamageEvent $event) : void{
        $entity = $event->getEntity();
        if ($event instanceof EntityDamageByEntityEvent) {
            $damager = $event->getDamager();
            if ($damager instanceof Player) {
				$inventory = $damager->getInventory();
	          	$hand = $inventory->getItemInHand();
		        $nbt = $hand->getNamedTag();
				// Sword Increase Attack Damage
				if ($nbt->getByte("IsValidSword", false) == true) {
		        	if ($nbt->getInt("AttackVersion", 1.0) == $this->plugin->attack) {
			        	$value = $nbt->getInt("DamageValue");
				        $event->setModifier($value, EntityDamageEvent::CAUSE_ENTITY_ATTACK);
					}
				}
				// Bow Increase Attack Damage
				if ($nbt->getByte("IsValidBow", false) == true) {
		        	if ($nbt->getInt("AttackVersion", 1.0) == $this->plugin->attack) {
			        	$value = $nbt->getInt("DamageValue");
				        $event->setModifier($value, EntityDamageEvent::CAUSE_PROJECTILE);
					}
				}
			}
		}
	}

	/**
     * @param BlockBreakEvent $event
     */
	public function onBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$drops = $event->getDrops();
		// Lucky Block
		if ($player->getGamemode() === 0) {
            if ($block->getId() == 19 and $block->getDamage() == 0) {
                $drops = array(Item::get(0, 0, 0));
                $event->setDrops($drops);
                $reward = rand(1, 73);
				$event->setCancelled(false);
                switch($reward) {
                    case 1:
					$item = Item::get(276, 0, 1);
                    $player->getInventory()->addItem($item);
                    break;
                    case 2:
                    break;
                    case 3:
                    $bonus = \kenygamer\Core\Main::mt_rand(10000, 30000);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 4:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(1, 5));
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
                    case 5:
                    $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 300*20, 4, true);
                    $player->addEffect($effect);
                    break;
                    case 6:
                    $tier = ItemUtils::get("book", "", [], ["blind" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 7:
                    $tier = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 8:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(203), 2));
                    $item->setCustomName("§r§bDiamond Pickaxe§r\n§eAutorepair II\n§bQuickening II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 9:
                    $tier = ItemUtils::get("book", "", [], ["energizing" => \kenygamer\Core\Main::mt_rand(1, 2)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 10:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(3, 4));
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
                    case 11:
                    $tier = Item::get(322, 0, 30);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 12:
                    $tier = Item::get(264, 0, 30);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 13:
                    break;
                    case 14:
                    $tier = ItemUtils::get("experience_bottle2(102)")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 15:
                    $item = Item::get(277, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(212), 4));
                    $item->setCustomName("§r§l§6Legendary Shovel§r\n§6Money Farm IV\n§bHaste IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 16:
                    $tier = ItemUtils::get("book", "", [], ["lifesteal" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 17:
                    if(\kenygamer\Core\Main::mt_rand(1, 20) === 1){
                    	$tier = ItemUtils::get("book", "", [], ["spider" => \kenygamer\Core\Main::mt_rand(1, 3)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    	$player->getInventory()->addItem($tier);
                    }
                    break;
                    case 18:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(3, 4), 4);
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
                    case 19:
                    $tier = Item::get(41, 0, 30);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 20:
                    $tier = Item::get(7, 0, 30);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 21:
                    $tier = ItemUtils::get("book", "", [], ["energizing" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 22:
                    $ore = Item::get(266, 0, 64);
                    $ore2 = Item::get(265, 0, 64);
                    $ore3 = Item::get(264, 0, 64);
                    $ore4 = Item::get(351, 4, \kenygamer\Core\Main::mt_rand(1, 4));
                    $ore5 = Item::get(263, 0, 64);
                    $player->getInventory()->addItem($ore);
                    $player->getInventory()->addItem($ore2);
                    $player->getInventory()->addItem($ore3);
                    $player->getInventory()->addItem($ore4);
                    $player->getInventory()->addItem($ore5);
                    break;
                    case 23:
                    $book = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
                    $player->getInventory()->addItem($book);
                    break;
                    case 24:
                    $bonus = \kenygamer\Core\Main::mt_rand(10000, 500000);
                    Main::getInstance()->addMoney($player, $bonus);
                    $player->addMoney($bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 25:
                    $tier = Item::get(388, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 26:      
					$item = ItemUtils::get("shard_note");
                    $player->getInventory()->addItem($item);
                    break;
                    case 27:
                    $tier = Item::get(278, 0, 1);
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 6));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(203), 2));
                    $tier->setCustomName("§r§bDiamond Pickaxe\n§eAutorepair V\n§bQuickening II");
                    $player->getInventory()->addItem($tier);
                    break;
                    case 28:
                    $tier = Item::get(2, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 29:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(1, 5), 5);
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
                    case 30:
                    $level = $this->plugin->getServer()->getLevelByName("wild");
                    $x = rand(-5000, 5000);
                    $y = rand(128, 256);
                    $z = rand(-5000, 5000);
                    $player->teleport(new Position($x, $y, $z, $level));
                    $player->sendMessage("core-wild");
                    break;
                    case 31:
                    $tier = Item::get(261, 0, 1);
                    $tier2 = Item::get(262, 0, 64);
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 5));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 3));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(316), 1));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(318), 2));
                    $tier->setCustomName("§r§l§9Bow§r\n§6Longbow III\n§eVolley I\n§bBow Lifesteal II");
                    $player->getInventory()->addItem($tier);
                    $player->getInventory()->addItem($tier2);
                    break;
                    case 32:
                    $tier = ItemUtils::get("common_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $tier2 = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
                    $player->getInventory()->addItem($tier);
                    $player->getInventory()->addItem($tier2);
                    break;
                    case 33:
                    $tier = Item::get(279, 0, 1);
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$tier->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(138), 2));
					$tier->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(205), 1));
                    $tier->setCustomName("§r§bDiamond Axe\n§6Insanity II\n§eRage III\n§eLumberjack I");
                    $player->getInventory()->addItem($tier);
                    break;
                    case 34:
                    $tier = ItemUtils::get("book", "", [], ["blind" => \kenygamer\Core\Main::mt_rand(1, 5)])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 35:
                    $item = Item::get(261, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(21), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(311), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(317), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(318), 5));
                    $item->setCustomName("§r§l§eFire Bow§r\n§6Blaze I\n§eVirus III\n§eVolley IV\n§bBow Lifesteal V");
					$item2 = Item::get(262, 0, 64);
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
                    case 36:
                    $effect = new EffectInstance(Effect::getEffect(Effect::INSTANT_DAMAGE), 30*20, 2, true);
                    $player->addEffect($effect);
                    break;
                    case 37:
                    $exp = \kenygamer\Core\Main::mt_rand(100, 500);
                    $player->addXp($exp);
                    $player->sendMessage("exp-bonus", $exp);
                    break;
                    case 38:
                    $tier = ItemUtils::get("book", "", [], ["radar" => 2])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 39:
                    break;
                    case 40:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(1, 7));
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
                    case 41:
                    $tier = Item::get(57, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 42:
                    $tier = Item::get(12, 0, 64);
                    $tier2 = Item::get(12, 1, 64);
                    $player->getInventory()->addItem($tier);
                    $player->getInventory()->addItem($tier2);
                    break;
                    case 43:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(1, 3), 3);
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
                    case 44:
                    $exp = \kenygamer\Core\Main::mt_rand(50, 250);
                    $player->addXp($exp);
                    $player->sendMessage("exp-bonus", $exp);
                    break;
                    case 45:
                    $tier = Item::get(32, 0, 1000);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 46:
                    $item = Item::get(310, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload IV\n§6Anti Knockback I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 47:
                    $item = Item::get(315, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(419), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 8));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
                    $item->setCustomName("§r§l§6Divine Gold Chestplate§r\n§6Overload VIII\n§6Armored V\n§6Anti Knockback I\n§eRevive V\n§eShileded IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 48:
                    $item = Item::get(316, 0, 1);
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 8));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
                    $item->setCustomName("§r§l§6Divine Gold Pants§r\n§6Overload VIII\n§6Armored V\n§6Anti Knockback I\n§eRevive V");
                    $player->getInventory()->addItem($item);
                    break;
                    case 49:
                    $tier = Item::get(322, 0, 20);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 50:
                    $tier = Item::get(466, 0, 10);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 51:
                    $item = Item::get(276, 0, 1);
                    $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(123), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(130), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(131), 1));
                    $item->setCustomName("§r§l§aSword of a Thousand Truths§r\n§6Curse I\n§6Drain IV\n§eObliterate I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 52:
                    $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 300*20, 9, true);
                    $player->addEffect($effect);
                    break;
                    case 53:
                    $item = Item::get(310, 0, 1);
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 3));
                    $item->setCustomName("§r§eFire Helmet\n§6Overload III\n§6Tank III\n§6Heavy III\n§6Amored III\n§6Implants III");
                    $item2 = Item::get(311, 0, 1);
		         	$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
		         	$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item2->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 3));
		           	$item2->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
			        $item2->setCustomName("§r§eFire Chestplate\n§6Overload III\n§eRevive III");
                    $item3 = Item::get(312, 0, 1);
		        	$item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
			        $item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(405), 1));
			        $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(418), 2));
			        $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(419), 2));
		        	$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		           	$item3->setCustomName("§r§eFire Leggings\n§6Overload III\n§eShileded II\n§eObsidian Shield I\n§bAngel II");
                    $item4 = Item::get(313, 0, 1);
		        	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
		          	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(2), 3));
		         	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(7), 3));
		         	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 3));
		        	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		          	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 2));
		           	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(701), 2));
		         	$item4->setCustomName("§r§eFire Boots\n§6Overload III\n§eDrunk III\n§bGears II\n§bSprings II");
                    $item5 = Item::get(276, 0, 1);
		          	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
		         	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 4));
		        	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 5));
			        $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
			        $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 3));
		        	$item5->setCustomName("§r§eFire Sword\n§6Deathbringer V\n§eAutorepair II\n§eCripple III\n§bLifesteal IV");
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 54:
                    $item = Item::get(359, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
                    $item->setCustomName("§r§bShears\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 55:
                    $tier = Item::get(400, 0, 50);
                    $tier2 = Item::get(260, 0, 50);
                    $player->getInventory()->addItem($tier);
                    $player->getInventory()->addItem($tier2);
                    break;
                    case 56:
                    $tier = Item::get(49, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 57:
                    $item = Item::get(313, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
                    $item->setCustomName("§r§bDiamond Boots\n§6Overload V\n§6Anti Knockback I\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 58:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(201), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(207), 4));
                    $item->setCustomName("§r§l§cFire Pickaxe§r\n§eAutorepair II\n§eDriller IV\n§bHaste IV\n§bSmelting I");
                    $player->getInventory()->addItem($item);
                    break;
                    case 59:
                    $tier = Item::get(399, 0, 10);
                    $player->getInventory()->addItem($tier);
                    break;
                    case 60:
                    $item = Item::get(317, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 8));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 2));
                    $item->setCustomName("§r§l§6Divine Gold Boots§r\n§6Overload VIII\n§6Armored V\n§6Anti Knockback I\n§eRevive V\n§bGears II");
                    $player->getInventory()->addItem($item);
                    break;
                    case 61:
                    $item = Item::get(314, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 8));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(409), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 8));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(429), 1));
                    $item->setCustomName("§r§l§6Divine Gold Helmet§r\n§6Overload VIII\n§6Armored V\n§6Anti Knockback I\n§6Endershift IV\n§eRevive III");
                    $player->getInventory()->addItem($item);
                    break;
                    case 62:
                    $effect = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 120*20, 4, true);
                    $player->addEffect($effect);
					$this->plugin->getScheduler()->scheduleDelayedTask(new GuardianTask($this->plugin, $player), 1);
                    break;
                    case 63:
                    $item = Item::get(310, 0, 1);
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
			        $item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(425), 3));
		         	$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(426), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(427), 3));
			        $item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(800), 2));
                    $item->setCustomName("§r§bDiamond Helmet\n§6Overload III\n§6Tank III\n§6Heavy III\n§6Amored III\n§6Implants II");
                    $item2 = Item::get(311, 0, 1);
		         	$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
		         	$item2->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item2->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(413), 3));
		           	$item2->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
			        $item2->setCustomName("§r§bDiamond Chestplate\n§6Overload III\n§eRevive III");
                    $item3 = Item::get(312, 0, 1);
		        	$item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 4));
			        $item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(405), 1));
			        $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(418), 2));
			        $item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(419), 2));
		        	$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		           	$item3->setCustomName("§r§bDiamond Leggings\n§6Overload III\n§eShileded II\n§eObsidian Shield I\n§bAngel II");
                    $item4 = Item::get(313, 0, 1);
		        	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(0), 5));
		          	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(2), 3));
		         	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(7), 3));
		         	$item4->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
	                $item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(410), 3));
		        	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(422), 3));
		          	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(700), 2));
		           	$item4->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(701), 2));
		         	$item4->setCustomName("§r§bDiamond Boots\n§6Overload III\n§eDrunk III\n§bGears II\n§bSprings II");
                    $item5 = Item::get(276, 0, 1);
		          	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 4));
		         	$item5->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 2));
	                $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 3));
		        	$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 4));
			        $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
			        $item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 3));
					$item5->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(121), 3));
		        	$item5->setCustomName("§r§bDiamond Sword\n§6Gravity III\n§6Deathbringer IV\n§eAutorepair II\n§eCripple III\n§bLifesteal III");
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    $player->getInventory()->addItem($item3);
                    $player->getInventory()->addItem($item4);
                    $player->getInventory()->addItem($item5);
                    break;
                    case 64:
                    $tier = ItemUtils::get("diamond_apple");
                    $player->getInventory()->addItem($tier);
                    break;
                    case 65:
                    $tier = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 4));
                    $player->getInventory()->addItem($tier);
                    break;
                    case 66:
                    $item = Item::get(283, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 6));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 2));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
                    $item->setCustomName("§r§l§6Divine Gold Sword Legendary§r\n§6Deathbringer VI\n§eAutorepair II\n§eRage III\n§eBlessed III\n§eCripple IV\n§bLifesteal V");
                    $player->getInventory()->addItem($item);
                    break;
                    case 67:
                    $item = Item::get(278, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(15), 7));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(204), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(212), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(213), 3));
                    $item->setCustomName("§r§bDiamond Pickaxe§r\n§6Grind III\n§6Money Farm IV\n§bHaste IV");
                    $player->getInventory()->addItem($item);
                    break;
                    case 68:
                    $fire = \kenygamer\Core\Main::mt_rand(10, 30);
                    $player->setOnFire($fire);
                    break;
                    case 69:
                    $bonus = \kenygamer\Core\Main::mt_rand(10000, 100000);
                    Main::getInstance()->addMoney($player, $bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
                    case 70:
                    $item = ItemUtils::get("book", "", [], ["blind" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
                    break;
					case 71:
					$amount = \kenygamer\Core\Main::mt_rand(1, 3);
					$item = Item::get(276, 0, 1);
		            $item->setCustomName("§r§6Diamond Sword");
		         	$item->setLore([
			        '§r§a+' . $amount . ' Attack Damage'
		            ]);
		            $nbt = $item->getNamedTag();
		            $nbt->setTag(new ByteTag("IsValidSword", true));
			        $nbt->setTag(new IntTag("AttackVersion", $this->plugin->attack));
		         	$nbt->setTag(new IntTag("DamageValue", $amount));
		         	$item->setCompoundTag($nbt);
			        $player->getInventory()->addItem($item);
					break;
					case 72:
					$nbt = Entity::createBaseNBT($player, null, 5, 2);
		            $nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", "Boss"),
                        new ByteArrayTag("Data", NPCSpawnTask::$skinData),
		                new ByteArrayTag("GeometryData", NPCSpawnTask::$geometryData)
		            ]));
					$health = \kenygamer\Core\Main::mt_rand(20, 40);
					$sword = Item::get(272, 0, 1);
					$item = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
					$item2 = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
					$item3 = ItemUtils::get("book", "", [], ["armored" => 3])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$item4 = Item::get(311, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$item5 = Item::get(466, 0, \kenygamer\Core\Main::mt_rand(0, 10));
			    	$npc = new Goblin($player->getLevel(), $nbt);
			    	$npc->setNameTag("§l§6» §eNPC §rGoblin §l§6«");
			    	$npc->setNameTagAlwaysVisible(true);
					$npc->setMaxHealth($health);
	            	$npc->setHealth($health);
					$npc->setScale(0.7);
					$npc->getInventory()->setItem(0, $sword);
					$npc->getInventory()->setItem(1, $item);
					$npc->getInventory()->setItem(2, $item2);
					$npc->getInventory()->setItem(3, $item3);
					$npc->getInventory()->setItem(4, $item4);
					$npc->getInventory()->setItem(5, $item5);
			    	$npc->spawnToAll();
					$player->sendMessage("core-spawnboss");
					break;
					case 73:
					$nbt = Entity::createBaseNBT($player, null, 4, 2);
			    	$nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", "Boss"),
                        new ByteArrayTag("Data", NPCSpawnTask::$skinData),
		                new ByteArrayTag("GeometryData", NPCSpawnTask::$geometryData)
		            ]));
					$health = \kenygamer\Core\Main::mt_rand(20, 40);
					$sword = Item::get(272, 0, 1);
					$item = ItemUtils::get("rare_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
					$item2 = ItemUtils::get("mythic_note(50)")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$item3 = ItemUtils::get("book", "", [], ["armored" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$item4 = ItemUtils::get("experience_bottle2(100)")->setCount(\kenygamer\Core\Main::mt_rand(1, 30));
					$item5 = ItemUtils::get("common_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 5));
					$item6 = ItemUtils::get("shield");
			    	$npc = new Goblin($player->getLevel(), $nbt);
			    	$npc->setNameTag("§l§6» §eNPC §rGoblin §l§6«");
			    	$npc->setNameTagAlwaysVisible(true);
					$npc->setMaxHealth($health);
	            	$npc->setHealth($health);
					$npc->setScale(0.7);
					$npc->getInventory()->setItem(0, $sword);
					$npc->getInventory()->setItem(1, $item);
					$npc->getInventory()->setItem(2, $item2);
					$npc->getInventory()->setItem(3, $item3);
					$npc->getInventory()->setItem(4, $item4);
					$npc->getInventory()->setItem(5, $item5);
					$npc->getInventory()->setItem(6, $item6);
			    	$npc->spawnToAll();
					$player->sendMessage("core-spawnboss");
					break;
				}
			}
		}
		// Rainbow Lucky Block
	    if ($player->getGamemode() === 0) {
            if ($block->getId() == 19 and $block->getDamage() == 1) {
                $drops = array(Item::get(0, 0, 0));
                $event->setDrops($drops);
                $reward = rand(1, 55);
				$event->setCancelled(false);
                switch($reward){
					case 1:
				    $tier = Item::get(57, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
				    case 2:
                    $tier = ItemUtils::get("diamond_apple");
                    $player->getInventory()->addItem($tier);
                    break;
				    case 3:
                    $effect = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 300*20, 4, true);
                    $player->addEffect($effect);
                    $this->plugin->getScheduler()->scheduleDelayedTask(new GuardianTask($this->plugin, $player), 1);
                    break;
					case 4:
                    $bonus = \kenygamer\Core\Main::mt_rand(100000, 2500000);
                    Main::getInstance()->addMoney($player, $bonus);
                    $player->sendMessage("money-bonus", $bonus);
                    break;
					case 5:
                    $tier = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
                    break;
				  	case 6:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(5, 10), 10);
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
				    case 7:
                    $exp = \kenygamer\Core\Main::mt_rand(1000, 5000);
                    $player->addXp($exp);
                    $player->sendMessage("exp-bonus", $exp);
                    break;
					case 8:
                    $effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 300*20, 0, true);
                    $player->addEffect($effect);
                    $this->plugin->getScheduler()->scheduleDelayedTask(new GuardianTask($this->plugin, $player), 1);
                    break;
					case 9:
                    $effect = new EffectInstance(Effect::getEffect(Effect::INSTANT_DAMAGE), 60*20, 4, true);
                    $player->addEffect($effect);
                    break;
					case 10:
					if (count($armor = $player->getArmorInventory()->getContents(false)) > 0) {
					    $item = $armor[array_rand($armor)];
                        $player->getArmorInventory()->removeItem($item);
                        $player->dropItem($item);
					}
					break;
					case 11:
                    $fire = rand(10, 30);
                    $player->setOnFire($fire);
                    break;
					case 12:
                    $explosion = new Explosion($block, \kenygamer\Core\Main::mt_rand(5, 7), 7);
                    $explosion->explodeB();
                    $player->sendMessage("core-boom");
                    break;
					case 13:
                    $tier = ItemUtils::get("rare_book")->setCount(\kenygamer\Core\Main::mt_rand(1, 3));
                    $player->getInventory()->addItem($tier);
				    break;
					case 14:
                    $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 60*20, 3, true);
                    $player->addEffect($effect);
                    break;
					case 15:
                    $tier = ItemUtils::get("ultra_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 15));
                    $player->getInventory()->addItem($tier);
                    break;
					case 16:
				    if (count($armor = $player->getArmorInventory()->getContents(false)) > 0) {
				    	$item = $armor[array_rand($armor)];
                        $player->getArmorInventory()->removeItem($item);
                        $player->dropItem($item);
				    }
					break;
					case 17:
                    $tier = ItemUtils::get("mythic_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
                    $player->getInventory()->addItem($tier);
                    break;
					case 18:
                    break;
				    case 19:
                    $level = $this->plugin->getServer()->getLevelByName("wild");
                    $x = rand(-5000, 5000);
                    $y = rand(128, 256);
                    $z = rand(-5000, 5000);
                    $player->teleport(new Position($x,$y,$z,$level));
                    $player->sendMessage("core-wild");
                    break;
					case 20:
				    $effect = new EffectInstance(Effect::getEffect(Effect::LEVITATION), 10*20, 9, true);
                    $player->addEffect($effect);
                    break;
					case 21:
					$tier = Item::get(41, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
					case 22:
					$tier = ItemUtils::get("mythic_note(10)")->setCount(\kenygamer\Core\Main::mt_rand(1, 10));
                    $drops = $event->getDrops();
                    $drops[] = $tier;
                    $event->setDrops($drops);
					break;
					case 23:
					$effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 120*20, 3, true);
                    $player->addEffect($effect);
					break;
					case 24:
                    $item = Item::get(261, 0, 1);
                    $item2 = Item::get(262, 0, 64);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 4));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(19), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(21), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(305), 4));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(311), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(317), 5));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(318), 5));
                    $item->setCustomName("§r§l§6Dragon Fire Bow§r\n§6Blaze I\n§6Disarm Protection I\n§eVirus IV\n§eVolley IV\n§bBow Lifesteal V");
                    $player->getInventory()->addItem($item);
                    $player->getInventory()->addItem($item2);
                    break;
					case 25:
					$tier = Item::get(57, 0, 128);
                    $player->getInventory()->addItem($tier);
					break;
					case 26:
					$tier = Item::get(466, 0, 30);
                    $drops = $event->getDrops();
                    $drops[] = $tier;
                    $event->setDrops($drops);
					break;
					case 27:
					$tier = Item::get(322, 0, 30);
                    $drops = $event->getDrops();
                    $drops[] = $tier;
                    $event->setDrops($drops);
					break;
					case 28:
					$tier = ItemUtils::get("book", "", [], ["armored" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
					break;
					case 29:
					$player->addMoney($money = \kenygamer\Core\Main::mt_rand(1000000, 10000000));
					$player->sendMessage("money-loss", $money);
					break;
					case 30:
				    $tier = Item::get(133, 0, 64);
                    $player->getInventory()->addItem($tier);
                    break;
					case 31:
					$tier = ItemUtils::get("experience_bottle(104)")->setCount(\kenygamer\Core\Main::mt_rand(0, 3));
                    $player->getInventory()->addItem($tier);
					break;
					case 32:
					$item = ItemUtils::get("book", "", [], ["disarmprotection" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 33:
					$tier = ItemUtils::get("experience_bottle(105)")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($tier);
					break;
					case 34:
					$item = ItemUtils::get("book", "", [], ["spitsweb" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 35:
                    $item = Item::get(455, 0, 1);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(28), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(135), 1));
					$item->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(1000), 3));
                    $item->setCustomName("§r§bTrident§r\n§6Disarm Protection I\n§6Nautica III\n§eAutorepair I");
                    $player->getInventory()->addItem($item);
                    break;
					case 36:
                    $item = ItemUtils::get("book", "", [], ["bleeding" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
                    break;
					case 37:
                    $item = ItemUtils::get("book", "", [], ["freeze" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
                    break;
					case 38:
                    $item = ItemUtils::get("book", "", [], ["freeze" => 2])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($item);
                    break;
					case 39:
					$book = ItemUtils::get("book", "", [], ["nutrition" => 4])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
                    break;
					case 40:
					$book = ItemUtils::get("book", "", [], ["nutrition" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
                    $player->getInventory()->addItem($book);
                    break;
					case 41:
					$tier = ItemUtils::get("experience_bottle2(101)")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
                    $player->getInventory()->addItem($tier);
					break;
					case 42:
                    $player->subtractXp($exp = \kenygamer\Core\Main::mt_rand(10000, 100000));
                    $player->sendMessage("exp-loss", $exp);
					break;
					case 43:
					$item = ItemUtils::get("book", "", [], ["deathbringer" => 6])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 44:
					$item = ItemUtils::get("book", "", [], ["darkroot" => 1])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 45:
					$item = ItemUtils::get("book", "", [], ["darkroot" => 2])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 46:
					$tier = ItemUtils::get("knight_note");
                    $player->getInventory()->addItem($tier);
                    break;
					case 47:
					$item = Item::get(450, 0, 1);
					$player->getInventory()->addItem($item);
					break;
					case 48:
					$amount = \kenygamer\Core\Main::mt_rand(1000000, 5000000);
					$currentamount = Main::getInstance()->getEntry($player, Main::ENTRY_BOUNTY);
					if($currentamount != null){
						Main::getInstance()->registerEntry($player, Main::ENTRY_BOUNTY, $currentamount + $amount);
					}else{
						Main::getInstance()->getEntry($player, Main::ENTRY_BOUNTY, $amount);
					}
					$player->getServer()->broadcastMessage(LangManager::translate("core-bounty-broadcast", $player->getName(), $amount));
					break;
					case 49:
					$item = ItemUtils::get("book", "", [], ["moneyfarm" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$player->getInventory()->addItem($item);
					break;
					case 50:
					$amount = \kenygamer\Core\Main::mt_rand(1000000, 5000000);
					$currentamount = Main::getInstance()->getEntry($player, Main::ENTRY_BOUNTY);
					if($currentamount != null){
						Main::getInstance()->registerEntry($player, Main::ENTRY_BOUNTY, $currentamount + $amount);
					}else{
						Main::getInstance()->getEntry($player, Main::ENTRY_BOUNTY, $amount);
					}
					$player->getServer()->broadcastMessage(LangManager::translate("core-bounty-broadcast", $player->getName(), $amount));
					break;
					case 51:
					$amount = \kenygamer\Core\Main::mt_rand(1, 3);
					$item = Item::get(276, 0, 1);
		            $item->setCustomName("§r§6Diamond Sword");
		         	$item->setLore([
			        '§r§a+' . $amount . ' Attack Damage'
		            ]);
		            $nbt = $item->getNamedTag();
		            $nbt->setTag(new ByteTag("IsValidSword", true));
			        $nbt->setTag(new IntTag("AttackVersion", $this->plugin->attack));
		         	$nbt->setTag(new IntTag("DamageValue", $amount));
		         	$item->setCompoundTag($nbt);
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 5));
					$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
			        $player->getInventory()->addItem($item);
					break;
					case 52:
					$nbt = Entity::createBaseNBT($player, null, 5, 2);
		            $nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", "Boss"),
                        new ByteArrayTag("Data", NPCSpawnTask::$skinData),
		                new ByteArrayTag("GeometryData", NPCSpawnTask::$geometryData)
		            ]));
					$health = \kenygamer\Core\Main::mt_rand(20, 40);
					$sword = Item::get(272, 0, 1);
					$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 3));
					$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 1));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(101), 3));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 1));
					$sword->setCustomName("§r§eGoblin Sword\n§eAutorepair I\n§bBlind III");
					$item = ItemUtils::get("enchanted_diamond_apple");
					$item2 = ItemUtils::get("book", "", [], ["moneyfarm" => 5])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$item3 = ItemUtils::get("book", "", [], ["armored" => 3])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$item4 = ItemUtils::get("experience_bottle2(101)")->setCount(\kenygamer\Core\Main::mt_rand(0, 5));
			    	$npc = new Goblin($player->getLevel(), $nbt);
			    	$npc->setNameTag("§l§6» §eNPC §rGoblin §l§6«");
			    	$npc->setNameTagAlwaysVisible(true);
					$npc->setMaxHealth($health);
	            	$npc->setHealth($health);
					$npc->setScale(0.7);
					$npc->getInventory()->setItem(0, $sword);
					$npc->getInventory()->setItem(1, $item);
					$npc->getInventory()->setItem(2, $item2);
					$npc->getInventory()->setItem(3, $item3);
					$npc->getInventory()->setItem(4, $item4);
			    	$npc->spawnToAll();
					$player->sendMessage("core-spawnboss");
					break;
					case 53:
					$nbt = Entity::createBaseNBT($player, null, 4, 2);
			    	$nbt->setTag(new CompoundTag("Skin", [
                        new StringTag("Name", "Boss"),
                        new ByteArrayTag("Data", NPCSpawnTask::$skinData),
		                new ByteArrayTag("GeometryData", NPCSpawnTask::$geometryData)
		            ]));
					$health = \kenygamer\Core\Main::mt_rand(20, 40);
					$sword = Item::get(267, 0, 1);
					$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 7));
					$sword->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 3));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(101), 5));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 4));
					$sword->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 3));
					$sword->setCustomName("§r§eGoblin Sword\n§6Deathbringer IV\n§eAutorepair III\n§bBlind V");
					$item = ItemUtils::get("enchanted_diamond_apple");
					$item2 = ItemUtils::get("book", "", [], ["moneyfarm" => 8])->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
					$item3 = Item::get(283, 0, \kenygamer\Core\Main::mt_rand(0, 1));
					$item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(9), 10));
					$item3->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment(17), 5));
					$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(100), 5));
					$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(102), 6));
					$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(108), 5));
					$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(109), 4));
					$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(125), 3));
					$item3->addEnchantment(new EnchantmentInstance(CustomEnchants::getEnchantment(126), 3));
                    $item3->setCustomName("§r§l§6Goblin Gold Sword§r\n§6Deathbringer VI\n§eAutorepair V\n§eRage III\n§eBlessed III\n§eCripple IV\n§bLifesteal V");
					$item4 = ItemUtils::get("experience_bottle2(101)")->setCount(\kenygamer\Core\Main::mt_rand(0, 30));
					$item5 = ItemUtils::get("legendary_key")->setCount(\kenygamer\Core\Main::mt_rand(0, 1));
			    	$npc = new Goblin($player->getLevel(), $nbt);
			    	$npc->setNameTag("§l§6» §eNPC §rGoblin §l§6«");
			    	$npc->setNameTagAlwaysVisible(true);
					$npc->setMaxHealth($health);
	            	$npc->setHealth($health);
					$npc->setScale(0.7);
					$npc->getInventory()->setItem(0, $sword);
					$npc->getInventory()->setItem(1, $item);
					$npc->getInventory()->setItem(2, $item2);
					$npc->getInventory()->setItem(3, $item3);
					$npc->getInventory()->setItem(4, $item4);
					$npc->getInventory()->setItem(5, $item5);
			    	$npc->spawnToAll();
					$player->sendMessage("core-spawnboss");
					break;
					case 54:
					$player->getInventory()->addItem(ItemUtils::get("mining_mask(1)"));
					break;
					case 55:
					$player->getInventory()->addItem(ItemUtils::get("netherite_ingot")->setCount(\kenygamer\Core\Main::mt_rand(1, 3)));
					break;
			 	}
			}
		}
	}
	
	/**
	 * Handler for redeemables, such as EXP, money, rank notes and enchant books.
	 *
     * @param PlayerInteractEvent $event
     * @ignoreCancelled false
     */
	public function onItemRedeem(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$item = $player->getInventory()->getItemInHand();
		$nbt = $item->getNamedTag();
		if($nbt->getByte("IsValidToken", false) == true){
			$tokens = $nbt->getInt("NoteValue");
			Main::getInstance()->addTokens($player, $tokens);
			$item->setCount($item->getCount() - 1);
			$player->getInventory()->setItemInHand($item);
			$event->setCancelled();
			return;
		}
		if($nbt->getByte("IsValidNote", false) == true){
			$money = $nbt->getInt("NoteValue");
			Main::getInstance()->addMoney($player, $money);
			$item->setCount($item->getCount() - 1);
			$player->getInventory()->setItemInHand($item);
			$player->sendMessage("money-bonus", $money);
			$event->setCancelled();
			return;
		}
		if($nbt->getByte("IsValidBottle", false) == true){
			$exp = $nbt->getInt("EXPValue");
			$player->addXp($exp);
			$item->setCount($item->getCount() - 1);
			$player->getInventory()->setItemInHand($item);
			$player->sendMessage("exp-bonus", $exp);
			$event->setCancelled();
			return;
		}
		
		$damage = $item->getDamage();
		
		//Port old mythic notes to new mechanism
		if($item->getId() === Item::PAPER){ 
		    if(!$item->getNamedTag()->hasTag("IsValidNote")){
		    	$note = ItemUtils::get("mythic_note(" . $item->getDamage() . ")");
		    	if(!$note->isNull()){
		    		$player->getInventory()->setItemInHand($note);
		    	}
		    }
		//Port old EXP bottles to new mechanism
		}elseif($item->getId() === Item::EXPERIENCE_BOTTLE){
			if(!$item->getNamedTag()->hasTag("IsValidBottle")){
		    	$bottle = ItemUtils::get("experience_bottle2(" . $item->getDamage() . ")");
		    	if(!$bottle->isNull()){
		    		$player->getInventory()->setItemInHand($bottle);
		    	}
		    }
		}
		
		//Rank upgrades
		/** @var array<string: rank<array<int: damage, int: cost>> */
		$ranks = [
		   "shard" => [200, 200000],
		   "harpy" => [201, 300000],
		   "fury" => [202, 400000],
		   "knight" => [203, 500000]
		];
		
		if($item->getId() === Item::PAPER){
			foreach($ranks as $rank => $data){
				$rankName = ucfirst($rank);
				list($dmg, $cost) = $data;
				if($damage === $dmg){
					if(!$player->hasPermission("core.paper." . $rank)){
						$player->sendMessage("core-redeemnote-error");
					}elseif(($expNeeded = $player->getCurrentTotalXp() - $cost) <= 0){
						$player->sendMessage("exp-needed", abs($expNeeded));
					}else{
						$player->subtractXp($cost);
						$player->sendMessage("core-redeemnote", $rankName);
						Main::getInstance()->permissionManager->setPlayerGroup($player, $rankName);
						$player->getInventory()->removeItem(ItemFactory::get($item->getId(), $item->getDamage(), 1));
						$event->setCancelled();
					}
				}
			}
		}
		
		//Book examine
        if ($item->getId() === Item::BOOK){
        	switch($item->getDamage()){
        		case 100: //Common
        		   $event->setCancelled();
        		   $book = $this->getRandomBook("Common");
        		   $cost = 2500000;
        		   break;
        		case 101: //Uncommon
        		   $event->setCancelled();
        		   $book = $this->getRandomBook("Uncommon");
        		   $cost = 12500000;
        		   break;
        		case 102: //Rare
        		   $event->setCancelled();
        		   $book = $this->getRandomBook("Rare");
        		   $cost = 25000000;
        		   break;
        		case 103: //Mythic
        		   $event->setCancelled();
        		    $book = $this->getRandomBook("Mythic");
        		   $cost = 50000000;
        		   break;
        		default:
        		   return;
        	}
        	if($player->getInventory()->canAddItem($book)){
        		if(false/*!Main::getInstance()->reduceMoney($player, $cost*/){
        			$player->sendMessage("money-needed", bcadd($cost, Main::getInstance()->myMoney($player)));
        		}elseif(\kenygamer\Core\Main::mt_rand(0, 99) <= $book->getNamedTag()->getInt(ItemUtils::BOOK_CHANCE_TAG, 100)){
        			$event->setCancelled();
        			$player->getInventory()->addItem($book);
        			$player->getInventory()->removeItem(ItemFactory::get($item->getId(), $item->getDamage(), 1));
        			$player->sendMessage("core-randomenchant");
        			
        			$random = new Random();
        			$yaw = $random->nextBoundedInt(360);
        			$pitch = -1 * (float)(90 + ($random->nextFloat() * 5 - 5 / 2));
        			$nbt = Entity::createBaseNBT($player->asVector3()->add(0.5, 0, 0.5), null, $yaw, $pitch);
        			$nbt->setByte("Flight", 1);
        			$entity = new FireworksEntity($player->getLevel(), $nbt);
        			
        			$player->getLevel()->addEntity($entity);
        			if($entity instanceof Entity){
        				$entity->spawnToAll();
        			}
        		}
        	}else{
        		$player->sendMessage("inventory-nospace");
        	}
        }
    }
}