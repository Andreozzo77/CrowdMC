<?php

namespace CustomEnchants;

use CustomEnchants\CustomEnchants\CustomEnchants;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Entities\MagicFireball;
use CustomEnchants\Entities\PiggyFireball;
use CustomEnchants\Entities\PiggyWitherSkull;
use CustomEnchants\Entities\PigProjectile;
use CustomEnchants\Tasks\TornadoTask;
use CustomEnchants\Tasks\CobwebTask;
use CustomEnchants\Tasks\GoeyTask;
use CustomEnchants\Tasks\GrapplingTask;
use CustomEnchants\Tasks\GuardianTask;
use CustomEnchants\Tasks\HallucinationTask;
use CustomEnchants\Tasks\ImplantsTask;
use CustomEnchants\Tasks\MoltenTask;
use CustomEnchants\Tasks\PlaceTask;
use CustomEnchants\Tasks\AntiGravityTask;
use CustomEnchants\Tasks\RocketTask;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\block\Block;
use pocketmine\block\Crops;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Entity;
use pocketmine\entity\Living;
use pocketmine\entity\projectile\Arrow;
use pocketmine\entity\projectile\Projectile;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\entity\EntityArmorChangeEvent;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\entity\ProjectileHitBlockEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\Event;
use pocketmine\inventory\InventoryHolder;
use pocketmine\event\inventory\InventoryPickupItemEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\cheat\PlayerIllegalMoveEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\inventory\transaction\action\SlotChangeAction;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\Sword;
use pocketmine\item\Tool;
use pocketmine\level\Explosion;
use pocketmine\level\particle\FlameParticle;
use pocketmine\level\particle\HappyVillagerParticle;
use pocketmine\level\particle\DestroyBlockParticle;
use pocketmine\level\particle\LavaDripParticle;
use pocketmine\level\sound\BlazeShootSound;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\Player;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\level\particle\HugeExplodeSeedParticle;
use pocketmine\level\particle\HugeExplodeParticle;

use LegacyCore\Events\Area;
use LegacyCore\Events\PrisonMiner;
use CustomEnchants\Tasks\EndermanTask;
use kenygamer\Core\Main as CoreMain;
use kenygamer\Core\koth\KothTask;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;

/**
 * @class CustomListener
 * @package CustomEnchants
 */
final class CustomListener implements Listener{
	public const HOLY_TAG = "Holy";
	
    public const ORE_TIER = [
        Block::COAL_ORE => 1,
        Block::IRON_ORE => 2,
        Block::GOLD_ORE => 3,
        Block::DIAMOND_ORE => 4,
        Block::EMERALD_ORE => 5
    ];

    public const SMELTED_ITEM = [
        Item::COBBLESTONE => [Item::STONE, 0],
        Item::IRON_ORE => [Item::IRON_INGOT, 0],
        Item::GOLD_ORE => [Item::GOLD_INGOT, 0],
        Item::SAND => [Item::GLASS, 0],
        Item::CLAY => [Item::BRICK, 0],
        Item::NETHERRACK => [Item::NETHER_BRICK, 0],
        Item::STONE_BRICK => [Item::STONE_BRICK, 2],
        Item::CACTUS => [Item::DYE, 2],
        Item::WOOD => [Item::COAL, 1],
        Item::WOOD2 => [Item::COAL, 1],
    ];
    
    private const VANILLA_ENCHANTS_ITEMS = [
       Enchantment::PROTECTION => "Equipment",
       Enchantment::FEATHER_FALLING => "Boots",
       Enchantment::RESPIRATION => "Helmets",
       Enchantment::DEPTH_STRIDER => "Boots",
       Enchantment::SHARPNESS => "Weapons",
       Enchantment::FIRE_ASPECT => "Weapons",
       Enchantment::EFFICIENCY => "Tools",
       Enchantment::SILK_TOUCH => "Tools",
       Enchantment::UNBREAKING => "Equipment",
       Enchantment::FORTUNE => "Tools",
       Enchantment::POWER => "Bows",
       Enchantment::PUNCH => "Bows"
    ];

    private $plugin;
    
    /** @var array string: damager => [string: entity, float: extraDamage, float: microtime] */
    private $extraDamage = [];
    /** @var array string: player => int: time */
    public $invencible = [];
    /** @var array string: player => int: time */
    public $hex = [];
    /** @var array */
    private $martyrdom = [];
    
    private static $instance = null;
    
    public static function getInstance() : ?self{
    	return self::$instance;
    }
    
    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
        self::$instance = $this;
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
			$listener = self::getInstance();
			foreach($listener->hex as $player => $time){
				if(time() >= $time){
					unset($listener->hex[$player]);
				}
			}
			foreach($listener->invencible as $player => $time){
				if(time() >= $time){
					unset($listener->invencible[$player]);
				}
			}
		}), 20);
    }
    
    /**
     * @param InventoryPickupItemEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onInventoryPickupItem(InventoryPickupItemEvent $event) : void{
    	$item = $event->getItem()->getItem();
    	$inventory = $event->getInventory();
    	$player = $inventory->getHolder();
    	if($player instanceof Player && $item->getId() === Item::REDSTONE_DUST && $item->getNamedTag()->hasTag("Bloody")){
    		$bloody = $item->getNamedTag()->getString("Bloody");
    		$target = $this->plugin->getServer()->getPlayerExact($bloody);
    		if($target instanceof Player && $target->distance($player) <= 10 && $target !== $player){
    			$event->getItem()->flagForDespawn();
    			$this->frozen($target);
    			LangManager::send("ce-bloodcurdle-target", $player, $target->getName());
    		}
    		$event->setCancelled();
    	}
    }

    /**
     * @param BlockBreakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onBreak(BlockBreakEvent $event)
    {
        $player = $event->getPlayer();
        $this->checkToolEnchants($player, $event);
    }
	
	/**
     * @param PlayerItemConsumeEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
	public function onConsume(PlayerItemConsumeEvent $event)
	{
		$player = $event->getPlayer();
		$this->checkGlobalEnchants($player, null, $event);
	}

    /**
     * @param EntityArmorChangeEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onArmorChange(EntityArmorChangeEvent $event)
    {
        $entity = $event->getEntity();
        $this->checkArmorEnchants($entity, $event);
    }

    /**
     * @param EntityDamageEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
    public function onDamage(EntityDamageEvent $event)
    {
        $entity = $event->getEntity();
        $cause = $event->getCause();
        if($entity instanceof Player){
        	if($cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK && isset($this->invencible[$entity->getName()])){
        		$event->setCancelled();
        		return;
        	}
        }
        
        if($cause == EntityDamageEvent::CAUSE_FALL && $entity instanceof Player && (isset($this->plugin->nofall[$entity->getName()]) || isset($this->plugin->flying[$entity->getName()]))){
            unset($this->plugin->nofall[$entity->getName()]);
            $event->setCancelled();
        }
        if($event instanceof EntityDamageByChildEntityEvent){
            $damager = $event->getDamager();
            $child = $event->getChild();
            if($damager instanceof Player && $child instanceof Projectile){
                $this->checkGlobalEnchants($damager, $entity, $event);
                $this->checkBowEnchants($damager, $entity, $event);
            }
        }
        if($event instanceof EntityDamageByEntityEvent){
            $damager = $event->getDamager();
            if($damager instanceof Player){
                if($damager->getInventory()->getItemInHand()->getId() !== Item::BOW){
                    $this->checkGlobalEnchants($damager, $entity, $event);
                }
            }
        }
        $this->checkArmorEnchants($entity, $event);
    }

    /**
     * @param EntityEffectAddEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onEffect(EntityEffectAddEvent $event)
    {
		$entity = $event->getEntity();
		$effect = $event->getEffect();
		if($entity instanceof Player){
            $this->checkArmorEnchants($entity, $event);
        }
    }

    /**
     * @param EntityShootBowEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onShoot(EntityShootBowEvent $event)
    {
        $shooter = $event->getEntity();
        $entity = $event->getProjectile();
        if($shooter instanceof Player){
            $this->checkBowEnchants($shooter, $entity, $event);
        }
    }

    /**
     * @param InventoryTransactionEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     *
     * Assumed lower priority in {@link muqsit\invmenu\InvMenuEventHandler\::onInventoryTransaction}
     */
    public function onTransaction(InventoryTransactionEvent $event)
    {
    	$player = $event->getTransaction()->getSource();
    	$transaction = $event->getTransaction();
        $actions = $transaction->getActions();
        $oldToNew = isset(array_keys($actions)[0]) ? $actions[array_keys($actions)[0]] : null;
        $newToOld = isset(array_keys($actions)[1]) ? $actions[array_keys($actions)[1]] : null;
		
        if($oldToNew instanceof SlotChangeAction && $newToOld instanceof SlotChangeAction){
            $itemClicked = $newToOld->getSourceItem();
            $itemClickedWith = $oldToNew->getSourceItem();
            if($itemClickedWith->getId() === Item::ENCHANTED_BOOK && $itemClicked->getId() === Item::ENCHANTED_BOOK){
            	return;
            }
			if($itemClickedWith->getId() === Item::BOOK && $itemClicked->getId() === Item::BOOK && ($type1 = ItemUtils::getEnchantBookType($itemClicked)) !== null && ($type2 = ItemUtils::getEnchantBookType($itemClickedWith))){
				//Forge
				if($type1 !== $type2){
					$player->sendMessage("forge-error");
					return;
				}
				$newToOld->getInventory()->setItem($newToOld->getSlot(), ItemFactory::get(Item::AIR));
				$event->setCancelled();
				$oldToNew->getInventory()->setItem($oldToNew->getSlot(), ItemFactory::get(Item::AIR));			
				$chance1 = $itemClicked->getNamedTag()->getInt(ItemUtils::BOOK_CHANCE_TAG, 0);
				
				$chance2 = $itemClickedWith->getNamedTag()->getInt(ItemUtils::BOOK_CHANCE_TAG, 0);
				
				$chance = min(100, $chance1 + $chance2);
				
				$count = $itemClicked->getCount() + $itemClickedWith->getCount();
				$itemClicked = ItemUtils::get($type1)->setCount($count);
			
				$player->getInventory()->addItem($itemClicked);
				$player->sendMessage("forge-success");
			}elseif($itemClickedWith->getId() === Item::ENCHANTED_BOOK && $itemClicked->getId() !== Item::AIR){
				//Combine book with item
                if(count($enchantments = $itemClickedWith->getEnchantments()) < 1) return;
                $enchantment = $enchantments[array_key_first($enchantments)];
                $success = false;
                if(CustomEnchants::getEnchantmentByName($enchantment->getType()->getName())){
                	if($this->plugin->canBeEnchanted($itemClicked, $enchantment->getType(), $enchantment->getLevel()) === true){
                    	$itemClicked = $this->plugin->addEnchantment($itemClicked, $enchantment->getId(), $enchantment->getLevel(), true, $player);
                    	$success = true;
                    }
                }else{
                	$success = true;
                	if(isset(self::VANILLA_ENCHANTS_ITEMS[$enchantment->getType()->getId()])){
                		$items = self::VANILLA_ENCHANTS_ITEMS[$enchantment->getType()->getId()];
                		switch($items){
                			case "Equipment":
                			    $success = $itemClicked instanceof Armor || $itemClicked instanceof Tool;
                			    break;
                			case "Tools":
                			    $success = $itemClicked instanceof Tool;
                			    break;
                			case "Weapons":
                			    $success = $itemClicked instanceof Sword || $itemClicked instanceof Axe;
                			    break;
                			case "Armor":
                			    $success = $itemClicked instanceof Armor;
                			    break;
                			case "Boots":
                			    $success = $itemClicked instanceof Boots;
                			    break;
                			case "Helmets":
                			    $success = $itemClicked instanceof Helmet;
                			    break;
                			case "Bows":
                			    $success = $itemClicked instanceof Bow;
                			    break;
                		}
                	}
                	if($success){
                		$itemClicked->addEnchantment($enchantment);
                	}
                }
                if($success){
                	$newToOld->getInventory()->setItem($newToOld->getSlot(), $itemClicked);
                    $event->setCancelled();
                    $oldToNew->getInventory()->setItem($oldToNew->getSlot(), Item::get(Item::AIR));
                 }
            }elseif($itemClickedWith->hasEnchantments() && $itemClicked->hasEnchantments()){
				//Combine book with book
            	$event->setCancelled();
            	foreach($itemClickedWith->getEnchantments() as $enchantment){
            	    if(!($enchantment->getType() instanceof CustomEnchants)){
            	        continue;
            	    }
            		if(!($this->plugin->canBeEnchanted($itemClicked, $enchantment->getType(), $enchantment->getLevel()) === true)){
                		continue;
                	}
                	if(($enchant = $itemClicked->getEnchantment($enchantment->getType()->getId())) !== null){
                		if($enchant->getLevel() < $enchantment->getLevel()){
                			$itemClicked = $this->plugin->removeEnchantment($itemClicked, $enchant->getType(), $enchant->getLevel());
                			$itemClicked = $this->plugin->addEnchantment($itemClicked, $enchantment->getType()->getId(), $enchantment->getLevel());
                			
                			$itemClickedWith = $this->plugin->removeEnchantment($itemClickedWith, $enchantment->getType(), $enchantment->getLevel());
                			$itemClickedWith = $this->plugin->addEnchantment($itemClickedWith, $enchant->getType()->getId(), $enchant->getLevel());
                		}elseif($enchantment->getLevel() < $enchant->getLevel()){
                		    $itemClicked = $this->plugin->removeEnchantment($itemClicked, $enchantment->getType(), $enchantment->getLevel());
                		    $itemClicked = $this->plugin->addEnchantment($itemClicked, $enchant->getType()->getId(), $enchant->getLevel());
                			
                			$itemClickedWith = $this->plugin->removeEnchantment($itemClickedWith, $enchant->getType(), $enchant->getLevel());
                			$itemClickedWith = $this->plugin->addEnchantment($itemClickedWith, $enchantment->getType()->getId(), $enchantment->getLevel());
                		}
                	}else{
                		$itemClickedWith = $this->plugin->removeEnchantment($itemClickedWith, $enchantment->getType(), $enchantment->getLevel());
                		$itemClicked = $this->plugin->addEnchantment($itemClicked, $enchantment->getType()->getId(), $enchantment->getLevel());
                	}
            	}
            	$newToOld->getInventory()->setItem($newToOld->getSlot(), $itemClicked);
            	$oldToNew->getInventory()->setItem($oldToNew->getSlot(), $itemClickedWith);
            }elseif($itemClicked->getNamedTag()->getInt(ItemUtils::CONSUMABLE_TAG, -1) === ItemUtils::CONSUMABLE_HOLY_SCROLL && $itemClickedWith->getId() !== Item::AIR){
				//Holy Scroll
				$nbt = $itemClickedWith->getNamedTag();
				$nbt->setInt(self::HOLY_TAG, 1);
				$itemClickedWith->setNamedTag($nbt);
				$itemClicked->setCount($itemClicked->getCount() - 1);
				$player->getInventory()->setItem($newToOld->getSlot(), $itemClicked);
				$player->getInventory()->setItem($oldToNew->getSlot(), $itemClickedWith);
				$player->sendMessage("consumable-apply");
				$event->setCancelled();
			}elseif($itemClicked->getNamedTag()->getInt(ItemUtils::CONSUMABLE_TAG, -1) === ItemUtils::CONSUMABLE_ENCHANT_DUST && ItemUtils::isEnchantBook($itemClickedWith)){
				//Enchant Dust
				$nbt = $itemClickedWith->getNamedTag();
				
				$chance = $nbt->getInt(ItemUtils::BOOK_CHANCE_TAG, 0);
				
				$value = $chance + mt_rand(0, 100 - $chance);
				$type = ItemUtils::getEnchantBookType($itemClickedWith);
				$itemClickedWith = ItemUtils::get($type . "(" . $chance . ")")->setCount($itemClicked->getCount() - 1);
				$itemClicked->setCount($itemClicked->getCount() - 1);
				$player->getInventory()->setItem($newToOld->getSlot(), $itemClicked);
				$player->getInventory()->setItem($oldToNew->getSlot(), $itemClickedWith);
				$player->sendMessage("consumable-apply");
				$event->setCancelled();
			}elseif($itemClicked->getNamedTag()->getInt(ItemUtils::CONSUMABLE_TAG, -1) === ItemUtils::CONSUMABLE_WHITE_SCROLL && ItemUtils::isEnchantBook($itemClickedWith)){
				//White Scroll
				$type = ItemUtils::getEnchantBookType($itemClickedWith);
				$itemClickedWith = ItemUtils::get($type . "(100)")->setCount($itemClicked->getCount() - 1);
				$itemClicked->setCount($itemClicked->getCount() - 1);
				$player->getInventory()->setItem($newToOld->getSlot(), $itemClicked);
				$player->getInventory()->setItem($oldToNew->getSlot(), $itemClickedWith);
				$player->sendMessage("consumable-apply");
				$event->setCancelled();
			}
        }
    }
    
    /**
     * @param EntityDeathEvent $event
     * @priority HIGHEST
     */
    public function onEntityDeath(EntityDeathEvent $event) : void{
    	$entity = $event->getEntity();
    	$drops = $event->getDrops();
    	if(!($entity instanceof Player) && ($cause = $entity->getLastDamageCause()) instanceof EntityDamageByEntityEvent && ($damager = $cause->getDamager()) instanceof Player){
    		$item = $damager->getInventory()->getItemInHand();
    		if($enchantment = $item->getEnchantment(CustomEnchantsIds::VACUUM)){
    			if($entity->distance($damager) <= $enchantment->getLevel() * 1.25){
    				foreach($drops as $i => $drop){
    					if($damager->getInventory()->canAddItem($drop)){
    						$damager->getInventory()->addItem($drop);
    						unset($drops[$i]);
    					}else{
    						break;
    					}
    				}
    				$event->setDrops($drops);
    			}
    		}
    	}
    }

    /**
     * @param PlayerDeathEvent $event
     * @priority HIGH
     */
    public function onDeath(PlayerDeathEvent $event)
    {
        $player = $event->getEntity();
        $this->checkGlobalEnchants($player, null, $event);
        
        if($player->getLastDamageCause() instanceof EntityDamageEvent && (($cause = $player->getLastDamageCause()->getCause()) === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION || $cause === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION)){
        	foreach($this->martyrdom as $damager => $data){
        		list($microtime, $pos, $affectionRadius) = $data;
        		if(microtime(true) - $microtime <= 1){//idk
        		    if($player->distance($pos) <= $affectionRadius){
								
						CoreMain::getInstance()->registerEntry($damager, CoreMain::ENTRY_KILLS);
						CoreMain::getInstance()->registerEntry($player, CoreMain::ENTRY_DEATHS);
					}else{
						unset($this->martyrdom[$damager]);
					}
				}
			}
		}
    }

    /**
     * Disable movement being reverted when flying with a Jetpack
     *
     * @param PlayerIllegalMoveEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onIllegalMove(PlayerIllegalMoveEvent $event)
    {
        $player = $event->getPlayer();
        if(isset($this->plugin->flying[$player->getName()]) || $player->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::SPIDER) !== null){
            $event->setCancelled();
        }
    }

    /**
     * @param PlayerInteractEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onInteract(PlayerInteractEvent $event)
    {
        $player = $event->getPlayer();
        $this->checkToolEnchants($player, $event);
    }

    /**
     * Disable kicking for flying when using jetpacks
     *
     * @param PlayerKickEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onKick(PlayerKickEvent $event)
    {
        $player = $event->getPlayer();
        $reason = $event->getReason();
        if($reason == "Flying is not enabled on this server"){
            if(isset($this->plugin->flying[$player->getName()]) || isset($this->plugin->freeze[$player->getName()]) || $player->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::SPIDER) !== null){
                $event->setCancelled();
            }
        }
    }

    /**
     * @param PlayerMoveEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     * @return bool
     */
    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
        $from = $event->getFrom();
        if(isset($this->plugin->nofall[$player->getName()])){
            if($this->plugin->checkBlocks($player, 0, 1) !== true && $this->plugin->nofall[$player->getName()] < time()){
                unset($this->plugin->nofall[$player->getName()]);
            } else {
                $this->plugin->nofall[$player->getName()]++;
            }
		}
        /*if($from->getFloorX() == $player->getFloorX() && $from->getFloorY() == $player->getFloorY() && $from->getFloorZ() == $player->getFloorZ()){
            $this->plugin->moved[$player->getName()] = 10;
            return false;
        }*/ //???
        $this->plugin->moved[$player->getName()] = 0;
        
        $this->checkGlobalEnchants($player, null, $event);
        $this->checkArmorEnchants($player, $event);
        
        return true;
    }

    /**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event)
    {
        $player = $event->getPlayer();
        $name = $player->getName();
		if(isset($this->plugin->applesick[$name])){
            unset($this->plugin->applesick[$name]);
		}
        if(isset($this->plugin->blockface[$name])){
            unset($this->plugin->blockface[$name]);
		}
		if(isset($this->plugin->cobweb[$name])){
            unset($this->plugin->cobweb[$name]);
        }
		if(isset($this->plugin->flamecircle[$name])){
            unset($this->plugin->flamecircle[$name]);
        }
        if(isset($this->plugin->glowing[$name])){
            unset($this->plugin->glowing[$name]);
        }
        if(isset($this->plugin->grew[$name])){
            unset($this->plugin->grew[$name]);
        }
        if(isset($this->plugin->flying[$name])){
            unset($this->plugin->flying[$name]);
        }
		if(isset($this->plugin->freeze[$name])){
            unset($this->plugin->freeze[$name]);
        }
		if(isset($this->plugin->bleeding[$name])){
            unset($this->plugin->bleeding[$name]);
        }
        if(isset($this->plugin->hallucination[$name])){
            unset($this->plugin->hallucination[$name]);
        }
        if(isset($this->plugin->implants[$name])){
            unset($this->plugin->implants[$name]);
        }
        if(isset($this->plugin->mined[$name])){
            unset($this->plugin->mined[$name]);
        }
        if(isset($this->plugin->nofall[$name])){
            unset($this->plugin->nofall[$name]);
        }
		for ($i = 0; $i <= 3; $i++){
            if(isset($this->plugin->overload[$name . "||" . $i])){
                unset($this->plugin->overload[$name . "||" . $i]);
            }
        }
        if(isset($this->plugin->shrunk[$name])){
            unset($this->plugin->shrunk[$name]);
        }
    }

    /**
     * @param PlayerToggleSneakEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onSneak(PlayerToggleSneakEvent $event)
    {
        $player = $event->getPlayer();
        if($event->isSneaking()){
            $this->checkArmorEnchants($player, $event);
        }
    }

    /**
     * @param ProjectileHitBlockEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onHit(ProjectileHitBlockEvent $event)
    {
        $entity = $event->getEntity();
        $shooter = $entity->getOwningEntity();
        if($shooter instanceof Player){
            $this->checkBowEnchants($shooter, $entity, $event);
        }
    }

    /**
     * @param DataPacketReceiveEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onDataPacketReceive(DataPacketReceiveEvent $event)
    {
        $player = $event->getPlayer();
        $packet = $event->getPacket();
        if($packet instanceof PlayerActionPacket){
            $action = $packet->action;
            switch ($action){
                case PlayerActionPacket::ACTION_JUMP:
                    $this->checkArmorEnchants($player, $event);
                    break;
                case PlayerActionPacket::ACTION_CONTINUE_BREAK:
                    $this->plugin->blockface[$player->getName()] = $packet->face;
                    break;
            }
        }
        if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY && $packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_ATTACK){
        	$target = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
        	if($target instanceof Player){
        		$enchantment = $player->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::ACCURACY);
        		if($enchantment === null){
        			return;
        		}
        		
        		$opposite = ($player->getYaw() + 180) % 360;
        		$opposite2 = ($target->getYaw() + 180) % 360;
        		$diff = abs(180 - ($opposite - $opposite2));
        		
        		$maxDamage = $enchantment->getLevel() * 2;
        		$awardPercent = 100 - ($diff / 360 * 100);
        		$extraDamage = round($maxDamage * $awardPercent / 100, 2);
        			
        		$this->extraDamage[$player->getName()] = [$target->getName(), $extraDamage, microtime(true)];
        	}
        }
    }
    
    /**
     * @param Player $player
     * @return bool
     */
    private function canProcess(Player $player) : bool{
    	return (($arena = CoreMain::getInstance()->getPlayerDuel($player)) !== null ? ($arena->getDuelType() !== CoreMain::DUEL_TYPE_FRIENDLY) : true) && !KothTask::getInstance()->isPlaying($player);
    }
    
    /**
     * @param Player $player
     * @param array $blocks
     */
    private function blockBreak(Player $player, array $blocks) : void{
    	$level = $player->getLevel();
    	$item = $player->getInventory()->getItemInHand();
    	
    	$blockCount = 0;
    	/** @var int[] */
    	$blockIds = [
    	    1, 15, 14, 16, 57, 133, 41, 42, 22, 152, 82, 21, 73, 74, 75, 76, 153, 56, 87, 2, 3, 129
    	];
    	
    	foreach($blocks as $block){
    		if($block->getId() === Block::CHEST){
    			continue;
    		}
    		$level->setBlockIdAt($block->x, $block->y, $block->z, 0);
    		
    		if(in_array($block->getId(), $blockIds)){
    			$blockCount++;
    		}
    	}
    	$items = [];
    	$enchantment = $item->getEnchantment(CustomEnchantsIds::KEYPLUS);
    	if($enchantment !== null){
    		$chance = 3 * $enchantment->getLevel();
    		for($i = 0; $i < $blockCount; $i++){
    			if(\kenygamer\Core\Main::mt_rand(0, 15000) <= $chance){
    				$tier = ItemUtils::get("common_key");
    				$items[] = $tier;
    			}
    			if(\kenygamer\Core\Main::mt_rand(0, 20000) <= $chance){
    				$tier = ItemUtils::get("rare_key");
    				$items[] = $tier;
				}
				if(\kenygamer\Core\Main::mt_rand(0, 25000) <= $chance){
					$tier = ItemUtils::get("ultra_key");
					$items[] = $tier;
				}
				if(\kenygamer\Core\Main::mt_rand(0, 30000) <= $chance){
					$tier = ItemUtils::get("mythic_key");
					$items[] = $tier;
				}
				if(\kenygamer\Core\Main::mt_rand(0, 50000) <= $chance){
					$tier = ItemUtils::get("legendary_key");
					$items[] = $tier;
				}
			}
		}
		$enchantment = $item->getEnchantment(CustomEnchantsIds::TOKENMASTER);
		if($enchantment !== null){
			$tokens = 0;
			$chance = 50 * $enchantment->getLevel();
			$fortuneLvl = ($enchantment = $item->getEnchantment(CustomEnchantsIds::KENYSFORTUNE)) !== null ? $enchantment->getLevel() : 0;
			for($i = 0; $i < $blockCount; $i++){
				if(\kenygamer\Core\Main::mt_rand(0, 500000) <= $chance){
					$extra = intval(\kenygamer\Core\Main::mt_rand(0, 1) ? floor($fortuneLvl) : ceil($fortuneLvl));
					$tokens += 1 + \kenygamer\Core\Main::mt_rand(0, $extra);
				}
			}
			if($tokens > 0){
				CoreMain::getInstance()->addTokens($player, $tokens);
				CoreMain::getInstance()->questManager->getQuest("token_collecter")->progress($player, $tokens);
			}
		}
		$enchantment = $item->getEnchantment(CustomEnchantsIds::TREASUREHUNTER);
		if($enchantment !== null){
			$chance = 5 * $enchantment->getLevel();
			for($i = 0; $i < $blockCount; $i++){
				if(\kenygamer\Core\Main::mt_rand(0, 8000) <= $chance){
					$tier = ItemUtils::get("experience_bottle2(101)");
					$items[] = $tier;
				}
				if(\kenygamer\Core\Main::mt_rand(0, 8000) <= $chance){
					$tier = ItemUtils::get("mythic_note(51");
					$items[] = $tier;
				}
                if(\kenygamer\Core\Main::mt_rand(0, 80000) <= $chance){
					$tier = ItemUtils::get("lucky_block");
				    $items[] = $tier;
				}
				if(\kenygamer\Core\Main::mt_rand(0, 200000) <= $chance){
					$tier = ItemUtils::get("atlas_crate");
                    $items[] = $tier;
				}
				if(\kenygamer\Core\Main::mt_rand(0, 350000) <= $chance){
					$tier = ItemUtils::get("hestia_crate");
                    $items[] = $tier;
			    }
			    if(\kenygamer\Core\Main::mt_rand(0, 400000) <= $chance){
			    	$tier = CoreMain::getInstance()->getRandomTag();
			    	$items[] = $tier;
			    }
			    if(\kenygamer\Core\Main::mt_rand(0, 3000000) <= $chance){
			    	$tier = ItemUtils::get("lord_knight_egg");
			    	$items[] = $tier;
			    }
			    if(\kenygamer\Core\Main::mt_rand(0, 30000000) <= $chance){
			    	$tier = ItemUtils::get("dragon_mask");
			    	$items[] = $tier;
			   }
			}
		}
		if(!empty($items)){
			$player->getLevel()->addParticle(new HappyVillagerParticle($player->asVector3()), [$player]);
		}
		$enchantment = $item->getEnchantment(CustomEnchantsIds::MONEYFARM);
		if($enchantment !== null){
			$moneyLvl = 2.5 * $enchantment->getLevel();
			$money = 0;
			for($i = 0; $i < $blockCount; $i++){
				if(\kenygamer\Core\Main::mt_rand(0, 1)){
					$money += $moneyLvl; //Up to 50 $ with level XX
            	}
            }
            if($money > 0){
            	$money = \kenygamer\Core\Main::mt_rand(max(0, (int) round($money / 2)), max(1, (int) round($money)));
            	CoreMain::getInstance()->addMoney($player, $money);
            }
        }
        $enchantment = $item->getEnchantment(CustomEnchantsIds::GRIND);
        if($enchantment !== null){
        	$enchantLvl = $enchantment->getLevel();
        	$expLvl = floor($enchantLvl / 2);
        	$exp = 0;
        	for($i = 0; $i < $blockCount; $i++){
        		if(\kenygamer\Core\Main::mt_rand(0, 1)){
        			$exp += $expLvl;
        			if($expLvl < 1){
        				if(\kenygamer\Core\Main::mt_rand(1, 3) <= $enchantLvl){
        					$exp += 1;
                    	}
                    }
                }
            }
            $player->addXp($exp);
        }
        foreach($items as $i){
        	if($player->getInventory()->canAddItem($i)){
        		$player->getInventory()->addItem($i);
        	}
        }
    }

    /**
     * @param Player $damager
     * @param Entity $entity
     * @param EntityEvent|Event $event
     */
    public function checkGlobalEnchants(Player $damager, Entity $entity = null, Event $event)
    {
        if($event instanceof EntityDamageEvent){
        	if($event instanceof EntityDamageByEntityEvent){
        	    $item = $damager->getInventory()->getItemInHand();
        		if($entity instanceof Player){
        		
	        		$entityItem = $entity->getInventory()->getItemInHand();
	        		/** @var int[] */
	        		static $playerDamageEnchants = [
	        		    CustomEnchantsIds::INQUISITIVE, CustomEnchantsIds::THIEF, CustomEnchantsIds::HEX,
	        		    CustomEnchantsIds::THIEF, CustomEnchantsIds::HEX, CustomEnchantsIds::FIREASPECT,
	        		    CustomEnchantsIds::BLOODING, CustomEnchantsIds::INVULNERABILITY, CustomEnchantsIds::SAVIOR,
	        		    CustomEnchantsIds::BLOODCURDLE, CustomEnchantsIds::DOUBLESTRIKE,
	        		    CustomEnchantsIds::TRICKSTER, CustomEnchantsIds::ROCKET, CustomEnchantsIds::ANTIGRAVITY,
	        		    CustomEnchantsIds::LIGHTNING, CustomEnchantsIds::CORRUPT, CustomEnchantsIds::WITHER,
	        		    CustomEnchantsIds::GRAVITY, CustomEnchantsIds::BLIND, CustomEnchantsIds::POISON,
	        		    CustomEnchantsIds::ICEASPECT, CustomEnchantsIds::CURSE, CustomEnchantsIds::CRIPPLINGSTRIKE,
	        		    CustomEnchantsIds::DARKROOT, CustomEnchantsIds::TORNADO, CustomEnchantsIds::LIFESTEAL,
	        		    CustomEnchantsIds::VAMPIRE, CustomEnchantsIds::DEATHBRINGER, CustomEnchantsIds::EXCALIBUR,
	        		    CustomEnchantsIds::INSANITY, CustomEnchantsIds::NAUTICA, CustomEnchantsIds::DEMISE,
	        		    CustomEnchantsIds::SHOCKWAVE, CustomEnchantsIds::FREEZE, CustomEnchantsIds::BLEEDING,
	        		    CustomEnchantsIds::HELLFORGED, CustomEnchantsIds::SKILLSWIPE, CustomEnchantsIds::GOOEY,
	        		    CustomEnchantsIds::CHARGE, CustomEnchantsIds::AERIAL, CustomEnchantsIds::BACKSTAB,
	        		    CustomEnchantsIds::CRITICAL, CustomEnchantsIds::OBLITERATE, CustomEnchantsIds::HELLFORGED,
	        		    CustomEnchantsIds::DISARMING, CustomEnchantsIds::DISARMOR, CustomEnchantsIds::SPITSWEB,
	        		    CustomEnchantsIds::HALLUCINATION, CustomEnchantsIds::BLESSED
	        		];
	        		$enchantments = $item->getEnchantments();
	        		usort($enchantments, function($a, $b) use($playerDamageEnchants){
	        		   return array_search($a->getId(), $playerDamageEnchants) < array_search($b->getId(), $playerDamageEnchants) ? -1 : 1;
	        		});
	        		foreach($enchantments as $enchantment){
	        		    if($event->isCancelled()){
	        		        break;
	        		    }
	        			$level = $enchantment->getLevel();
	        			switch($enchantment->getId()){
							case CustomEnchantsIds::DAZE:
								if(mt_rand(0, 50) <= $level){
									$entity->addEffect(new EffectInstance(Effect::getEffect(Effect::NAUSEA), $level * 75, $level - 1));
								}
								break;
							case CustomEnchantsIds::FROST:
								if(mt_rand(0, 50) <= $level){
									$entity->addEffect(new EffectInstance(Effect::getEffect(Effect::SLOWNESS), $level * 50, 0));
								}
								break;
							case CustomEnchantsIds::HADES:
								if(mt_rand(0, 50) <= $level){
									$entity->setOnFire($level * 2.5);
									$entity->setHealth($entity->getHealth() - $level * 0.5);
									$entity->getLevel()->addParticle(new FlameParticle($entity->add((mt_rand(-10,10) /10), (mt_rand(0,20) / 10), (mt_rand(-10,10) / 10))));
								}
								break;
							case CustomEnchantsIds::KABOOM:
								if(mt_rand(0, 50) <= $level){
									$level = 0.8;
									if($level <= 2){
										$level = 0.6;
									}
									$entity->getLevel()->addParticle(new HugeExplodeParticle($entity));
									$entity->getLevel()->broadcastLevelSoundEvent($entity, 48);
									$entity->knockBack($damager, 0, $entity->getX() - $damager->getX(), $entity->getZ() - $damager->getZ(), $level);
									if($entity->getHealth() < 10){
										$entity->setHealth($entity->getHealth() - ($level * 1.33));
									}else{
										$entity->setHealth($entity->getHealth() - ($level * 2));
									}
								}
								break;
							case CustomEnchantsIds::OOF:
								if(mt_rand(0, 49) <= $level && $entity instanceof Player){
									foreach($damager->getLevel()->getNearbyEntities($damager->getBoundingBox()->expandedCopy(16, 16, 16)) as $ent){
										if($ent instanceof Player){
											$pk = new PlaySoundPacket();
											$pk->soundName = "random.hurt";
											$pk->x = $ent->getX();
											$pk->y = $ent->getY();
											$pk->z = $ent->getX();
											$pk->volume = $level * 2;
											$pk->pitch = 1;
											$ent->dataPacket($pk);
										}
									}
								}
								break;
							case CustomEnchantsIds::UPLIFT:
								if(mt_rand(0, 23) <= $level){
									$levels = range(1, 3, 0.2);
									$level = $levels[array_rand($levels)];
									$entity->knockBack($damager, 0, $entity->getX() - $damager->getX(), $entity->getZ() - $damager->getZ(), $level);
								}
								break;
	        				case CustomEnchantsIds::INQUISITIVE:
	        				    if($event->getFinalDamage() >= $entity->getHealth() && $entity instanceof Living && !($entity instanceof Player)){
	        				        if(method_exists($entity, "updateXpDropAmount")){
	        				        	$entity->updateXpDropAmount();
	        				        }
	        				        $damager->addXp(($entity->getXpDropAmount() * 50) * $level);
	        				    }
	        				    break;
	        				case CustomEnchantsIds::THIEF:
	        				    $antitheft = $entityItem->getEnchantment(CustomEnchantsIds::ANTITHEFT);
	        				    $minus = $antitheft !== null ? $antitheft->getLevel() * 25 : 0;
	        				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= ($level * 0.3) - $minus && $this->canProcess($entity)){
	        				        $items = $entity->getInventory()->getContents(false);
	        				        if(!empty($items)){
	        				            $item = $items[array_rand($items)];
	        				            if($item->getCount() > 1 && spl_object_hash($item) !== spl_object_hash($entityItem)){
	        				                $item->setCount(\kenygamer\Core\Main::mt_rand(1, $item->getCount()));
	        				            }
	        				            if($damager->getInventory()->canAddItem($item)){
	        				            	$entity->getInventory()->removeItem($item);
	        				            	$damager->getInventory()->addItem($item);
	        				            }
	        				        }
	        				    }
	        				    break;
	        				case CustomEnchantsIds::FIREASPECT:
	        				    $entity->setOnFire($level * 4);
	        				    break;
	        				case CustomEnchantsIds::BLOODING:
	        				    for($i = 0; $i < $level * 3; $i++){
	        				    	$x = $entity->getX() + floatval("0." . \kenygamer\Core\Main::mt_rand(1, 5));
	        						$y = $entity->getY() + 1 + floatval("0." . \kenygamer\Core\Main::mt_rand(1, 5));
	        						$z = $entity->getZ() + floatval("0." . \kenygamer\Core\Main::mt_rand(1, 5));
	        						$entity->getLevel()->addParticle(new LavaDripParticle(new Vector3(\kenygamer\Core\Main::mt_rand(0, 1) ? $x : -$x, $y, \kenygamer\Core\Main::mt_rand(0, 1) ? $z : -$z)));
	        					}
	        				    break;
	        				case CustomEnchantsIds::INVULNERABILITY:
	        				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= 5){
	        				    	$sec = 3 + ($level - 1);
	        				    	$this->invencible[$damager->getName()] = time() + $sec;
	        				    	LangManager::send("ce-invulnerability-damager", $damager, $sec);
	        				    	LangManager::send("ce-invulnerability-entity", $entity, $damager->getName(), $sec);
	        				    	$event->setCancelled();
	        				    }
	        				    break;
	        				case CustomEnchantsIds::SAVIOR:
	        				    if(\kenygamer\Core\Main::mt_rand(1, 999) <= 5){
	        				    	$distance = $level * 5;
	        				    	if(\kenygamer\Core\Main::mt_rand(0, 1)){
	        				    		$entity->teleport($entity->getLevel()->getSafeSpawn($entity->add(\kenygamer\Core\Main::mt_rand(0, 1) ? $distance : -$distance, 0, 0)));
	        				    	}else{
	        				    		$entity->teleport($entity->getLevel()->getSafeSpawn($entity->add(0, 0, \kenygamer\Core\Main::mt_rand(0, 1) ? $distance : -$distance)));
	        				    	}
	        				    	LangManager::send("ce-savior-entity", $entity, $damager->getName());
	        				    	LangManager::send("ce-savior-damager", $damager, $entity->getName());
	        				    	$event->setCancelled();
	        				    }
	        				    break;
	        				case CustomEnchantsIds::BLOODCURDLE:
	        				    $item = $damager->getInventory()->getItemInHand();
	        				    if(\kenygamer\Core\Main::mt_rand(1, 999) <= $level * 5){
	        				    	$blood = ItemFactory::get(Item::REDSTONE_DUST, 0, 1);
	        				    	$nbt = $blood->getNamedTag();
            		    			$nbt->setString("Bloody", $entity->getName());
            		    			$blood->setNamedTag($nbt);
            		    			$entity->getLevel()->dropItem(new Vector3(($entity->x + $damager->x) / 2, ($entity->y + $damager->y) / 2, ($entity->z + $damager->z) / 2), $blood);
            		    			LangManager::send("ce-bloodcurdle-entity", $entity);
            		    		}
	        				    break;
	        				case CustomEnchantsIds::DOUBLESTRIKE:
	        				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= $level * 2.5){
	        				    	$entity->attack($event);
            						LangManager::send("ce-doublestrike", $damager, $entity->getName());
            					}
	        				    break;
	        				case CustomEnchantsIds::TRICKSTER:
	        				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= $level * 2.5){
	        				    	$yaw = $entity->getYaw();
            						$dx = sin(deg2rad($yaw));
            						$dz = -cos(deg2rad($yaw));
            						$damager->teleport($entity->add($dx, 0, $dz));
            						$damager->pitch = $entity->pitch;
            						$damager->yaw = $entity->yaw;
            						LangManager::send("ce-trickster", $damager);
            					}
            					break;
            				case CustomEnchantsIds::ROCKET:
            				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= $level * 2){
            				    	$blocks = 9 * $level;
            						$this->plugin->getScheduler()->scheduleRepeatingTask(new RocketTask($entity, $blocks), 5);
            						LangManager::send("ce-rocket", $entity);
            					}
            				    break;
            				case CustomEnchantsIds::ANTIGRAVITY:
            				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= $level * 2){
            				    	$blocks = 9 * $level;
            						$this->plugin->getScheduler()->scheduleRepeatingTask(new AntiGravityTask($entity, $blocks), 5);
            						LangManager::send("ce-antigravity", $entity);
            					}
            					break;
            				case CustomEnchantsIds::LIGHTNING:
            				    //20% base + 10% added per extra level (so > 1)
            				    $chance = \kenygamer\Core\Main::mt_rand(1, 99);
            				    if($chance <= (20 + (($level - 1) * 10))){
            						$thunderbolt = new AddActorPacket();
            						$thunderbolt->type = AddActorPacket::LEGACY_ID_MAP_BC[93];
            						$thunderbolt->entityRuntimeId = Entity::$entityCount++;
            						$thunderbolt->metadata = [];
            						$thunderbolt->yaw = $entity->getYaw();
            						$thunderbolt->pitch = $entity->getPitch();
            						$thunderbolt->position = $entity->asVector3();
            						foreach($entity->getLevel()->getNearbyEntities($entity->boundingBox->expandedCopy(16, 16, 16), $entity) as $recipient){
            							if($recipient instanceof Player){
						    				$recipient->dataPacket($thunderbolt);
						    			}
						    		}
						    		$this->plugin->reduceEnchantLevel($item, $enchantment);
						    		$event->getDamager()->getInventory()->setItemInHand($item);
						    	}
            				    break;
            				case CustomEnchantsIds::HEX:
            				    if(\kenygamer\Core\Main::mt_rand(1, 99) <= $level * 3){
            				    	$this->hex[$entity->getName()] = time() + ($sec = $level * 3);
	        						LangManager::send("ce-hex-damager", $damager, $entity->getName());
	        						LangManager::send("ce-hex-entity", $entity, $sec);
	        					}
            				    break;
            				case CustomEnchantsIds::CORRUPT:
            					if(\kenygamer\Core\Main::mt_rand(0, 1000) <= 10 * $level && !isset($this->plugin->nopower[$damager->getName()])){
            						$task = new GuardianTask($this->plugin, $entity);
                            		$this->plugin->getScheduler()->scheduleDelayedTask($task, 1);
                            		foreach([Effect::ABSORPTION, Effect::REGENERATION, Effect::HEALTH_BOOST, Effect::INVISIBILITY] as $effect){
                            			$entity->removeEffect($effect);
                            		}
                            		$effect = new EffectInstance(Effect::getEffect(Effect::FATAL_POISON), 1000 + 20 * $level, 1 + $level, false);
                            		$entity->addEffect($effect);
                            		$effect = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 1000 + 20 * $level, 5 + $level, false);
                            		$entity->addEffect($effect);
                            		$effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 1000 + 20 * $level, 6 + $level, false);
                            		$entity->addEffect($effect);
                            		$effect = new EffectInstance(Effect::getEffect(Effect::HUNGER), 1000 + 20 * $level, 4 + $level, false);
                            		$entity->addEffect($effect);
                            	}
                            	break;
                            case CustomEnchantsIds::WITHER:
                                if(\kenygamer\Core\Main::mt_rand(0, 500) <= 5 * $level){
                                	$effect = new EffectInstance(Effect::getEffect(Effect::WITHER), 100 * $level, $level, false);
                                	$entity->addEffect($effect);
                                }
                                break;
                            case CustomEnchantsIds::GRAVITY:
                                if(\kenygamer\Core\Main::mt_rand(0, 500) <= 5 * $level){
                                	$effect = new EffectInstance(Effect::getEffect(Effect::LEVITATION), 10 * $level, $level, false);
                                	$entity->addEffect($effect);
                                	LangManager::send("ce-gravity", $entity);
                                }
                                break;
                            case CustomEnchantsIds::BLIND:
                                if(\kenygamer\Core\Main::mt_rand(0, 500) <= 10 * $enchantment->getLevel()){
                                	$effect = new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 100 + 20 * $level, 0, false);
                                	$entity->addEffect($effect);
                                }
                                break;
                            case CustomEnchantsIds::ICEASPECT:
                                if(\kenygamer\Core\Main::mt_rand(0, 500) <= 10 * $level){
                                    $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 90 * $level, $level, false);
                                    $entity->addEffect($effect);
                                }
                                break;
                            case CustomEnchantsIds::CURSE:
                                if(\kenygamer\Core\Main::mt_rand(0, 850) <= 7 * $level && !isset($this->plugin->nopower[$damager->getName()])){
                                	$effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 350 * $level, $level, false);
                                	$entity->addEffect($effect);
                                	$effect = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 500 * $level, $level, false);
                                	$entity->addEffect($effect);
                                	$effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 400 * $level, 6 + $level, false);
                                	$entity->addEffect($effect);
                                	$entity->removeEffect(Effect::REGENERATION);
                                	LangManager::send("ce-curse", $entity);
                                }
                                break;
                            case CustomEnchantsIds::CRIPPLINGSTRIKE:
                                if(\kenygamer\Core\Main::mt_rand(0, 750) <= 10 * $level && !isset($this->plugin->nopower[$damager->getName()])){
                                	$effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 40 * $level, 0, false);
                                	$entity->addEffect($effect);
                                	$effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 120 * $level, $level, false);
                                	$entity->addEffect($effect);
                                	$effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 200 * $level, 2 + $level, false);
                                	$entity->addEffect($effect);
                                	LangManager::send("ce-cripplingstrike", $entity);
                                }
                                break;
                            case CustomEnchantsIds::DARKROOT:
                                if(\kenygamer\Core\Main::mt_rand(0, 600) <= 5 * $level && !isset($this->plugin->nopower[$damager->getName()])){
                                	$effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 120 * $level, 0, false);
                                	$entity->addEffect($effect);
                                	$effect = new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 120 * $level, 0, false);
                                	$entity->addEffect($effect);
                                	LangManager::send("ce-darkroot", $entity);
                                }
                                break;
                            case CustomEnchantsIds::TORNADO:
                                if(\kenygamer\Core\Main::mt_rand(0, 99) <= $level * 0.1){
                                	$points = 0;
									foreach($entity->getArmorInventory()->getContents() as $armor){
										if($enchantment = $armor->getEnchantment(CustomEnchantsIds::WHIRL)){
											$points += $enchantment->getLevel() * 4;
										}
									}
									if(\kenygamer\Core\Main::mt_rand(0, 99) <= $points){
										$this->plugin->getScheduler()->scheduleRepeatingTask(new TornadoTask($this->plugin, $entity), 1);
										LangManager::send("ce-tornado-target", $entity);
										LangManager::send("ce-tornado", $damager, $entity->getName());
									}
								}
								break;
							case CustomEnchantsIds::LIFESTEAL:
							    if(!isset($this->plugin->nopower[$damager->getName()]) && (!isset($this->plugin->lifestealcd[$damager->getName()]) || time() > $this->plugin->lifestealcd[$damager->getName()])){
							    	$this->plugin->lifestealcd[$damager->getName()] = time() + 5;
							    	if($damager->getHealth() + 2 + $level <= $damager->getMaxHealth()){
                            			$damager->setHealth($damager->getHealth() + 2 + $level);
                            		}else{
                            			$damager->setHealth($damager->getMaxHealth());
                            		}
                            	}
							    break;
							case CustomEnchantsIds::VAMPIRE:
							    if(!isset($this->plugin->vampirecd[$damager->getName()]) || time() > $this->plugin->vampirecd[$damager->getName()]){
							    	$this->plugin->vampirecd[$damager->getName()] = time() + 5;
                    				if($damager->getHealth() + ($event->getFinalDamage() / 2) <= $damager->getMaxHealth()){
                        				$damager->setHealth($damager->getHealth() + ($event->getFinalDamage() / 2));
                        			}else{
                        				$damager->setHealth($damager->getMaxHealth());
                        			}
                        			if($damager->getFood() + ($event->getFinalDamage() / 2) <= $damager->getMaxFood()){
                        				$food = $damager->getFood() + ($event->getFinalDamage() / 2);
                        				$damager->setFood($food > 0 ? $food : 0);
                        			}else{
                        				$damager->setFood($damager->getMaxFood());
                        			}
                        		}
							    break;
							case CustomEnchantsIds::DEATHBRINGER:
							    $damage = 3 + ($level / 30);
							    $event->setModifier($damage, CustomEnchantsIds::DEATHBRINGER);
							    break;
							case CustomEnchantsIds::EXCALIBUR:
							    $damage = 5 + ($level / 70);
							    $event->setModifier($damage, CustomEnchantsIds::EXCALIBUR);
							    break;
							case CustomEnchantsIds::INSANITY:
							    $damage = 7 + ($level / 100);
							    $event->setModifier($damage, CustomEnchantsIds::INSANITY);
							    break;
							case CustomEnchantsIds::NAUTICA:
							    $damage = 5 + ($level / 100);
							    $event->setModifier($damage, EntityDamageEvent::CAUSE_MAGIC);
							    $entity->getLevel()->addParticle(new DestroyBlockParticle($entity, Block::get(57)));
							    break;
							case CustomEnchantsIds::DEMISE:
							    if(\kenygamer\Core\Main::mt_rand(0, 500) <= 5 * $enchantment->getLevel()){
							    	$damage = 0.5 * $level;
							    	$event->setModifier($damage, EntityDamageEvent::MODIFIER_ARMOR);
							    	LangManager::send("ce-demise", $entity);
							    }
							    break;
							case CustomEnchantsIds::SHOCKWAVE:
							    if(\kenygamer\Core\Main::mt_rand(0, 250) <= 5 * $level){
							    	$radius = 3 * $level;
							    	LangManager::send("ce-shockwave", $damager);
							    	foreach($damager->getLevel()->getEntities() as $target){
							    		if($target !== $damager && $target instanceof Living && $target->distance($damager) <= $radius){
							    			$value = 5 * $level;
							    			$target->attack(new EntityDamageEvent($target, EntityDamageEvent::CAUSE_MAGIC, $value));
							    		}
							    	}
							    }
							    break;
							case CustomEnchantsIds::FREEZE:
							    if(\kenygamer\Core\Main::mt_rand(0, 99) <= 2 * $level){
							    	$cost = 1000 * $level;
							    	if(!($damager->getCurrentTotalXp() - $cost < 0)){
							    		$damager->subtractXp($cost);
							    		$frostbite = $item->getEnchantment(CustomEnchantsIds::FROSTBITE);
							    		$force = $frostbite !== null && \kenygamer\Core\Main::mt_rand(1, 10) <= $frostbite->getLevel();
							    		$this->frozen($entity, $force);
							    		LangManager::send("ce-freeze", $damager, $entity->getName());
							    	}
							    }
							    break;
							case CustomEnchantsIds::BLEEDING:
							    if(\kenygamer\Core\Main::mt_rand(0, 500) <= 5 * $level){
							    	$cost = 1000 * $level;
							    	if(!(($damager->getCurrentTotalXp() - $cost < 0) || $entity->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::ADHESIVE) !== null)){
							    		if(!($damager->getCurrentTotalXp() - $cost < 0)){
				        					$damager->subtractXp($cost);
				        				}
				        				$this->blood($entity);
				        				$entity->removeEffect(Effect::REGENERATION);
				        				LangManager::send("ce-bleeding-entity", $entity);
				        				LangManager::send("ce-bleeding-damager", $damager, $entity->getName());
				        			}
				        		}
				        		break;
				        	case CustomEnchantsIds::HELLFORGED:
				        	    if(\kenygamer\Core\Main::mt_rand(0, 500) <= 10 * $level){
				        	    	$fire = \kenygamer\Core\Main::mt_rand(5, 10 * $level);
				        	    	$entity->setOnFire($fire);
				        	    	$entity->removeEffect(Effect::ABSORPTION);
				        	    	$entity->removeEffect(Effect::INVISIBILITY);
				        	    	$event->setModifier((($event->getFinalDamage() * (1 + 1.00 * $level)) - $event->getFinalDamage()), CustomEnchantsIds::HELLFORGED);
				        	    }
				        	    break;
				        	case CustomEnchantsIds::DRAIN:
				        	    $money = \kenygamer\Core\Main::mt_rand(0, 10000 * $level);
				        	    if(CoreMain::getInstance()->reduceMoney($entity, $money)){
				        	    	CoreMain::getInstance()->addMoney($damager, $money);
				        	    }
				        	    break;
				        	case CustomEnchantsIds::SKILLSWIPE:
				        	    if(!($entity->getCurrentTotalXp() - ($exp = \kenygamer\Core\Main::mt_rand(0, 1000 * $level)) < 0)){
				        	    	$entity->subtractXp($exp);
				        	    	$damager->addXp($exp);
				        	    }
				        	    break;
				        	case CustomEnchantsIds::GOOEY:
				        	    if(\kenygamer\Core\Main::mt_rand(0, 500) <= 5 * $level){
				        	    	$task = new GoeyTask($this->plugin, $entity, $level);
				        	    	$this->plugin->getScheduler()->scheduleDelayedTask($task, 1);
				        	    	LangManager::send("ce-gooey", $entity);
				        	    }
				        	    break;
				        	case CustomEnchantsIds::CHARGE:
				        	    if($damager->isSprinting()){
				        	    	$event->setModifier((($event->getFinalDamage() * (1 + 0.10 * $level)) - $event->getFinalDamage()), CustomEnchantsIds::CHARGE);
				        	    }
				        	    break;
				        	case CustomEnchantsIds::AERIAL:
				        	    if(!$damager->isOnGround()){
				        	    	$event->setModifier((($event->getFinalDamage() * (1 + 0.10 * $level)) - $event->getFinalDamage()), CustomEnchantsIds::AERIAL);
				        	    }
							    break;
							case CustomEnchantsIds::BACKSTAB:
							    if($damager->getDirectionVector()->dot($entity->getDirectionVector()) > 0){
							    	$event->setModifier((($event->getFinalDamage() * (1 + 0.10 * $level)) - $event->getFinalDamage()), CustomEnchantsIds::BACKSTAB);
							    }
							    break;
							case CustomEnchantsIds::CRITICAL:
							    if(\kenygamer\Core\Main::mt_rand(0, 500) <= 10 * $level){
							    	$event->setModifier((($event->getFinalDamage() * (1 + 0.50 * $level)) - $event->getFinalDamage()), CustomEnchantsIds::CRITICAL);
							    	LangManager::send("ce-critical", $entity);
							    }
							    break;
							case CustomEnchantsIds::OBLITERATE:
							    if(\kenygamer\Core\Main::mt_rand(0, 500) <= 10 * $level){
							    	$event->setKnockBack(0.3 * $level);
							    	LangManager::send("ce-obliterate", $entity, $damager->getName());
							    }
							    break;
							case CustomEnchantsIds::DEMONFORGED:
							    if(\kenygamer\Core\Main::mt_rand(0, 300) <= 5 * $level){
							    	foreach($entity->getArmorInventory()->getContents() as $slot => $armor){
							    		if($armor instanceof Armor){
							    			$value = \kenygamer\Core\Main::mt_rand(3, 7 * $level);
                                			$armor->applyDamage($value);
					    	    			$entity->getArmorInventory()->setItem($slot, $armor);
					    	    		}
					    	    	}
					    	    }
					    	    break;
					    	case CustomEnchantsIds::DISARMING:
					    	    if($this->canProcess($entity)){
					    	        if(\kenygamer\Core\Main::mt_rand(0, 1000) <= 5 * $level){
										$process = ($protection = $entityItem->getEnchantment(CustomEnchantsIds::DISARMPROTECTION)) !== null ? \kenygamer\Core\Main::mt_rand(0, 9) > $protection->getLevel() : true;
										if($process){
					    	        		$entity->getInventory()->removeItem($entityItem);
					    	        		$entity->dropItem($entityItem);
					    	        		$entity->addTitle(TextFormat::RED . "Disarming", TextFormat::GOLD . "You were disarmed!", 30, 30, 30);
										}
					    	        }
					    	    }
					    	    break;
					    	case CustomEnchantsIds::DISARMOR:
					    	    if($this->canProcess($entity) && \kenygamer\Core\Main::mt_rand(0, 1000) <= 5 * $level){
					    	    	if(count($armor = $entity->getArmorInventory()->getContents(false)) > 0){
                        				$process = ($protection = $entityItem->getEnchantment(CustomEnchantsIds::DISARMORPROTECTION)) !== null ? \kenygamer\Core\Main::mt_rand(0, 9) > $protection->getLevel() : true;
                        				if($process){
                        					$piece = $armor[array_rand($armor)];
                                			$entity->getArmorInventory()->removeItem($piece);
                                			$entity->dropItem($piece);
                                			$entity->addTitle(TextFormat::RED . "Disarmor", TextFormat::GOLD . "You were disarmored!", 30, 30, 30);
                                		}
                                	}
                                }
                                break;
                            case CustomEnchantsIds::SPITSWEB:
                                if(\kenygamer\Core\Main::mt_rand(0, 750) <= 5 * $level && $entity->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::ANTITRAP) === null){
                                    if(!isset($this->plugin->cobweb[$entity->getName()])){
                                		$this->plugin->cobweb[$entity->getName()] = true;
                                		$task = new CobwebTask($this->plugin, $entity, $entity->getPosition());
                                		$handler = $this->plugin->getScheduler()->scheduleRepeatingTask($task, 1);
                                		$task->setHandler($handler);
				                		$entity->addTitle(LangManager::translate("ce-spitsweb-entity-1", $entity), LangManager::translate("ce-spitsweb-entity-2", $entity), 30, 30, 30);
				                		LangManager::send("ce-spitsweb-damager", $damager);
									}
								}
                                break;
                            case CustomEnchantsIds::HALLUCINATION:
                                if(\kenygamer\Core\Main::mt_rand(0, 750) <= 5 * $level && $entity->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::ANTITRAP) === null){
                                	if(!isset($this->plugin->hallucination[$entity->getName()])){
                                		$this->plugin->hallucination[$entity->getName()] = true;
                                		$task = new HallucinationTask($this->plugin, $entity, $entity->getPosition());
                                		$handler = $this->plugin->getScheduler()->scheduleRepeatingTask($task, 1);
                                		$task->setHandler($handler);
						        		$entity->addTitle(LangManager::translate("ce-hallucination-entity-1", $entity), LangManager::translate("ce-hallucination-entity-2", $entity), 30, 30, 30);
						        		LangManager::send("ce-hallucination-damager", $damager);
						        	}
                                }
                                break;
                            case CustomEnchantsIds::BLESSED:
                                if(\kenygamer\Core\Main::mt_rand(0, 400) <= 15 * $level){
                                    foreach($damager->getEffects() as $effect){
                                    	if($effect->getType()->isBad()){
                            				$damager->removeEffect($effect->getId());
                            			}
                            		}
                            		LangManager::send("ce-blessed", $damager);
                            	}
                            	break;
	        			} //End switch
	        		}
	        		
	        		//Other logic of enchantments
					
					foreach($entity->getArmorInventory()->getContents() as $gear){
						if($gear->hasEnchantment(CustomEnchantsIds::SCORCH)){
							$level = $gear->getEnchantment(CustomEnchantsIds::SCORCH)->getLevel();
							if(mt_rand(0, 50) <= $level){
								$damager->setOnFire($level);
							}
						}
						
						if($gear->hasEnchantment(CustomEnchantsIds::ADRENALINE)){
							$level = $gear->getEnchantment(CustomEnchantsIds::ADRENALINE)->getLevel();
							if($entity->getHealth() <= 6){
								$entity->addEffect(new EffectInstance(Effect::getEffect(Effect::SPEED), 140, 0));
							}
						}
					}
					
	        		if(isset($this->hex[$entity->getName()]) && microtime(true) - ($this->lastHexAttack[$entity->getName()] ?? 0) >= 0.5){
	        			$this->lastHexAttack[$entity->getName()] = microtime(true);
	        			$ev = new EntityDamageByEntityEvent($damager, $entity, EntityDamageEvent::CAUSE_ENTITY_ATTACK, $event->getBaseDamage());
	        			$damager->attack($ev);
	        		    LangManager::send("ce-hex-reflected-damager", $damager);
	        		    LangManager::send("ce-hex-reflected-entity", $entity, $damager->getName());
	        		}
	        		if(isset($this->extraDamage[$damager->getName()])){
	        			list($player, $extraDamage, $microtime) = $this->extraDamage[$damager->getName()];
	        			if(microtime(true) - $microtime <= 0.1){
	        				$damager->addTitle(TextFormat::GOLD . "Accuracy", TextFormat::DARK_AQUA . "+{$extraDamage} damage", 3, 3, 3);
	        				$event->setBaseDamage($event->getBaseDamage() + $extraDamage);
	        				unset($this->extraDamage[$damager->getName()]);
	        			}
	        		}
	        		$size = 0;
	        		foreach($damager->getArmorInventory()->getContents() as $slot => $armor){
	        			$enchantment = $armor->getEnchantment(CustomEnchantsIds::MARTYRDOM);
            			if($enchantment !== null){
            				$size += $enchantment->getLevel();
            			}
            		}
            		if($size > 0 && $event->getFinalDamage() >= $entity->getHealth()){
            			$pos = $damager->asPosition();
            			$this->martyrdom[$damager->getName()] = [microtime(true), $pos, $size * 2];
            			$this->invencible[$damager->getName()] = time() + 1;
            			$explosion = new Explosion($pos, $size);
            			$explosion->explodeB();
            			$event->setCancelled();
            		}
	        		
	        	}elseif($entity instanceof Living){
	        	    static $entityDamageEnchants = [
	        	        CustomEnchantsIds::LOOTING
	        	    ];
	        	    $enchantments = $item->getEnchantments();
	        		usort($enchantments, function($a, $b) use($entityDamageEnchants){
	        		   return array_search($a->getId(), $entityDamageEnchants) < array_search($b->getId(), $entityDamageEnchants) ? -1 : 1;
	        		});
	        		foreach($enchantments as $enchantment){
	        		    if($event->isCancelled()){
	        		        break;
	        		    }
	        			$level = $enchantment->getLevel();
	        			switch($enchantment->getId()){
	        			    case CustomEnchantsIds::LOOTING:
	        			    	if(($count = $entity->namedtag->getTag("EntityCount", IntTag::class)) !== null && $event->getFinalDamage() >= $entity->getHealth()){
	        			        	$count = \kenygamer\Core\Main::mt_rand($count->getValue(), $count->getValue() * (1 + $level));
	        			        	if($count > 0x7fffffff){
	        			            	$count = 0x7fffffff;
	        			        	}
	        			        	$entity->namedtag->setInt("EntityCount", $count);
	        			    	}
	        			    	break;
	        			} //End switch
	        		}
	        	}
	        }
	    }
		if($event instanceof PlayerDeathEvent){
			$entity = $event->getEntity();
			$cause = $event->getEntity()->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent){
                $killer = $event->getEntity()->getLastDamageCause()->getDamager();
                if($killer instanceof Player){
                	$killerItem = $killer->getInventory()->getItemInHand();
					
					$enchantment = $killerItem->getEnchantment(CustomEnchantsIds::KILLCOUNTER);
					if($enchantment !== null){
						$lore = $killerItem->getLore();
						$nbt = $killerItem->getNamedTag();
						$nbt->setString("Kills", $kills = $nbt->getString("Kills", 0) + 1);
						$killerItem->setNamedTag($nbt);
						$tag = TextFormat::colorize("&r&6Kills: &b");
						$which = -1;
						foreach($lore as $i => $line){
							if(strpos($line, $tag) !== false){
								$which = $i;
							}
						}
						if($which > -1){
							$lore[$which] = $tag . $kills;
						}else{
							$lore[] = $tag . $kills;
						}
						$killerItem->setLore($lore);
						$killer->getInventory()->setItemInHand($killerItem);
					}
                	
                	//Headless
                    $enchantment = $killerItem->getEnchantment(CustomEnchantsIds::HEADLESS);
					if($enchantment !== null){
						$head = CoreMain::getInstance()->getPlayerHead($entity->getSkin(), $entity->getName());
						if($killer->getInventory()->canAddItem($head)){
							$killer->getInventory()->addItem($head);
							LangManager::send("ce-headless", $killer, $entity->getName());
						}
				    }
				    
				    //Killer Money
					$enchantment = $killerItem->getEnchantment(CustomEnchantsIds::KILLERMONEY);
					if($enchantment !== null){
                        $money = 5000 * $enchantment->getLevel();
                        CoreMain::getInstance()->addMoney($killer->getName(), $money);
                        LangManager::send("ce-killermoney", $killer, $money);
                    }
                    
                    //Frenzy
                    $enchantment = $killerItem->getEnchantment(CustomEnchantsIds::FRENZY);
                    if($enchantment !== null){
                    	$effect = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 20 * 60, 1, false);
                    	$damager->addEffect($effect);
                    	$effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 20 * 45, 1, false);
                    	$damager->addEffect($effect);
                    }
                }
			}
			
			/** @var Item[] */
            $drops = $event->getDrops();
			foreach($drops as $i => $drop){
				if($drop->getNamedTag()->getInt(self::HOLY_TAG, 0)){
					$nbt = $drop->getNamedTag();
					$nbt->removeTag(self::HOLY_TAG);
					$drop->setNamedTag($nbt);
					$entity->getLevel()->dropItem($entity->asVector3(), $drop);
					unset($drops[$i]);
				}
			}
            $keepInventory = false;
            
            if(!empty($drops)){
            	//Soulbound
            	$getters = ["getInventory", "getArmorInventory"];
            	foreach($getters as $getter){
            		foreach($entity->{$getter}()->getContents(false) as $slot => $item){
            			if($item->hasEnchantment(CustomEnchantsIds::SOULBOUND) && false){
            				$keepInventory = true;
            				
            				foreach($drops as $i => $drop){
            					$soulbound = $item->getEnchantment(CustomEnchantsIds::SOULBOUND);
            					if($soulbound !== null && $drop->equals($item)){
            						$this->plugin->reduceEnchantLevel($item, CustomEnchantsIds::SOULBOUND);
            						$entity->{$getter}()->setItem($slot, $item);
            						unset($drops[$i]);
            						break;
            					}
            				}
            			}else{
            				$entity->{$getter}()->setItem($slot, ItemFactory::get(Item::AIR));
            			}
            		}
            	}
            }
            
            if($cause instanceof EntityDamageByEntityEvent && ($damager = $cause->getDamager()) instanceof Player){
            		
            	$item = $damager->getInventory()->getItemInHand();
            	if(($enchantment = $item->getEnchantment(CustomEnchantsIds::COMPRESS)) !== null){
            		$rod = ItemFactory::get(Item::BLAZE_ROD, 0, 1);
            		$nbt = $rod->getNamedTag();
            		/** @var StringTag[] */
            		$items = [];
            			
            		$dropped = [];
            		foreach($drops as $i => $drop){
            			if($i - 1 >= $damager->getInventory()->getSize()){
            				$dropped[] = $drop;
            				continue;
            			}
            			$enchants = "";
            			if($drop->hasEnchantments()){
            				$enchantments = $drop->getEnchantments();
            				foreach($enchantments as $i => $enchantment){
            					$enchants .= $enchantment->getType()->getId() . ";" . $enchantment->getLevel() . ($i !== count($enchantments) - 1 ? "," : "");
            				}
            			}
            			$items[] = new StringTag((string) $i, ($drop->hasCustomName() ? $drop->getCustomName() : $drop->getVanillaName()) . ":" . $drop->getId() . ":" . $drop->getDamage() . ":" . $drop->getCount() . ($enchants !== "" ? (":" . $enchants) : ""));
            		}
            		$nbt->setTag(new ListTag("ItemList", $items, NBT::TAG_String));
            		$rod->setNamedTag($nbt);
            		$rod->setCustomName(TextFormat::colorize("&eMurdered Road"));
            		$rod->setLore([TextFormat::colorize("&r&7{$entity->getName()}"), TextFormat::colorize("&6Date of death: &b" . date("F dS, Y")), "", TextFormat::colorize("&r&7Click on ground to redeem.")]);
            		$drops = [];
            		$drops = array_merge([$rod], $dropped);
            		    
            	}
            	foreach($drops as $i => $drop){
            		if($damager->getInventory()->canAddItem($drop)){
            			$damager->getInventory()->addItem($drop);
            			unset($drops[$i]);
            		}
            	}
            }
            
            $event->setDrops($drops);
            $event->setKeepInventory($keepInventory);
        }
        
		if($event instanceof PlayerItemConsumeEvent){
			$player = $event->getPlayer();
			$item = $event->getItem();
			$enchantment = $player->getArmorInventory()->getLeggings()->getEnchantment(CustomEnchantsIds::NUTRITION); //Nutrition
			if($enchantment !== null){
	        	if($item->getId() === 350 || $item->getId() === 322 || $item->getId() === 466){ 
                    if(!isset($this->plugin->applesick[$player->getName()]) || $this->plugin->applesick[$player->getName()] < time()){	
                        $timer = 30 - 5 * $enchantment->getLevel();					
                        $this->plugin->applesick[$player->getName()] = time() + $timer;
						if($item->getId() === 322){
                            $damage = $item->getDamage();
                            switch($damage){
                                case 0:
				                $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 30*20, 2, true);
			                    $player->addEffect($effect);
			                    $effect2 = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 120*20, 1, true);
			        	        $player->addEffect($effect2);
							}
						}
			        	if($item->getId() === 466){
                            $damage = $item->getDamage();
                            switch($damage){
                                case 0:
			                    $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 120*20, 4, true);
			        	        $player->addEffect($effect);
							}
						}
		         	    if($item->getId() === 350){
                            $damage = $event->getItem()->getDamage();
                            switch($damage){
                                case 0:
                                $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 300*20, 6, true);
                                $effect2 = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 300*20, 0, true);
                                $effect3 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 300*20, 1, true);
                                $effect4 = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 300*20, 2, true);
                                $effect5 = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 120*20, 4, true);
                                $player->addEffect($effect);
                                $player->addEffect($effect2);
                                $player->addEffect($effect3);
                                $player->addEffect($effect4);
                                $player->addEffect($effect5);
                                $player->addEffect($effect5);
                                break;
                                case 5:
                                $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 360*20, 9, true);
                                $effect2 = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 360*20, 0, true);
                                $effect3 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 360*20, 2, true);
                                $effect4 = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 360*20, 3, true);
                                $effect5 = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 180*20, 4, true);
                                $player->addEffect($effect);
                                $player->addEffect($effect2);
                                $player->addEffect($effect3);
                                $player->addEffect($effect4);
                                $player->addEffect($effect5);
                                break;
			        		}
			        	}
			        } else {
			        	$effect = new EffectInstance(Effect::getEffect(Effect::INSTANT_DAMAGE), 30*20 - 100 * $enchantment->getLevel(), 2, true);
                        $player->addEffect($effect);
                        LangManager::send("ce-nutrition", $player);
					}
				}
			} else {
				if($item->getId() === 350 || $item->getId() === 322 || $item->getId() === 466){ 
                    if(!isset($this->plugin->applesick[$player->getName()]) || $this->plugin->applesick[$player->getName()] < time()){			
                        $this->plugin->applesick[$player->getName()] = time() + 30;
						if($item->getId() === 322){
                            $damage = $item->getDamage();
                            switch($damage){
                                case 0:
				                $effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 30*20, 2, true);
			                    $player->addEffect($effect);
			                    $effect2 = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 120*20, 1, true);
			        	        $player->addEffect($effect2);
							}
						}
			        	if($item->getId() === 466){
                            $damage = $item->getDamage();
                            switch($damage){
                                case 0:
			                    $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 120*20, 4, true);
			        	        $player->addEffect($effect);
							}
						}
		         	    if($item->getId() === 350){
                            $damage = $item->getDamage();
                            switch($damage){
                                case 0:
                                $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 300*20, 6, true);
                                $effect2 = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 300*20, 0, true);
                                $effect3 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 300*20, 1, true);
                                $effect4 = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 300*20, 2, true);
                                $effect5 = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 120*20, 4, true);
                                $player->addEffect($effect);
                                $player->addEffect($effect2);
                                $player->addEffect($effect3);
                                $player->addEffect($effect4);
                                $player->addEffect($effect5);
                                $player->addEffect($effect5);
                                break;
                                case 5:
                                $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 360*20, 9, true);
                                $effect2 = new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 360*20, 0, true);
                                $effect3 = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 360*20, 2, true);
                                $effect4 = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 360*20, 3, true);
                                $effect5 = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 180*20, 4, true);
                                $player->addEffect($effect);
                                $player->addEffect($effect2);
                                $player->addEffect($effect3);
                                $player->addEffect($effect4);
                                $player->addEffect($effect5);
                                break;
			        		}
			        	}
			        } else {
			        	$effect = new EffectInstance(Effect::getEffect(Effect::INSTANT_DAMAGE), 30*20, 2, true);
                        $player->addEffect($effect);
                        LangManager::send("ce-nutrition", $player);
					}
				}
			}
		}
	}

    /**
     * @param Player $player
     * @param Event $event
     */
    public function checkToolEnchants(Player $player, Event $event)
    {
        if($event instanceof BlockBreakEvent){
            $block = $event->getBlock();
            $drops = $event->getDrops();
            $item = $event->getItem();
            
			$enchantment = $item->getEnchantment(CustomEnchantsIds::FEED);
			if($enchantment !== null){
				if($player->getFood() < ($max = $player->getMaxFood())){
					$player->setFood(min($max, $player->getFood() + mt_rand(0, 1)));
				}
			}
			
            $enchantment = $item->getEnchantment(CustomEnchantsIds::EXPLOSIVE);
            if($enchantment !== null){
                if(!isset($this->plugin->using[$player->getName()]) || microtime(true) > $this->plugin->using[$player->getName()]){
                	$this->plugin->using[$player->getName()] = PHP_INT_MAX;
                	$event->setCancelled();
                    $explosion = new Explosion($block->asPosition(), 1 + ($enchantment->getLevel() * 0.3));
					$explosion->explodeA();
					$cmd = Area::getInstance()->cmd;
					PrisonMiner::queue0($player);
					foreach($explosion->affectedBlocks as $i => $block){
						if(!$cmd->canEdit($player, $block)){
							unset($explosion->affectedBlocks[$i]);
						}
						PrisonMiner::$queue[$player->getName()][1][] = $block->getId();
					}
					$this->blockBreak($player, $explosion->affectedBlocks);
					$this->plugin->using[$player->getName()] = microtime(true) + 0.25;
					$player->getLevel()->addParticle(new HugeExplodeSeedParticle($block->asVector3()));
					$player->getLevel()->broadcastLevelSoundEvent($block->asVector3(), LevelSoundEventPacket::SOUND_EXPLODE);
                }
                return;
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::LUMBERJACK);
            if($enchantment !== null){
                if($player->isSneaking()){
                    if($block->getId() == Block::WOOD || $block->getId() == Block::WOOD2){
                        if(!isset($this->plugin->using[$player->getName()]) || $this->plugin->using[$player->getName()] < time()){
                            $this->plugin->mined[$player->getName()] = 0;
                            $this->breakTree($block, $player);
                        }
                    }
                }
                $event->setInstaBreak(true);
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::DRILLER);
            if($enchantment !== null){
                if(!isset($this->plugin->using[$player->getName()]) || $this->plugin->using[$player->getName()] < time()){
                    if(isset($this->plugin->blockface[$player->getName()])){
                        $side = $this->plugin->blockface[$player->getName()];
                        $sides = [$side <= 1 ? $side + 2 : $side - 2, $side > 1 && $side < 4 ? $side + 2 : ($side >= 4 ? $side - 4 : $side + 4)];
                        $item = $player->getInventory()->getItemInHand();
                        $blocks = [];
                        for ($i = 0; $i <= $enchantment->getLevel(); $i++){
                            $b = $block->getSide($side ^ 0x01, $i);
                            $b1 = $b->getSide($sides[0]);
                            $b2 = $b->getSide($sides[0] ^ 0x01);
                            $blocks[] = $b->getSide($sides[1]);
                            $blocks[] = $b->getSide($sides[1] ^ 0x01);
                            $blocks[] = $b1;
                            $blocks[] = $b2;
                            $blocks[] = $b1->getSide($sides[1] ^ 0x01);
                            $blocks[] = $b2->getSide($sides[1] ^ 0x01);
                            $blocks[] = $b1->getSide($sides[1]);
                            $blocks[] = $b2->getSide($sides[1]);
                            if($b !== $block){
                                $blocks[] = $b;
							}
                        }
                        $this->plugin->using[$player->getName()] = time() + 2;
                        foreach ($blocks as $b){
                            $block->getLevel()->useBreakOn($b, $item, $player, false);
                        }
                        unset($this->plugin->blockface[$player->getName()]);
				    }
                }
				$event->setInstaBreak(true);
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::SMELTING);
            if($enchantment !== null){
                $finaldrop = array();
                $otherdrops = array();
                foreach ($drops as $drop){
                    if(isset(self::SMELTED_ITEM[$drop->getId()])){
                        $finaldrop[] = Item::get(self::SMELTED_ITEM[$drop->getId()][0], self::SMELTED_ITEM[$drop->getId()][1], $drop->getCount());
                        continue;
                    }
                    if($drop->getId() == Item::SPONGE && $drop->getDamage() == 1){
                        $finaldrop[] = Item::get(Item::SPONGE, 0, $drop->getCount());
                        continue;
                    }
                    $finaldrop[] = $drop;
                }
                $event->setDrops($drops = array_merge($finaldrop, $otherdrops));
            }
            $this->blockBreak($player, [$block]);
            
            $enchantment = $item->getEnchantment(CustomEnchantsIds::ENERGIZING);
            if($enchantment !== null){
				$effect = new EffectInstance(Effect::getEffect(Effect::HASTE), 80 + 30 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                $player->addEffect($effect);
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::QUICKENING);
            if($enchantment !== null){
				$effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 80 + 20 * $enchantment->getLevel(), 1 + $enchantment->getLevel(), false);
                $player->addEffect($effect);
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::FARMER);
            if($enchantment !== null){
                $seed = null;
                switch ($block->getId()){
                    case Block::WHEAT_BLOCK:
                        $seed = Item::SEEDS;
                        break;
                    case Block::POTATO_BLOCK:
                        $seed = Item::POTATO;
                        break;
                    case Block::CARROT_BLOCK:
                        $seed = Item::CARROT;
                        break;
                    case Block::BEETROOT_BLOCK:
                        $seed = Item::BEETROOT_SEEDS;
                        break;
                }
                if($seed !== null){
                    $seed = Item::get($seed, 0, 1);
                    $pos = $block->subtract(0, 1);
                    $this->plugin->getScheduler()->scheduleDelayedTask(new PlaceTask($this->plugin, $pos, $block->getLevel(), $seed, $player), 1);
                }
            }
            $enchantment = $item->getEnchantment(CustomEnchantsIds::HARVEST);
            if($enchantment !== null){
                $radius = $enchantment->getLevel();
                if(!isset($this->plugin->using[$player->getName()]) || $this->plugin->using[$player->getName()] < time()){
                    if($block instanceof Crops){
                        for ($x = -$radius; $x <= $radius; $x++){
                            for ($z = -$radius; $z <= $radius; $z++){
                                $pos = $block->add($x, 0, $z);
                                if($block->getLevel()->getBlock($pos) instanceof Crops){
                                    $this->plugin->using[$player->getName()] = time() + 1;
                                    $item = $player->getInventory()->getItemInHand();
                                    $block->getLevel()->useBreakOn($pos, $item, $player);
                                }
                            }
                        }
                    }
                }
            }
            
            $enchantment = $item->getEnchantment(CustomEnchantsIds::JACKHAMMER);
            if($enchantment !== null){
            	if(isset($this->jackhammer_cooldown[$player->getName()]) && !(time() >= $this->jackhammer_cooldown[$player->getName()])){
            		return;
            	}
            	$event->setCancelled();
            	$this->jackhammer_cooldown[$player->getName()] = time() + 1;
            	switch($enchantment->getLevel()){
            		case 1:
            		   $side = Facing::HORIZONTAL[array_rand(Facing::HORIZONTAL)];
            		   for($i = 0; $i < 10; $i++){
            		   	    $player->getLevel()->useBreakOn($block->getSide($side, $i), $item, $player, false);
            		   	}
            		   break;
            		case 2:
            		    $side = Facing::HORIZONTAL[array_rand(Facing::HORIZONTAL)];
            	        for($i = 0; $i < 12; $i++){
            		   	    $player->getLevel()->useBreakOn($block->getSide($side, $i), $item, $player, false);
            		   	}
            		   	break;
            		case 3:
            		    $side = Facing::HORIZONTAL[array_rand(Facing::HORIZONTAL)];
            		    for($i = 0; $i < 14; $i++){
            		   	    $player->getLevel()->useBreakOn($block->getSide($side, $i), $item, $player, false);
            		   	}
            		   	break;
            		case 4:
            		    $side = Facing::HORIZONTAL[array_rand(Facing::HORIZONTAL)];
            		    for($i = 0; $i < 16; $i++){
            		   	    $player->getLevel()->useBreakOn($block->getSide($side, $i), $item, $player, false);
            		   	}
            		   	break;
            		case 5:
            		    foreach(Facing::HORIZONTAL as $side){
            		    	for($i = 0; $i < 16; $i++){
            		    		$player->getLevel()->useBreakOn($block->getSide($side, $i), $item, $player, false);
            		    	}
            		    }
            		    break;
            	}
            }
        }
        if($event instanceof PlayerInteractEvent){
            $block = $event->getBlock();
            
            $item = $event->getItem();
            if($item->getId() === Item::BLAZE_ROD){
            	$items = $item->getNamedTag()->getListTag("ItemList");
            	if($items instanceof ListTag){
            		$items = $items->getValue();
            		$event->setCancelled();
            	}else{
            		$items = [];
            	}
            	
            	if(!(count($player->getInventory()->getContents(false)) - 1 + count($items) > $player->getInventory()->getSize())){
            		$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
            	}else{
            		LangManager::send("inventory-nospace", $player);
            		return;
            	}
            
            	foreach($items as $item){
            		$item = $item->getValue();
            		$parts = explode(":", $item);
            		
            		list($name, $id, $damage, $count) = $parts;
            		$item = ItemFactory::get((int) $id, (int) $damage);
            		$item->setCustomName(TextFormat::RESET . $name);
            		$item->setCount((int) $count);
            		
            		if(isset($parts[4])){
            			$enchants = explode(",", $parts[4]);
            			foreach($enchants as $enchant){
            				$data = explode(";", $enchant);
            				$enchantment = CustomEnchants::getEnchantment($data[0]);
            				if(!$enchantment instanceof Enchantment){
            					$enchantment = Enchantment::getEnchantment($data[0]);
            				}
            				$item->addEnchantment(new EnchantmentInstance($enchantment, $data[1]));
            			}
            		}
            		$player->getInventory()->addItem($item);
            		LangManager::send("ce-compress", $player, $item->getCount(), TextFormat::clean(explode("\n", $item->getName())[0]));
            	}
            }
            
            $enchantment = $player->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::FERTILIZER);
            if($enchantment !== null){
                if(!isset($this->plugin->using[$player->getName()]) || $this->plugin->using[$player->getName()] < time()){
                    if($this->plugin->checkBlocks($block, [Block::DIRT, Block::GRASS])){
                        $radius = $enchantment->getLevel();
                        for ($x = -$radius; $x <= $radius; $x++){
                            for ($z = -$radius; $z <= $radius; $z++){
                                $pos = $block->add($x, 0, $z);
                                if($this->plugin->checkBlocks(Position::fromObject($pos, $block->getLevel()), [Block::DIRT, Block::GRASS])){
                                    $this->plugin->using[$player->getName()] = time() + 1;
                                    $item = $player->getInventory()->getItemInHand();
                                    $block->getLevel()->useItemOn($pos, $item, 0, $pos, $player);
                                }
                            }
                        }
                    }
                }
            }
		}
    }

    /**
     * @param Player $damager
     * @param Entity $entity
     * @param EntityEvent $event
     */
    public function checkBowEnchants(Player $damager, Entity $entity, EntityEvent $event)
    {
        if($event instanceof EntityDamageByChildEntityEvent){
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::PARALYZE);
            if($enchantment !== null){
                if($entity instanceof Living && $entity instanceof Player){
                    $chance = 7 * $enchantment->getLevel();
                    $random = \kenygamer\Core\Main::mt_rand(0, 800);
                    if($random <= $chance){
					    $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 100 * $enchantment->getLevel(), 0, false);
                        $entity->addEffect($effect);
					    $effect2 = new EffectInstance(Effect::getEffect(Effect::BLINDNESS), 100 + ($enchantment->getLevel() - 1) * 30, 0, false);
                        $entity->addEffect($effect2);
						$effect3 = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 200 * $enchantment->getLevel(), $enchantment->getLevel() - 1, false); 
                        $entity->addEffect($effect3);
                        LangManager::send("ce-paralyze", $entity);
					}
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::INSOMNIA);
            if($enchantment !== null){
                if($entity instanceof Living && $entity instanceof Player){
                    $chance = 7 * $enchantment->getLevel();
                    $random = \kenygamer\Core\Main::mt_rand(0, 800);
                    if($random <= $chance){
				     	$effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 20 * $enchantment->getLevel(), 0, false);
                        $entity->addEffect($effect);
					    $effect2 = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 100 * $enchantment->getLevel(), 0, false);
                        $entity->addEffect($effect2);
                        LangManager::send("ce-insomnia", $entity);
	    			}
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::VIRUS);
            if($enchantment !== null){
                if($entity instanceof Living && $entity instanceof Player){
                    $chance = 7 * $enchantment->getLevel();
                    $random = \kenygamer\Core\Main::mt_rand(0, 800);
                    if($random <= $chance){
				        $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 100 * $enchantment->getLevel(), 2, false);
                        $entity->addEffect($effect);
					    $effect2 = new EffectInstance(Effect::getEffect(Effect::WITHER), 100 + 30 * $enchantment->getLevel(), $enchantment->getLevel(), false);           
                        $entity->addEffect($effect2);
                        $entity->removeEffect(Effect::REGENERATION);
                        LangManager::send("ce-virus", $entity);
					}
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::VENOM);
            if($enchantment !== null){
                if($entity instanceof Living && $entity instanceof Player){
                    $chance = 5 * $enchantment->getLevel();
                    $random = \kenygamer\Core\Main::mt_rand(0, 500);
                    if($random <= $chance){
					    $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 150 + 30 * $enchantment->getLevel(), $enchantment->getLevel(), false);  
                        $entity->addEffect($effect);
                        LangManager::send("ce-venom", $entity);
					}
                }
            }
            
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::PIERCING);
            if($enchantment !== null){
                if($entity instanceof Player){
                    $chance = 5 * $enchantment->getLevel();
                    $random = \kenygamer\Core\Main::mt_rand(0, 500);
                    if($random <= $chance){
                        $damage = 2 + 3 * $enchantment->getLevel();
				        $event->setModifier($damage, EntityDamageEvent::MODIFIER_ARMOR);
				        LangManager::send("ce-piercing", $entity);
                    }
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::SHUFFLE);
            if($enchantment !== null){
                if($entity instanceof Player){
                    $chance = 10 * $enchantment->getLevel();
                    $random = \kenygamer\Core\Main::mt_rand(0, 500);
                    if($random <= $chance){
                        $pos1 = clone $damager->getPosition();
                        $pos2 = clone $entity->getPosition();
                        $damager->teleport($pos2);
                        $entity->teleport($pos1);
						$name = $entity->getNameTag();
                        if($entity instanceof Player){
                            $name = $entity->getDisplayName();
                            LangManager::send("ce-shuffle", $entity, $damager->getName());
                        }
                        LangManager::send("ce-shufle", $damager, $entity->getName());
                    }
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::BOUNTYHUNTER);
            if($enchantment !== null){
				if($entity instanceof Player){
                    if(!isset($this->plugin->bountyhuntercd[$damager->getName()]) || time() > $this->plugin->bountyhuntercd[$damager->getName()]){
                        $bountydrop = $this->getBounty();
                        $damager->getInventory()->addItem(Item::get($bountydrop, 0, \kenygamer\Core\Main::mt_rand(0, 8 + $enchantment->getLevel()) + 1));
                        $this->plugin->bountyhuntercd[$damager->getName()] = time() + 10;
					}
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::HEALING);
            if($enchantment !== null){
				if($entity instanceof Player){
                    if($entity->getHealth() + $event->getFinalDamage() + $enchantment->getLevel() <= $entity->getMaxHealth()){
                        $entity->setHealth($entity->getHealth() + $event->getFinalDamage() + $enchantment->getLevel());
                    } else {
                        $entity->setHealth($entity->getMaxHealth());
                    }
                    foreach ($event->getModifiers() as $modifier => $damage){
                        $event->setModifier(0, $modifier);
                    }
                    $event->setBaseDamage(0);
				}
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::HEADHUNTER);
            if($enchantment !== null){
                $projectile = $event->getChild();
                if($projectile->y > $entity->getPosition()->y + $entity->getEyeHeight()){
                    $damage = 1 + ($enchantment->getLevel() / 10);
                    $event->setModifier((($event->getFinalDamage() * (1 + 0.10 * $enchantment->getLevel())) - $event->getFinalDamage()), CustomEnchantsIds::HEADHUNTER);
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::LONGBOW);
            if($enchantment !== null){
				$damage = 3 + ($enchantment->getLevel() / 25);
                $event->setModifier($damage, CustomEnchantsIds::LONGBOW);
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::BOWLIFESTEAL);
            if($enchantment !== null){
                if($damager->getHealth() + 2 + $enchantment->getLevel() <= $damager->getMaxHealth()){
                    $damager->setHealth($damager->getHealth() + 2 + $enchantment->getLevel());
                } else {
                    $damager->setHealth($damager->getMaxHealth());
                }
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::GRAPPLING);
            if($enchantment !== null){
                $task = new GrapplingTask($this->plugin, $damager->getPosition(), $entity);
                $this->plugin->getScheduler()->scheduleDelayedTask($task, 1); //Delayed due to knockback interfering
            }
		}
        if($event instanceof EntityShootBowEvent){
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::PLACEHOLDER);
            if($enchantment !== null){
                $nbt = Entity::createBaseNBT($entity, $damager->getDirectionVector(), $entity->yaw, $entity->pitch);
                $newentity = Entity::createEntity("VolleyArrow", $damager->getLevel(), $nbt, $damager, $entity->isCritical(), true, false);
                $newentity->setMotion($newentity->getMotion()->multiply($event->getForce()));
                $newentity->spawnToAll();
                $entity->close();
                $entity = $newentity;
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::PORKIFIED);
            if($enchantment !== null && $entity instanceof PigProjectile !== true){
                $nbt = Entity::createBaseNBT($entity, $damager->getDirectionVector(), $entity->yaw, $entity->pitch);
                $pig = Entity::createEntity("PigProjectile", $damager->getLevel(), $nbt, $damager, isset($entity->placeholder) ? $entity->placeholder : false, $enchantment->getLevel());
                $pig->setMotion($pig->getMotion()->multiply($event->getForce()));
                $pig->spawnToAll();
                $entity->close();
                $entity = $pig;
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::WITHERSKULL);
            if($enchantment !== null && $entity instanceof PiggyWitherSkull !== true){
                $nbt = Entity::createBaseNBT($entity, $damager->getDirectionVector(), $entity->yaw, $entity->pitch);
                $skull = Entity::createEntity("PiggyWitherSkull", $damager->getLevel(), $nbt, $damager, isset($entity->placeholder) ? $entity->placeholder : false, $enchantment->getLevel() > 1 ? true : false);
                $skull->setMotion($skull->getMotion()->multiply($event->getForce()));
                $skull->spawnToAll();
                $entity->close();
                $entity = $skull;
            }
            $enchantment = $damager->getInventory()->getItemInHand()->getEnchantment(CustomEnchantsIds::VOLLEY);
            if($enchantment !== null){
                $amount = 1 + 2 * $enchantment->getLevel();
                $anglesbetweenarrows = (45 / ($amount - 1)) * M_PI / 180;
                $pitch = ($damager->getLocation()->getPitch() + 90) * M_PI / 180;
                $yaw = ($damager->getLocation()->getYaw() + 90 - 45 / 2) * M_PI / 180;
                $sZ = cos($pitch);
                for ($i = 0; $i < $amount; $i++){
                    $nX = sin($pitch) * cos($yaw + $anglesbetweenarrows * $i);
                    $nY = sin($pitch) * sin($yaw + $anglesbetweenarrows * $i);
                    $newDir = new Vector3($nX, $sZ, $nY);
                    $projectile = null;
                    if($entity instanceof Arrow){
                        $nbt = Entity::createBaseNBT($damager->add(0, $damager->getEyeHeight()), $damager->getDirectionVector(), $damager->yaw, $damager->pitch);
                        $projectile = Entity::createEntity("VolleyArrow", $damager->getLevel(), $nbt, $damager, $entity->isCritical(), false, true);
                    }
                    if($entity instanceof PigProjectile){
                        $nbt = Entity::createBaseNBT($damager->add(0, $damager->getEyeHeight()), $damager->getDirectionVector(), $damager->yaw, $damager->pitch);
                        $projectile = Entity::createEntity("PigProjectile", $damager->getLevel(), $nbt, $damager, false, $entity->getPorkLevel());
                    }
                    if($entity instanceof PiggyWitherSkull){
                        $nbt = Entity::createBaseNBT($damager->add(0, $damager->getEyeHeight()), $damager->getDirectionVector(), $damager->yaw, $damager->pitch);
                        $projectile = Entity::createEntity("PiggyWitherSkull", $damager->getLevel(), $nbt, $damager);
                    }
                    $projectile->setMotion($newDir->normalize()->multiply($entity->getMotion()->multiply($event->getForce())->length()));
                    if($projectile->isOnFire()){
                        $projectile->setOnFire($entity->fireTicks * 20);
                    }
                    $projectile->spawnToAll();
                }
                $entity->close();
            }
        }
        if($event instanceof ProjectileHitBlockEvent && $entity instanceof Projectile){
			$player = $entity->getOwningEntity();
			if($player !== null){
				$item = $player->getInventory()->getItemInHand();
				
				$enchantment = $item->getEnchantment(CustomEnchantsIds::RELOCATE);
	            if($enchantment !== null){
					$player->teleport($entity->asPosition());
				}
			}
			
			$enchantment = $item->getEnchantment(CustomEnchantsIds::GRAPPLING);
            if($enchantment !== null){
                $location = $entity->getPosition();
                $damagerloc = $damager->getPosition();
                if($damager->distance($entity) < 6){
                    if($location->y > $damager->y){
                        $damager->setMotion(new Vector3(0, 0.25, 0));
                    } else {
                        $v = $location->subtract($damagerloc);
                        $damager->setMotion($v);
                    }
                } else {
                    $g = -0.08;
                    $d = $location->distance($damagerloc);
                    $t = $d;
                    $v_x = (1.0 + 0.07 * $t) * ($location->x - $damagerloc->x) / $t;
                    $v_y = (1.0 + 0.03 * $t) * ($location->y - $damagerloc->y) / $t - 0.5 * $g * $t;
                    $v_z = (1.0 + 0.07 * $t) * ($location->z - $damagerloc->z) / $t;
                    $v = $damager->getMotion();
                    $v->setComponents($v_x, $v_y, $v_z);
                    $damager->setMotion($v);
                }
                $this->plugin->nofall[$damager->getName()] = time() + 1;
            }
        }
    }

    /**
     * @param Entity $entity
     * @param EntityEvent|Event $event
     */

    public function checkArmorEnchants(Entity $entity, Event $event)
    {
        if($entity instanceof Player){
            if($event instanceof EntityDamageEvent){
            	
            	foreach($entity->getLevel()->getNearbyEntities($entity->getBoundingBox()->expandedCopy(10, 10, 10)) as $ent){
            		if($ent instanceof Player && $ent !== $entity){
            			$enchantment = $ent->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::BLOODLUST);
            			if($enchantment !== null){
            				if(\kenygamer\Core\Main::mt_rand(1, 99) <= $enchantment->getLevel() * 5){
            					$newHealth = $ent->getHealth() + $event->getFinalDamage();
            					$ent->setHealth(!($newHealth > $ent->getMaxHealth()) ? $newHealth : $ent->getMaxHealth());
            					if(round($event->getFinalDamage()) > 0){
            						LangManager::send("ce-bloodlust", $ent, round($event->getFinalDamage()));
            					}
            					break;
            	    		}
            	    	}
            	    }
            	}
            	
                $cause = $event->getCause();
                $antikb = 4;
				if($cause === EntityDamageEvent::CAUSE_BLOCK_EXPLOSION || $cause === EntityDamageEvent::CAUSE_ENTITY_EXPLOSION){
					
                    $enchantment = $entity->getArmorInventory()->getLeggings()->getEnchantment(CustomEnchantsIds::CREEPERARMOR);
                    if($enchantment !== null){
				    	$chance = 20 * $enchantment->getLevel();
                        $random = \kenygamer\Core\Main::mt_rand(0, 100);
                        if($random <= $chance){
                        	LangManager::send("ce-creeperarmor", $entity);
					        $event->setCancelled();
						}
                    }
                }
				if($cause === EntityDamageEvent::CAUSE_PROJECTILE || $cause === EntityDamageEvent::CAUSE_ENTITY_ATTACK){
                    $enchantment = $entity->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::EVASION);
                    if($enchantment !== null){
						$damager = $event->getDamager();
						if($damager instanceof Player){
				    	    $chance = 10 * $enchantment->getLevel();
                            $random = \kenygamer\Core\Main::mt_rand(0, 100);
                            if($random <= $chance){
                            	LangManager::send("ce-evasion", $damager, $entity->getName());
						        $event->setCancelled();
							}
						}
					}
				}
                foreach ($entity->getArmorInventory()->getContents() as $slot => $armor){
					if($cause === EntityDamageEvent::CAUSE_FIRE || $cause === EntityDamageEvent::CAUSE_FIRE_TICK || $cause === EntityDamageEvent::CAUSE_LAVA){
                        $enchantment = $armor->getEnchantment(CustomEnchantsIds::OBSIDIANSHIELD);
                        if($enchantment !== null){
						    if($entity->isOnFire()){
                                $entity->extinguish();
					 	        $event->setCancelled();
							}
						}
					}
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::REVIVE);
                    if($enchantment !== null){
                        if($event->getFinalDamage() >= $entity->getHealth()){
					    	$cost = 10000;
	                        if(!($entity->getCurrentTotalXp() - $cost < 0)){
                                if($enchantment->getLevel() > 1){
                                    $entity->getArmorInventory()->setItem($slot, $this->plugin->addEnchantment($armor, $enchantment->getId(), $enchantment->getLevel() - 1));
                                } else {
                                    $entity->getArmorInventory()->setItem($slot, $this->plugin->removeEnchantment($armor, $enchantment));
                                    LangManager::send("ce-revive-maxuses", $entity);
                                }
                                $entity->setHealth($entity->getMaxHealth());
                                $entity->setFood($entity->getMaxFood());
						        $effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 600, 4, false);
                                $entity->addEffect($effect);
						        $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 600, 0, false);
                                $entity->addEffect($effect);
                                for ($i = $entity->y; $i <= 256; $i += 0.25){
                                    $entity->getLevel()->addParticle(new FlameParticle(new Vector3($entity->x, $i, $entity->z)));
                                }
                                LangManager::send("ce-revive", $entity);
							    $entity->subtractXp($cost);
                                foreach ($event->getModifiers() as $modifier => $damage){
                                    $event->setModifier(0, $modifier);
                                }
                                $event->setBaseDamage(0);
							}
						}
					}
					$newHealth = max(0, $entity->getHealth() - $event->getFinalDamage());
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::ENDERSHIFT);
                    if($enchantment !== null){
                        if($newHealth <= 4){
							if(!isset($this->plugin->endershiftcd[$entity->getName()]) || time() > $this->plugin->endershiftcd[$entity->getName()]){
                                $this->plugin->endershiftcd[$entity->getName()] = time() + 300;
							    $effect = new EffectInstance(Effect::getEffect(Effect::SPEED), 500 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                $entity->addEffect($effect);
							    $effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 500 * $enchantment->getLevel(), 4 + $enchantment->getLevel(), false);
                                $entity->addEffect($effect);
                                LangManager::send("ce-endershift", $entity);
                            }
                        }
                    }
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::DEFENSE);
                    if($enchantment !== null){
                        if($newHealth <= 4){
                            if(!isset($this->plugin->defensecd[$entity->getName()]) || time() > $this->plugin->defensecd[$entity->getName()]){
                                $this->plugin->defensecd[$entity->getName()] = time() + 300;
								$effect = new EffectInstance(Effect::getEffect(Effect::DAMAGE_RESISTANCE), 300 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                $entity->addEffect($effect);
								$effect2 = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 200 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                $entity->addEffect($effect2);
                                LangManager::send("ce-defense", $entity);
                            }
                        }
                    }
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::BERSERKER);
                    if($enchantment !== null){
                        if($newHealth <= 4){
                            if(!isset($this->plugin->berserkercd[$entity->getName()]) || time() > $this->plugin->berserkercd[$entity->getName()]){
                                $this->plugin->berserkercd[$entity->getName()] = time() + 300;
							    $effect = new EffectInstance(Effect::getEffect(Effect::STRENGTH), 600 * $enchantment->getLevel(), 4 + $enchantment->getLevel(), false);
                                $entity->addEffect($effect);
                                LangManager::send("ce-berseker", $entity);
                            }
                        }
                    }
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::ANGELIC);
                    if($enchantment !== null){
                        if(!isset($this->plugin->angeliccd[$entity->getName()]) || time() > $this->plugin->angeliccd[$entity->getName()]){
                            $this->plugin->angeliccd[$entity->getName()] = time() + 3;
                            if($entity->getHealth() + 2 + $enchantment->getLevel() <= $entity->getMaxHealth()){
                                $entity->setHealth($entity->getHealth() + 2 + $enchantment->getLevel());
                            } else {
                                $entity->setHealth($entity->getMaxHealth());
                            }
                        }
                    }
                }
                if($event instanceof EntityDamageByEntityEvent){
                    $damager = $event->getDamager();
                    foreach ($entity->getArmorInventory()->getContents() as $slot => $armor){
                        $enchantment = $armor->getEnchantment(CustomEnchantsIds::MOLTEN);
                        if($enchantment !== null){
                            $this->plugin->getScheduler()->scheduleDelayedTask(new MoltenTask($this->plugin, $damager, $enchantment->getLevel()), 1);
                        }
                        $enchantment = $armor->getEnchantment(CustomEnchantsIds::ENLIGHTED);
                        if($enchantment !== null){
                            $chance = 5 * $enchantment->getLevel();
                            $random = \kenygamer\Core\Main::mt_rand(0, 1000);
                            if($random <= $chance){
								$effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 60 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                $entity->addEffect($effect);
                                LangManager::send("ce-enlighted", $entity);
                            }
                        }
						if($damager instanceof Living && $damager instanceof Player){
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::HARDENED);
                            if($enchantment !== null){
								$chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 500);
                                if($random <= $chance){
                                    $effect = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 120 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect);
								}
                            }
							$enchantment = $armor->getEnchantment(CustomEnchantsIds::POISONED);
                            if($enchantment !== null){
								$chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 500);
                                if($random <= $chance){
                                    $effect = new EffectInstance(Effect::getEffect(Effect::POISON), 100 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect);
								}
                            }
							$enchantment = $armor->getEnchantment(CustomEnchantsIds::FROZEN);
                            if($enchantment !== null){
								$chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 500);
                                if($random <= $chance){
                                    $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 100 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect);
								}
                            }
							$enchantment = $armor->getEnchantment(CustomEnchantsIds::REVULSION);
                            if($enchantment !== null){
								$chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 500);
                                if($random <= $chance){
                                    $effect = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 60 * $enchantment->getLevel(), 0, false);
                                    $damager->addEffect($effect);
								}
                            }
							$enchantment = $armor->getEnchantment(CustomEnchantsIds::CURSED);
                            if($enchantment !== null){
								$chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 500);
                                if($random <= $chance){
                                    $effect = new EffectInstance(Effect::getEffect(Effect::WITHER), 100 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect);
								}
                            }
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::DRUNK);
                            if($enchantment !== null){
                                $chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 1000);
                                if($random <= $chance){
							        $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 120 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect);
								    $effect2 = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 200 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect2);
								    $effect3 = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 30 * $enchantment->getLevel(), 0, false);
                                    $damager->addEffect($effect3);
                                    LangManager::send("ce-drunk", $damager, $entity->getName());
								}
							}
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::DOOMED);
                            if($enchantment !== null){
                                $chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 1500);
                                if($random <= $chance){
								    $effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 200 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect);
								    $effect2 = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 650 * $enchantment->getLevel(), 7 + $enchantment->getLevel(), false);
                                    $damager->addEffect($effect2);
								    $effect3 = new EffectInstance(Effect::getEffect(Effect::WITHER), 400 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect3);
							        $effect4 = new EffectInstance(Effect::getEffect(Effect::WEAKNESS), 500 * $enchantment->getLevel(), $enchantment->getLevel(), false);
                                    $damager->addEffect($effect4);
								    $effect5 = new EffectInstance(Effect::getEffect(Effect::NAUSEA), 120 * $enchantment->getLevel(), 0, false);
                                    $damager->addEffect($effect5);
                                    LangManager::send("ce-doomed", $damager);
								}
                            }
							$enchantment = $armor->getEnchantment(CustomEnchantsIds::NATUREWRATH);
                            if($enchantment !== null){
                                $chance = 5 * $enchantment->getLevel();
                                $random = \kenygamer\Core\Main::mt_rand(0, 500);
                                if($random <= $chance){
									$cost = 10000 * $enchantment->getLevel();
	                                if($entity->getCurrentTotalXp() - $cost < 0){
					     	        } else {
										$entity->subtractXp($cost);
										$radius = 5 + 2 * $enchantment->getLevel();
										LangManager::send("ce-naturewrath", $entity, $radius);
                                     	foreach ($entity->getLevel()->getEntities() as $target){
                         	                if($target !== $entity && $target instanceof Living && $target->distance($entity) <= $radius){
													$effect = new EffectInstance(Effect::getEffect(Effect::SLOWNESS), 1000 * $enchantment->getLevel(), $enchantment->getLevel(), false);
								                    $effect2 = new EffectInstance(Effect::getEffect(Effect::MINING_FATIGUE), 1000 * $enchantment->getLevel(), $enchantment->getLevel(), false);
											    	$target->addEffect($effect);
                                                    $target->addEffect($effect2);
                                                    if($target instanceof Player){
                                                    	$this->frozen($target);
                                                    }
											}
										}
									}
								}
							}
                        }
                        $enchantment = $armor->getEnchantment(CustomEnchantsIds::CLOAKING);
                        if($enchantment !== null){
                            if((!isset($this->plugin->cloakingcd[$entity->getName()]) || time() > $this->plugin->cloakingcd[$entity->getName()]) && $entity->hasEffect(Effect::INVISIBILITY)){
                                $this->plugin->cloakingcd[$entity->getName()] = time() + 10;
                                $effect = new EffectInstance(Effect::getEffect(Effect::INVISIBILITY), 120 * $enchantment->getLevel(), 0, false);
                                $entity->addEffect($effect);
                                LangManager::send("ce-cloaking", $entity);
                            }
                        }
                        $enchantment = $armor->getEnchantment(CustomEnchantsIds::ANTIKNOCKBACK);
                        if($enchantment !== null){
                            $event->setKnockBack($event->getKnockBack() - ($event->getKnockBack() / $antikb));
                            $antikb--;
                        }
                        if($damager instanceof Player){
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::ARMORED);
                            if($enchantment !== null){
                                if($damager->getInventory()->getItemInHand() instanceof Sword){
                                    $event->setModifier(-($event->getFinalDamage() * 0.1 * $enchantment->getLevel()), CustomEnchantsIds::ARMORED);
                                }
                            }
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::TANK);
                            if($enchantment !== null){
                                if($damager->getInventory()->getItemInHand() instanceof Axe){
                                    $event->setModifier(-($event->getFinalDamage() * 0.1 * $enchantment->getLevel()), CustomEnchantsIds::TANK);
                                }
                            }
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::HEAVY);
                            if($enchantment !== null){
                                if($damager->getInventory()->getItemInHand()->getId() == Item::BOW){
                                    $event->setModifier(-($event->getFinalDamage() * 0.1 * $enchantment->getLevel()), CustomEnchantsIds::HEAVY);
								}
							}
                            $enchantment = $armor->getEnchantment(CustomEnchantsIds::DIVINE);
                            if($enchantment !== null){
							    if($damager->getInventory()->getItemInHand() instanceof Tool){
								    $event->setModifier(-($event->getFinalDamage() * 0.2 * $enchantment->getLevel()), CustomEnchantsIds::DIVINE);
								}
								$item = $damager->getInventory()->getItemInHand();
                                if($item->getId() === Item::TRIDENT || $item->getId() === Item::AIR){
									$event->setModifier(-($event->getFinalDamage() * 0.2 * $enchantment->getLevel()), CustomEnchantsIds::DIVINE);
                                }
                            }
                        }
                    }
                }
            }
            if($event instanceof EntityEffectAddEvent){
                $effect = $event->getEffect();
                $enchantment = $entity->getArmorInventory()->getHelmet()->getEnchantment(CustomEnchantsIds::FOCUSED);
                if($enchantment !== null){
                    if(!isset($this->plugin->using[$entity->getName()]) || $this->plugin->using[$entity->getName()] < time()){
                        if($effect->getId() == Effect::NAUSEA){
                            if($effect->getEffectLevel() - ($enchantment->getLevel() * 2) <= 0){
                                $event->setCancelled();
                            } else {
                                $event->setCancelled();
                                $this->plugin->using[$entity->getName()] = time() + 1;
                                $entity->addEffect($effect->setAmplifier($effect->getEffectLevel() - (1 + ($enchantment->getLevel() * 2))));
                            }
                        }
                    }
                }
                $enchantment = $entity->getArmorInventory()->getHelmet()->getEnchantment(CustomEnchantsIds::ANTITOXIN);
                if($enchantment !== null){
                    if($effect->getId() == Effect::POISON){
                        $event->setCancelled();
                    }
                }
                $enchantment = $entity->getArmorInventory()->getHelmet()->getEnchantment(CustomEnchantsIds::CLARITY);
                if($enchantment !== null){
                    if($effect->getId() == Effect::BLINDNESS){
                        $event->setCancelled();
                    }
                }
                $enchantment = $entity->getArmorInventory()->getLeggings()->getEnchantment(CustomEnchantsIds::SASH);
                if($enchantment !== null){
                    if($effect->getId() == Effect::SLOWNESS){
                        $event->setCancelled();
                    }
                }
                $enchantment = $entity->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::VITAMINS);
                if($enchantment !== null){
                    if($effect->getId() == Effect::WEAKNESS){
                        $event->setCancelled();
                    }
                }
                $enchantment = $entity->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::REMEDY);
                if($enchantment !== null){
                    if($effect->getId() == Effect::LEVITATION || $effect->getId() == Effect::SLOWNESS || $effect->getId() == Effect::WEAKNESS || $effect->getId() == Effect::BLINDNESS || $effect->getId() == Effect::POISON || $effect->getId() == Effect::WITHER || $effect->getId() == Effect::MINING_FATIGUE){
                    	if(($cause = $entity->getLastDamageCause()) instanceof EntityDamageByEntityEvent && ($damager = $cause->getDamager()) instanceof Player){
                    		$points = 0;
                    		foreach($damager->getArmorInventory()->getContents() as $armor){
                    			if($enchantment = $armor->getEnchantment(CustomEnchantsIds::PENETRATING)){
                    				$points += $enchantment->getLevel() * 2.5;
                    			}
                    		}
                    		if(!(\kenygamer\Core\Main::mt_rand(0, 99) <= $points)){
                    			$event->setCancelled();
                    		}
                    	}
                    }
                }
            }
            if($event instanceof PlayerMoveEvent){
            	$enchantment = $entity->getArmorInventory()->getBoots()->getEnchantment(CustomEnchantsIds::FROSTWALKER);
            	if($enchantment !== null){
            		$radius = 2 + $enchantment->getLevel();
            		
            		for($x = -$radius; $x <= $radius; $x++){
            			for($z = -$radius; $z <= $radius; $z++){
            				$b = $entity->getLevel()->getBlock($entity->add($x, -1, $z));
            				$itm = ItemFactory::get(Item::DIAMOND_SHOVEL);
            				if(in_array($b->getId(), [Block::STILL_WATER, Block::FLOWING_WATER])){
            					if(Area::getInstance()->cmd->canEdit($entity, $b->asPosition())){
            						$entity->getLevel()->setBlockIdAt($b->x, $b->y, $b->z, Block::ICE);
            					}
            				}
            			}
            		}
            	}
                $enchantment = $entity->getArmorInventory()->getBoots()->getEnchantment(CustomEnchantsIds::MAGMAWALKER);
                if($enchantment !== null){
                    $block = $entity->getLevel()->getBlock($entity);
                    if(!$this->plugin->checkBlocks($block, [Block::STILL_LAVA, Block::LAVA, Block::FLOWING_LAVA])){
                        $radius = $enchantment->getLevel() + 2;
                        for ($x = -$radius; $x <= $radius; $x++){
                            for ($z = -$radius; $z <= $radius; $z++){
                                $b = $entity->getLevel()->getBlock($entity->add($x, -1, $z));
                                if($this->plugin->checkBlocks($b, [Block::STILL_LAVA, Block::LAVA, Block::FLOWING_LAVA])){
                                    if($this->plugin->checkBlocks($b, [Block::STILL_LAVA, Block::LAVA, Block::FLOWING_LAVA], -1) !== true){
                                        if(!($b->getId() == Block::FLOWING_LAVA && $b->getDamage() > 0)){ //In vanilla, Frostwalker doesn't change non source blocks to ice
                                            $block = Block::get(Block::OBSIDIAN, 15);
                                            $entity->getLevel()->setBlock($b, $block);
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                $enchantment = $entity->getArmorInventory()->getHelmet()->getEnchantment(CustomEnchantsIds::MEDITATION);
                if($enchantment !== null){
                    if($event->getFrom()->floor() !== $event->getTo()->floor()){
                        $this->plugin->meditationTick[$entity->getName()] = 0;
                    }
                }
                $enchantment = $entity->getArmorInventory()->getHelmet()->getEnchantment(CustomEnchantsIds::IMPLANTS);
                if($enchantment !== null){
                   // if($event->getFrom()->floor() !== $event->getTo()->floor()){
					if(true){
                        if(!isset($this->plugin->implantscd[$entity->getName()]) || $this->plugin->implantscd[$entity->getName()] < time()){
                            if($entity->getFood() < 20){
                                $entity->setFood($entity->getFood() + $enchantment->getLevel() > 20 ? 20 : $entity->getFood() + $enchantment->getLevel());
                            }
                            if($entity->getAirSupplyTicks() < $entity->getMaxAirSupplyTicks() && isset($this->plugin->implants[$entity->getName()]) !== true){
                                $this->plugin->implants[$entity->getName()] = true;
                                $task = new ImplantsTask($this->plugin, $entity);
                                $handler = $this->plugin->getScheduler()->scheduleDelayedRepeatingTask($task, 20, 60);
                                $task->setHandler($handler);
                            }
                            $this->plugin->implantscd[$entity->getName()] = time() + 1;
						}
					}
				}
			}
			if($event instanceof EntityArmorChangeEvent){
				$item = $event->getNewItem();
				$oldItem = $event->getOldItem();
				foreach([
					CustomEnchantsIds::BUNNY => Effect::JUMP_BOOST] as $enchant => $effect){
					if($item->hasEnchantment($enchant)){
						if(!$entity->hasEffect($effect)){
							$level = $item->getEnchantment($enchant)->getLevel();
							$entity->addEffect(new EffectInstance(Effect::getEffect($effect), 20 * 60, $level - 1));
						}
					}
					if($oldItem->hasEnchantment($enchant)){
						if($entity->hasEffect($effect)){
							$entity->removeEffect($effect);
						}
					}
				}
				
				if($item->hasEnchantment(CustomEnchantsIds::OVERLORD)){
					$level = $item->getEnchantment(CustomEnchantsIds::OVERLORD)->getLevel();
					$entity->setMaxHealth($entity->getMaxHealth() + ($level * 2));
				}
				if($oldItem->hasEnchantment(CustomEnchantsIds::OVERLORD)){
					if($entity->getMaxHealth() > 20){
						$level = $oldItem->getEnchantment(CustomEnchantsIds::OVERLORD)->getLevel();
						$entity->setMaxHealth($entity->getMaxHealth() - ($level * 2));
					}
				}
			}
            if($event instanceof PlayerToggleSneakEvent && !$entity->isFlying()){
                $shrinkpoints = 0;
                $growpoints = 0;
                $shrinklevel = 0;
                $growlevel = 0;
                foreach ($entity->getArmorInventory()->getContents() as $armor){
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::SHRINK);
                    if($enchantment !== null){
                        $shrinklevel += $enchantment->getLevel();
                        $shrinkpoints++;
                    }
                    $enchantment = $armor->getEnchantment(CustomEnchantsIds::GROW);
                    if($enchantment !== null){
                        $growlevel += $enchantment->getLevel();
                        $growpoints++;
                    }
                }
				$shrinkpoints = 0; //TODO
                if($shrinkpoints >= 4){
                    if(isset($this->plugin->shrunk[$entity->getName()]) && $this->plugin->shrunk[$entity->getName()] > time()){
                        $this->plugin->shrinkremaining[$entity->getName()] = $this->plugin->shrunk[$entity->getName()] - time();
                        unset($this->plugin->shrinkcd[$entity->getName()]);
                        unset($this->plugin->shrunk[$entity->getName()]);
                        $entity->setScale(1);
                        LangManager::send("ce-shrink-normal", $entity);
                    } else {
                        if(!isset($this->plugin->shrinkcd[$entity->getName()]) || $this->plugin->shrinkcd[$entity->getName()] <= time()){
                            $scale = $entity->getScale() - 0.25 - (($shrinklevel / 4) * 0.05);
                            $entity->setScale(max(0.01, $scale));
                            $this->plugin->shrunk[$entity->getName()] = isset($this->plugin->shrinkremaining[$entity->getName()]) ? time() + $this->plugin->shrinkremaining[$entity->getName()] : time() + 60;
                            $this->plugin->shrinkcd[$entity->getName()] = isset($this->plugin->shrinkremaining[$entity->getName()]) ? time() + (75 - (60 - $this->plugin->shrinkremaining[$entity->getName()])) : time() + 75;
							LangManager::send("ce-shrink-shrunk", $entity);
                            if(isset($this->plugin->shrinkremaining[$entity->getName()])){
                                unset($this->plugin->shrinkremaining[$entity->getName()]);
                            }
                        }
                    }
                }
                if($growpoints >= 4){
                    if(isset($this->plugin->grew[$entity->getName()]) && $this->plugin->grew[$entity->getName()] > time()){
                        $this->plugin->growremaining[$entity->getName()] = $this->plugin->grew[$entity->getName()] - time();
                        unset($this->plugin->growcd[$entity->getName()]);
                        unset($this->plugin->grew[$entity->getName()]);
                        $entity->setScale(1);
						LangManager::send("ce-grow-normal", $entity);
                    } else {
                        if(!isset($this->plugin->growcd[$entity->getName()]) || $this->plugin->growcd[$entity->getName()] <= time()){
                            $scale = $entity->getScale() + 0.25 + (($growlevel / 4) * 0.05);
                            $entity->setScale($scale);
                            $this->plugin->grew[$entity->getName()] = isset($this->plugin->growremaining[$entity->getName()]) ? time() + $this->plugin->growremaining[$entity->getName()] : time() + 60;
                            $this->plugin->growcd[$entity->getName()] = isset($this->plugin->growremaining[$entity->getName()]) ? time() + (75 - (60 - $this->plugin->growremaining[$entity->getName()])) : time() + 75;
							LangManager::send("ce-grow-grown", $entity);
                            if(isset($this->plugin->growremaining[$entity->getName()])){
                                unset($this->plugin->growremaining[$entity->getName()]);
                            }
                        }
                    }
                }
                $enchantment = $entity->getArmorInventory()->getBoots()->getEnchantment(CustomEnchantsIds::JETPACK);
                if($enchantment !== null){
                    if(isset($this->plugin->flying[$entity->getName()]) && $this->plugin->flying[$entity->getName()] > time()){
                        if($entity->isOnGround()){
                            $this->plugin->flyremaining[$entity->getName()] = $this->plugin->flying[$entity->getName()] - time();
                            unset($this->plugin->jetpackcd[$entity->getName()]);
                            unset($this->plugin->flying[$entity->getName()]);
                            LangManager::send("ce-jetpack-off", $entity);
                        } else {
                        	LangManager::send("ce-jetpack-flying", $entity);
                        }
                    } else {
                        if(!in_array($event->getPlayer()->getLevel()->getName(), $this->plugin->jetpackDisabled)){
                            if(!isset($this->plugin->jetpackcd[$entity->getName()]) || $this->plugin->jetpackcd[$entity->getName()] <= time()){
                                $this->plugin->flying[$entity->getName()] = isset($this->plugin->flyremaining[$entity->getName()]) ? time() + $this->plugin->flyremaining[$entity->getName()] : time() + 300;
                                $this->plugin->jetpackcd[$entity->getName()] = isset($this->plugin->flyremaining[$entity->getName()]) ? time() + (360 - (300 - $this->plugin->flyremaining[$entity->getName()])) : time() + 360;
                                LangManager::send("ce-jetpack-on", $entity);
                                if(isset($this->plugin->flyremaining[$entity->getName()])){
                                    unset($this->plugin->flyremaining[$entity->getName()]);
							    }
							}
                        } else {
                        	LangManager::send("ce-jetpack-notworld", $entity);
                        }
                    }
                }
            }
		}
	}

    /**
     * @param Block $block
     * @param Player $player
     * @param Block|null $oldblock
     */
    public function breakTree(Block $block, Player $player, Block $oldblock = null)
    {
        $item = $player->getInventory()->getItemInHand();
        for ($i = 0; $i <= 5; $i++){
            if($this->plugin->mined[$player->getName()] > 800){
                break;
            }
            $this->plugin->using[$player->getName()] = time() + 1;
            $side = $block->getSide($i);
            if($oldblock !== null){
                if($side->equals($oldblock)){
                    continue;
                }
            }
            if($side->getId() !== Block::WOOD && $side->getId() !== Block::WOOD2){
                continue;
            }
            $player->getLevel()->useBreakOn($side, $item, $player);
            $this->plugin->mined[$player->getName()]++;
            $this->breakTree($side, $player, $block);
        }
    }

	/**
	 * @param Player $player
	 * @param bool $force
     */
	public function frozen(Player $player, bool $force = false)
	{
		if($player->getArmorInventory()->getBoots()->getEnchantment(CustomEnchantsIds::WARMER) !== null && !$force){
			LangManager::send("ce-warmer", $player);
			return;
		}
			
        if(isset($this->plugin->freeze[$player->getName()])){
            if((time() - $this->plugin->freeze[$player->getName()]) > $this->plugin->cold){
            	LangManager::send("ce-freeze", $player, $this->plugin->cold);
            }
        } else {
            LangManager::send("ce-freeze", $player, $this->plugin->cold);
        }
        $this->plugin->freeze[$player->getName()] = time();
    }
	
	/**
	 * @param Player $player
     */
	public function blood(Player $player)
	{
        if(isset($this->plugin->bleeding[$player->getName()])){
            if((time() - $this->plugin->bleeding[$player->getName()]) > $this->plugin->bleed){
            	LangManager::send("ce-bleeding", $player, $this->plugin->bleed);
            }
	    } else {
            LangManager::send("ce-bleeding", $player, $this->plugin->bleed);
        }
        $this->plugin->bleeding[$player->getName()] = time();
    }

    /**
     * @return int
     */
    public function getBounty()
    {
        $random = \kenygamer\Core\Main::mt_rand(0, 75);
        $currentchance = 2.5;
        if($random < $currentchance){
            return Item::EMERALD;
        }
        $currentchance += 5;
        if($random < $currentchance){
            return Item::DIAMOND;
        }
        $currentchance += 15;
        if($random < $currentchance){
            return Item::GOLD_INGOT;
        }
        $currentchance += 27.5;
        if($random < $currentchance){
            return Item::IRON_INGOT;
        }
        return Item::COAL;
    }
}