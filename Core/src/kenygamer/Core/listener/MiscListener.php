<?php

declare(strict_types=1);

namespace kenygamer\Core\listener;

//Server events
use pocketmine\event\server\QueryRegenerateEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;

//Entity events
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityMotionEvent;
use pocketmine\event\entity\EntityExplodeEvent;

//Block events
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\BlockSpreadEvent;
use pocketmine\event\block\BlockGrowEvent;
use pocketmine\event\block\BlockBurnEvent;

//Player events
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;

//Inventory events
//use pocketmine\event\inventory\InventoryPickupItemEvent;

//Level events
use pocketmine\event\level\ChunkLoadEvent;
use pocketmine\event\level\ChunkPopulateEvent;

//MCPE packets
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\ResourcePacksInfoPacket;
use pocketmine\network\mcpe\protocol\ResourcePackStackPacket; 
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\TextPacket;
use pocketmine\network\mcpe\protocol\PlayerActionPacket;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\ChangeDimensionPacket;
//use pocketmine\network\mcpe\protocol\PlayStatusPacket;
//use pocketmine\network\mcpe\protocol\DisconnectPacket;
use pocketmine\network\mcpe\protocol\StartGamePacket;
use pocketmine\network\mcpe\protocol\RespawnPacket;
use pocketmine\network\mcpe\protocol\ModalFormResponsePacket;
use pocketmine\network\mcpe\protocol\ModalFormRequestPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\network\mcpe\protocol\NetworkStackLatencyPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsRequestPacket;
use pocketmine\network\mcpe\protocol\ServerSettingsResponsePacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\BatchPacket;
use pocketmine\network\mcpe\protocol\PacketPool;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\SetTitlePacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\AddItemActorPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;

//Entity imports
use pocketmine\entity\Entity;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
//use pocketmine\entity\PrimedTNT;
use pocketmine\entity\Human;
use pocketmine\entity\object\PrimedTNT;
use pocketmine\entity\Attribute;
//use pocketmine\entity\AttributeMap;

//Level imports
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\level\format\Chunk;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\level\particle\SmokeParticle;
//use pocketmine\level\format\EmptySubChunk;
use pocketmine\level\biome\Biome;

//NBT imports
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\CompoundTag;

//Other imports
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\tile\Tile;
use pocketmine\tile\Chest;
use pocketmine\tile\ItemFrame;
use pocketmine\tile\EnchantTable;
use pocketmine\tile\Furnace;
use pocketmine\tile\Skull;
use pocketmine\tile\EnderChest;
use pocketmine\tile\FlowerPot;
use pocketmine\tile\Banner;
use pocketmine\tile\Sign;
use pocketmine\tile\Bed;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\WrittenBook;
use pocketmine\item\Durable;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Random;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\ClosureTask;
use pocketmine\utils\Config;
//use pocketmine\utils\Internet;
use pocketmine\block\Stair;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\math\AxisAlignedBB;

//This plugin imports
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\item\BrewingStand;
use kenygamer\Core\item\ArmorStand;
use kenygamer\Core\entity\EasterEgg;
use kenygamer\Core\entity\HeadEntity;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\task\ReferralUITask;
use kenygamer\Core\task\JoinTask;
use kenygamer\Core\task\SlotsTask;
use kenygamer\Core\task\BossEventTask;
use kenygamer\Core\task\WingsTask;
use kenygamer\Core\item\Elytra;
use kenygamer\Core\item\Shield;
use kenygamer\Core\inventory\TradeInventory;
use kenygamer\Core\vehicle\Vehicle;

//Other plugin imports
use LegacyCore\Core;
use LegacyCore\Events\PlayerEvents;
use LegacyCore\Commands\Fly;
use LegacyCore\Tasks\ScoreHudTask;
use LegacyCore\Events\Area;
use LegacyCore\Commands\Area\ProtectedArea;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\CustomListener;
use muqsit\invmenu\inventory\InvMenuInventory;
use muqsit\invmenu\InvMenu;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\ModalForm;
use revivalpmmp\pureentities\tile\MobSpawner;

final class MiscListener implements Listener{
	//public $isMCPE1460 = [];
	
	/** @var Main */
	public $plugin;
	
	/** @var array */
	public static $referred_playing = [];
	/** @var int[] */
	public static $last_move = [];
	/** @var bool[] */
	private $cookie = [];
	/** @var int[] */
	public $lemon = [];
	/* @var AxisAlignedBB[] */
	public $raids = [];
	/** @var array */
	private $confirmRaid = [];
	/** @var array */
	public $cps = [];
	/** @var array */
	public $comboHits = [];
	/** @var bool[]  */
	private $lobbyparkour = [];
	/** @var bool[] */
	public $playingSlots = [];
	/** @var string[] */
	public $finishedSlots = [];
	/** @var int[] */
	public $combatLogger = [];
	/** @var int[] */
	private $respawnCooldown = [];
	/** @var int[] */
	private $lastCmd = [];
	/** @var int */
	private $lastChat = [];
	/** @var string[] */
	private $tldList = [];
    /** @var string[] */
	private $bannedWords = [];
	/** @var string[] */
	public $placeegg = [];
	/** @var Vector3[] */
	public static $unclaimedEnvoys = [];
	/** @var array */
	private $raining = [];
	/** @var int[] */
	public $changingDimension, $currentDimension, $dimensionChangeCallbacks = []; 
	/** @var int[] */
	private $respawnExp = [];
	/** @var int */
	private $motdIndex = -1;
	/** @var int[] */
	private $startEatTick = [];
	/** @var array<string, <Vector3, int, int> */
	private $sitting = [];
	/** @var string */
	private $guide = "";
	/** @var array<int, array<int, int, int, string>> */
	private $crystals = [];
	/** @var array<string, mixed> */
	public static $pgQueue = [];
	/** @var array<string, int[]>*/
	private $sentCrystals = [];
	/** @var callable[] */
	private $formImagesFix = [];
	/** @var array */
	private $freezeFormData;
	
	private const EXEMPTED_ADVERTS = [
	    "mcpe.life", "mcpe.life/vote1", "mcpe.life/vote2", "mcpe.life/discord", "mcpe.life/vote", "mcpe.life/apply", "mcpe.life/apply4builder", "mcpe.life/apply4mod", "mcpe.life/applyforbuilder", "mcpe.life/applyformod"
	];
	public const CHAT_COMMANDS = [
	    "/me", "/say", "./me", "./say", "/pocketmine:me", "/pocketmine:say"
	];
	
	/**
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin){
		
		$this->plugin = $plugin;
		$this->crystals = [];
		foreach($this->plugin->getConfig()->get("ender-crystals") as $data){
			list($x, $y, $z, $world) = $data;
			$x = (int) round((string) $x);
			$y = (int) round((string) $y);
			$z = (int) round((string) $z);
			$this->crystals[] = [new Vector3($x, $y, $z), $world];
		}
		$content = [];
		$input = ["type" => "input", "text" => "Noob", "placeholder" => "", "default" => \null, "label" => \null];
		for($i = 0; $i < 5000; $i++){
			$content[$i] = $input;
		}
		$this->freezeFormData = ["type" => "custom_form", "title" => "Noob", "content" => $content];

		$this->shop = new Config($plugin->getDataFolder() . "shop.yml", Config::YAML);
		$this->sell = new Config($plugin->getDataFolder() . "sell.yml", Config::YAML);
		
		
		$plugin->saveResource("txt/bannedwords.txt", true);
		$plugin->saveResource("txt/tldlist.txt", true);
		/*$plugin->saveResource("txt/guide.txt", true);
		$this->guide = (new Config($plugin->getDataFolder() . "txt/guide.txt", Config::ENUM))->getAll();
		$this->guide = implode("\n", array_keys($this->guide));*/
		$this->bannedWords = array_keys((new Config($plugin->getDataFolder() . "txt/bannedwords.txt", Config::ENUM))->getAll());
		$this->tldList = array_map(function(string $tld) : string{
			return "." . $tld;
		}, array_keys((new Config($plugin->getDataFolder() . "txt/tldlist.txt", Config::ENUM))->getAll()));
		
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
		
		$plugin->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) : void{
			$plugin = Main::getInstance();
			foreach($plugin->getServer()->getOnlinePlayers() as $player){
				$name = $player->getName();
				if($player->isOnline()){
					//Autorepair
					if((Main::$last_move[$player->getName()] ?? -1) === time()){
						foreach(["getArmorInventory", "getInventory"] as $getter){
							$inventory = $player->{$getter}();
							foreach($inventory->getContents() as $slot => $item){
		                		$enchantment = $item->getEnchantment(CustomEnchantsIds::AUTOREPAIR);
		                		if($enchantment !== null && $item instanceof Durable){
									if($player instanceof Player){
							    		$cost = 1 + 1 * $enchantment->getLevel();
						        		if($player->getCurrentTotalXp() - $cost >= 0 && $item->getDamage() > 0){
		                            		$item->setDamage((int) round(max(0, $item->getDamage() - ($item->getMaxDurability() * $enchantment->getLevel() / 100))));
									    	$player->subtractXp($cost);
		                            		$inventory->setItem($slot, $item);
										}
									}
								}
							}
						}
					}
					
					//Lemons
					if(isset($this->lemon[$player->getName()])){
						$diff = time() - $this->lemon[$player->getName()];
						if($diff < 60){
							$player->addEffect(new EffectInstance(Effect::getEffect(Effect::RESISTANCE), 60, 9));
						}
						if($diff < 50){
							$player->addEffect(new EffectInstance(Effect::getEffect(Effect::REGENERATION), 60, 1));
						}
						if($diff < 600){
							$player->addEffect(new EffectInstance(Effect::getEffect(Effect::FIRE_RESISTANCE), 60, 3));
						}
					}
				}
			}
			
			//Combat Logger
			$core = $plugin->getPlugin("LegacyCore");
			foreach($this->combatLogger as $player => $time){
				$core->loggerTime[$player] = $time - time();
				if(time() >= $time){
					unset($this->combatLogger[$player]);
					unset($core->loggerTime[$player]);
					$pl = $plugin->getServer()->getPlayerExact($player);
					if($pl instanceof Player){
						LangManager::send("combatlogger-expired", $pl);
						$pl->addTitle(LangManager::translate("combatlogger-expired-title-1", $pl), LangManager::translate("combatlogger-expired-title-2", $pl), 15, 15, 15);
					}
				}
			}
		}), 20);
		
		//$plugin->getScheduler()->scheduleRepeatingTask(new BossEventTask($plugin), 20);
	}
	
	/**
	 * @param Player $player
	 * @param bool $newStatus
	 * @param Vector3|null $pos
	 *
	 * @return bool New status is not the same as the old
	 */
	private function setSitting(Player $player, bool $newStatus, ?Vector3 $pos = null) : bool{
		$uuid = $player->getRawUniqueId();
		if($newStatus && !isset($this->sitting[$uuid])){
			if($pos === null){
				throw new \InvalidArgumentException("Argument 3 must be a Vector3 if sitting a player");
			}
			$entityId = Entity::$entityCount++; //won't repeat. bad practice tho?
			$this->sitting[$uuid] = [$pos->floor(), $entityId, time()];
			
			$addentity = new AddActorPacket(); //fake entity
			$addentity->entityRuntimeId = $entityId;
			$addentity->type = AddActorPacket::LEGACY_ID_MAP_BC[113]; //Panda
			$addentity->position = $pos->add(0.5, 1.5, 0.5);
			$flags = (1 << Entity::DATA_FLAG_IMMOBILE | 1 << Entity::DATA_FLAG_SILENT | 1 << Entity::DATA_FLAG_INVISIBLE);
			$addentity->metadata = [Entity::DATA_FLAGS => [Entity::DATA_TYPE_LONG, $flags]];
			
			$link = new EntityLink($fromEntityUniqueId = $entityId, $toEntityUnqiueId = $player->getId(), $type = EntityLink::TYPE_RIDER, true, $causedByRider = false);
			
			$setlink = new SetActorLinkPacket();
			$setlink->link = $link;
			
            $player->setGenericFlag(Entity::DATA_FLAG_RIDING, true);
            
            $players = $this->plugin->getServer()->getOnlinePlayers();
            $this->plugin->getServer()->broadcastPacket($players, $setlink);
            $this->plugin->getServer()->broadcastPacket($players, $setlink);
			return true;
			
		}elseif(!$newStatus && isset($this->sitting[$uuid])){
			$id = $this->sitting[$uuid][1];
			
			unset($this->sitting[$uuid]);
			
			$link = new EntityLink($fromEntityUniqueId = $id, $toEntityUnqiueId = $player->getId(), $type = EntityLink::TYPE_REMOVE, true, $causedByRider = false);
			
			$setlink = new SetActorLinkPacket();
			$setlink->link = $link;
			
			$players = $this->plugin->getServer()->getOnlinePlayers();
			$this->plugin->getServer()->broadcastPacket($players, $setlink);
			
			$removeEntity = new RemoveActorPacket();
			$removeEntity->entityUniqueId = $id;
			$this->plugin->getServer()->broadcastPacket($players, $removeEntity);
			
			$player->setGenericFlag(Entity::DATA_FLAG_RIDING, false);
			return true;
		}else{
			return false;
		}
	}
	
	/**
	 * @param Player $player
	 * @param string $world
	 */
	private	function removeCrystalsNonLevel(Player $player, string $world) : void{
		if($world === ""){
			unset($this->crystals[$player->getName()]);
			return;
		}
		foreach(($this->sentCrystals[$player->getName()] ?? []) as $data){
			list($entityId, $world_) = $data;
			if($world !== $world_){
				$pk = new RemoveActorPacket();
				$pk->entityUniqueId = $entityId;
				$player->sendDataPacket($pk);
			}
		}
	}
	
	/**
	 * @param Player $player
	 */
	private function sendCrystals(Player $player){
		$world = $player->getLevel()->getFolderName();
		foreach($this->crystals as $data){
			list($vec, $world_) = $data;
			if($world === $world_){
				$pk = new AddActorPacket();
				
				$pk->entityUniqueId = $pk->entityRuntimeId = Entity::$entityCount++;
				$pk->type = AddActorPacket::LEGACY_ID_MAP_BC[Entity::ENDER_CRYSTAL];
				$pk->position = $vec;
				$propertyManager = new DataPropertyManager();
				$propertyManager->setFloat(Entity::DATA_SCALE, 1);
				$pk->metadata = $propertyManager->getAll();
				$player->dataPacket($pk);
				if(!isset($this->sentCrystals[$player->getName()])){
					$this->sentCrystals[$player->getName()] = [];
				}
				$this->sentCrystals[$player->getName()][] = [$pk->entityUniqueId, $world];
			}
		}
	}
	/**
	 * @param Position $pos
	 * @return bool
	 */
	private function canEdit(Position $pos) : bool{
	    if(!class_exists(Area::class)){
	    	return false;
	    }
		$cmd = Area::getInstance()->cmd;
		$levelName = $pos->getLevel()->getFolderName();
		foreach($cmd->areas as $area){
			if($area->contains($pos->asVector3(), $levelName) && $area->getFlag("edit")){
				return true;
			}
		}
		return !(isset($cmd->levels[$levelName]) ? $cmd->levels[$levelName]["Edit"] : $cmd->edit);
	}
	
	public function onLeavesDecay(LeavesDecayEvent $event){
		if(!$this->canEdit($event->getBlock())){
			$event->setCancelled();
		}
	}
	
	/**
	 * @param BlockUpdateEvent $event
	 * @priority MONITOR
	 */
	public function onBlockUpdate(BlockUpdateEvent $event){
		if(!$this->canEdit($event->getBlock())){
			$event->setCancelled();
		}
	}
	
	public function onBlockSpread(BlockSpreadEvent $event){
		if(!$this->canEdit($event->getBlock())){
			$event->setCancelled();
		}
	}
	
	public function onBlockGrow(BlockGrowEvent $event){
		if(!$this->canEdit($event->getBlock())){
			$event->setCancelled();
		}
	}
	
	public function onBlockBurn(BlockBurnEvent $event){
		if(!$this->canEdit($event->getBlock())){
			$event->setCancelled();
		}
	}
	
	/**
	 * @param PlayerQuitEvent $event
	 * @priority HIGHEST
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();	
		$name = $player->getName();
		$this->removeCrystalsNonLevel($player, "");
		
		$vehicle = $this->plugin->getVehicle($player);
		if($vehicle !== null){
			$vehicle->removePlayer($player);
		}
		
		$this->setSitting($player, false);
		$this->plugin->getPlayerBossBar($player)->removePlayer($player);
		$event->setQuitMessage("");
		
		$sessionTime = $this->plugin->currentTimeonline[$name] ?? 0;
		$this->plugin->questManager->getQuest("online_4ever")->progress($player, $sessionTime);
		$this->plugin->questManager->getQuest("ancient_player")->progress($player, $sessionTime);
		
		$sessions = $this->plugin->timeonline->get($name, []);
		$sessions[] = [time() - $sessionTime => $sessionTime];
		
		$this->plugin->timeonline->set($name, $sessions);
		unset($this->plugin->currentTimeonline[$name]);
		
		if(!$this->plugin->getServer()->isRunning()){
			unset($this->combatLogger[$name]);
		}
		$player->doCloseInventory(); //Possible fix for cursor inventory losses ?
		
		if(isset($this->combatLogger[$name])){
			unset($this->combatLogger[$name]);
			$player->kill();
		}
		if(($i = array_search($name, $this->plugin->fileReadMode)) !== false){
			unset($this->plugin->fileReadMode[$i]);
		}
		
		if(isset($this->respawnExp[$player->getName()])){
			$player->addXp($this->respawnExp[$player->getName()]);
			unset($this->respawnExp[$player->getName()]);
		}
	}
	
	/**
	 * @param EntityExplodeEvent $event
	 * @ignoreCancelled true
	 */
	public function onEntityExplode(EntityExplodeEvent $event) : void{
		$entity = $event->getEntity();
		$blocks = $event->getBlockList();
		$source = $event->getPosition(); //Floored
		if($entity instanceof PrimedTNT){
			//Reverse size using yield formula. 25 yield = 4 size
			$size = (100 / $event->getYield()) * 2;
			for($xx = -$size; $xx < $size; $xx += 1){
				for($yy = -$size; $yy < $size; $yy += 1){
					for($zz = -$size; $zz < $size; $zz += 1){
						$v = new Vector3(intval($source->getX() + $xx), intval($source->getY() + $yy), intval($source->getZ() + $zz));
						if($entity->getLevel()->getBlockIdAt($v->getX(), $v->getY(), $v->getZ()) === Block::BEDROCK){
							$data = $entity->getLevel()->getBlockDataAt($v->getX(), $v->getY(), $v->getZ());
							$data += ($product = min(15 - $data, \kenygamer\Core\Main::mt_rand(1, 15)));
							if($product !== 0){
								$entity->getLevel()->setBlockDataAt($v->getX(), $v->getY(), $v->getZ(), $data);
							}else{
								$entity->getLevel()->setBlockIdAt($v->getX(), $v->getY(), $v->getZ(), 0);
								$entity->getLevel()->setBlockDataAt($v->getX(), $v->getY(), $v->getZ(), 0);
							}
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param EntityEffectAddEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onEntityEffectAdd(EntityEffectAddEvent $event) : void{
		$entity = $event->getEntity();
		$event->getEffect()->setVisible(false); //Reduce FX/visual lag
		$world = $entity->getLevel()->getFolderName();
		if($entity instanceof Player){
			if($world === "minigames" && !$entity->isOp()){
				$event->setCancelled();
			}
			foreach(Area::getInstance()->cmd->areas as $area){
				if($area->getName() === "effects" && $area->contains($entity->asVector(), $world)){
					$event->setCancelled();
					$entity->removeAllEffects();
					break;
				}
			}
		}
	}
	
	/**
	 * @param Player $player
	 */
	private function onPacketSend(Player $player) : void{
		$timestamp = \kenygamer\Core\Main::mt_rand() * 1000;
		$pk = new NetworkStackLatencyPacket();
		$pk->timestamp = $timestamp;
		$pk->needResponse = true;
		$player->sendDataPacket($pk);
		$this->formImagesFix[$player->getId()][$timestamp] = $callback;
	}
	
	/**
	 * @param Player $player
	 */
	private function formImagesFix2(Player $player) : void{
		$pk = new UpdateAttributesPacket();
		$pk->entityRuntimeId = $player->getId();
		$pk->entries[] = $player->getAttributeMap()->getAttribute(Attribute::EXPERIENCE_LEVEL);
	}
	
	/**
	 * @param QueryRegenerateEvent $event
	 */
	public function onQueryRegenerate(QueryRegenerateEvent $event) : void{
		$online = count($this->plugin->getServer()->getOnlinePlayers());
		$max = $this->plugin->getServer()->getMaxPlayers();
		
		$step = 10;
		$range = array_reverse(range($step, max($step * 2, $max), $step));
		
		for($i = 0; $i < count($range) - 1; $i++){
			if($range[$i] - $online <= $step){
				$event->setMaxPlayerCount($range[$i]);
				break;
			}
		}
		
		$online = strval(count($this->plugin->getServer()->getOnlinePlayers()));
    	$max = strval($this->plugin->getServer()->getMaxPlayers());
		
        /** @var string[] */
        $motd = explode("@&r", implode("@&r", explode("@", LangManager::translate("server-motd"))));
		
        if(!isset($motd[++$this->motdIndex])){
        	$this->motdIndex = 0;
        }
        $msg = $motd[$this->motdIndex];
		$this->plugin->getServer()->getNetwork()->setName(TextFormat::colorize($msg));
	}
	
	public function onPlayerToggleSneak(PlayerToggleSneakEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getInventory()->getItemInHand() instanceof Shield){
			if($event->isSneaking()){
				$player->setGenericFlag(Entity::DATA_FLAG_BLOCKING, true);
			}else{
				$player->setGenericFlag(Entity::DATA_FLAG_BLOCKING, false);
			}
		}
	}
	
	/**
	 * @param DataPacketSendEvent $event
	 */
	public function onDataPacketDebug(DataPacketSendEvent $event){
		$pk = $event->getPacket();
		$player = $event->getPlayer();
		/*if($pk->getName() === "PlayerListPacket" && $this->isMCPE1460[$player->getUniqueId()->toString()]){
		    $event->setCancelled();
		}*/
	}
	
	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event){
		$player = $event->getPlayer();
		$pk = $event->getPacket();
		/* Support Waterdog {@see https://github.com/yesdog/Waterdog} - does not support Razer's Synapse cloud-based config software for controls/macros (key sequences) */
		if($pk instanceof LoginPacket){
			foreach($this->plugin->getServer()->getNetwork()->getInterfaces() as $interface){
				if($interface instanceof RakLibInterface){
					try{
						$reflector = new \ReflectionProperty($interface, "interface");
						$reflector->setAccessible(\true);
						$reflector->getValue($interface)->sendOption("packetLimit", 900000000000);
					}catch (\ReflectionException $e){
					}
				}
			}
			if(isset($pk->clientData["WaterDog_RemoteIP"])){
				$class = new \ReflectionClass($player);
				$prop = $class->getProperty("ip");
				$prop->setAccessible(true);
				$prop->setValue($event->getPlayer(), $pk->clientData["WaterDog_RemoteIP"]);
			}
		}
		
	 	/*if($pk instanceof LoginPacket){
	 		$this->isMCPE1460[$pk->clientUUID] = $pk->protocol === 390;
	 	}*/
		
		//Vehicles
		if($pk instanceof PlayerInputPacket){
			if($player->getName() === "XxKenyGamerxX"){
				//echo "[PLAYERINPUTPACKET: {$pk->motionX} {$pk->motionY}]\n";
			}
			[$motionX, $motionY] = [$pk->motionX, $pk->motionY];
			$vehicle = $this->plugin->getVehicle($player);
			if($vehicle !== null){
				$event->setCancelled();
				if($motionX === 0.0 && $motionY === 0.0){
					return; //Useless pk
				}
				if(($driver = $vehicle->getDriver()) !== null && $driver->getRawUniqueId() === $player->getRawUniqueId()){
					if($player->getName() === "XxKenyGamerxX"){
						//echo " - UPDATE MOTION ";
					}
					$vehicle->updateMotion($motionX, $motionY);
				}
			}
			if($player->getName() === "XxKenyGamerxX"){
				//echo "- END\n";
			}
		}
		if($pk instanceof InteractPacket && $pk->action === InteractPacket::ACTION_LEAVE_VEHICLE){
			$vehicle = $player->getLevel()->getEntity($pk->target);
			if($vehicle !== null){
				$vehicle->removePlayer($player);
				$event->setCancelled();
			}
		}
		
		if($pk instanceof ActorEventPacket){
			if($pk->event === ActorEventPacket::EATING_ITEM){ //This gets sent several times tho
				$this->startEatTick[$player->getName()] = $player->getServer()->getTick();
			}
		}
		if(!($pk instanceof BatchPacket)){
			//var_dump($pk->getName());
		}
		if($pk instanceof InventoryTransactionPacket){
			//var_dump($pk);
	    }
	 	$isWin10 = PlayerEvents::OS_LIST[PlayerEvents::getPlayerData($player)["DeviceOS"]] === "Windows 10";
	 	if($pk instanceof BatchPacket){
	 		try{
	 		    $pk->decode();
	 			$count = 0;
	 			foreach($pk->getPackets() as $buf){
	 				$packet = PacketPool::getPacket($buf);
	 				if($packet instanceof InventoryTransactionPacket){
	 					$packet->decode();
	 					if($packet->transactionType === InventoryTransactionPacket::USE_ITEM_ACTION_BREAK_BLOCK){
	 						if($count++ > 40){ //&& !$isWin10
								$this->freezeMc($player);
	 					//		$player->sendMessage("nuking-disabled");
	 				//			$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
	 			//				$this->plugin->registerWarn($player, 2, "CONSOLE", false);
	 		//					$event->setCancelled();
	 							break;
	 						}
	 					}
	 				}
	 			}
	 			if($count > 40){
	 				//var_dump($count);
	 			}
	 		}catch(\Exception $e){ //Cant catch UnexpectedValueException!
				$this->freezeMc($player);
	 		    if(!$isWin10){
	 		   // 	$player->sendMessage("nuking-disabled");
	 		//    	$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
	 	//	    	$this->plugin->registerWarn($player, 2, "CONSOLE", false);
	 		    }
	 		}
	 	}
	 	if($pk instanceof PlayerActionPacket){
	 		switch($pk->action){
	 			case PlayerActionPacket::ACTION_START_SWIMMING:
	 			    $player->setGenericFlag(Player::DATA_FLAG_SWIMMING, true);
	 			    break;
	 			case PlayerActionPacket::ACTION_STOP_SWIMMING:
	 			    $player->setGenericFlag(Player::DATA_FLAG_SWIMMING, false);
	 			    break;
	 			case PlayerActionPacket::ACTION_START_GLIDE:
	 			    $player->setGenericFlag(Player::DATA_FLAG_GLIDING, true);
	 			    break;
	 			case PlayerActionPacket::ACTION_STOP_GLIDE:
	 			    $player->setGenericFlag(Player::DATA_FLAG_GLIDING, false);
	 			    if(!$player->isAlive() || !$player->isSurvival()){
	 			    	break;
	 			    }
	 			    $elytra = $player->getArmorInventory()->getChestplate();
	 			    if($elytra instanceof Elytra){
	 			    	$elytra->applyDamage(1);
	 			    }
	 			    break;
	 			case PlayerActionPacket::ACTION_DIMENSION_CHANGE_ACK:
	 			    if(isset($this->changingDimension[$player->getName()])){
						
	 			    	$this->currentDimension[$player->getName()] = $this->changingDimension[$player->getName()];
	 			    	if(isset($this->dimensionChangeCallbacks[$player->getName()])){
	 			    		$callbacks = $this->dimensionChangeCallbacks[$player->getName()];
	 			    		unset($this->dimensionChangeCallbacks[$player->getName()]);
	 			    		$this->plugin->scheduleDelayedCallbackTask(function() use($callbacks, $player){
	 			    			foreach($callbacks as $callback){
	 			    				$callback();
	 			    			}
								unset($this->changingDimension[$player->getName()]);
	 			    		}, 25);
							
	 			    	}else{
							unset($this->changingDimension[$player->getName()]);
	 			    	}
	 			    }
	 			    break;
	 		}
	 	}
	 	if($pk instanceof ServerSettingsRequestPacket){
	 		$packet = new ServerSettingsResponsePacket();
			
	 		$packet->formData = json_encode([
	 		    "type" => "custom_form",
	 		    "title" => "Display Settings",
	 		    "icon" => [
	 		        "type" => "url",
	 		        "data" => "https://u.cubeupload.com/kenygamer/iconfinderSettings14.png"
	 		    ],
	 		    "content" => [
	 		        [
	 		            "type" => "toggle",
	 		            "text" => "HUD",
	 		            "default" => $this->plugin->settings->getNested($player->getName() . ".hud", true)
	 		        ], [
	 		            "type" => "dropdown",
	 		            "text" => "Scoreboard",
	 		            "options" => [
	 		                "Off",
	 		                "Normal",
	 		                "Faction"
	 		            ],
	 		            "default" => $this->plugin->settings->getNested($player->getName() . ".scoreboard", 1)
	 		        ], [
	 		            "type" => "toggle",
	 		            "text" => "Compass",
	 		            "default" => $this->plugin->settings->getNested($player->getName() . ".compass", true)
	 		        ]
	 		    ]
	 		]);
	 		$packet->formId = 1101;
	 		$player->directDataPacket($packet);
	 	}
		
		// Form Images Fix - https://github.com/Muqsit/FormImagesFix
		if($pk instanceof NetworkStackLatencyPacket && isset($this->formImagesFix[$player->getName()][$pk->timestamp])){
			$callback = $this->formImagesFix[$player->getName()][$pk->timestamp];
			unset($this->formImagesFix[$player->getName()][$pk->timestamp]);
			if(count($this->formImagesFix[$player->getName()]) === 0){
				unset($this->formImagesFix[$player->getName()]);
			}
			$callback();
		}
		if($pk instanceof ModalFormRequestPacket){ 
			$this->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($player) : void{
				if($player->isOnline()){
					$this->formImagesFix1($player, function() use($player) : void{
						if($player->isOnline()){
							$this->formImagesFix2($player);
							$player->sendDataPacket($pk);
							$times = 1;
							$handler = $this->getScheduler()->scheduleRepeatingTask(new ClosureTask(function(int $currentTick) use($player, $times, &$handler) : void{
								if(--$times >= 0 && $player->isOnline()){
									$this->formImagesFix2($player);
								}else{
									$handler->cancel();
								}
							}), 10);
						}
					});
				}
			}), 1);
		}
		
	 	if($pk instanceof ModalFormResponsePacket){ 
	 	    if($pk->formId === 1101){
	 	    	$data = (array) json_decode($pk->formData, true);
				//var_dump($data);
	 	    	if(count($data) === 3 && array_values($this->plugin->settings->get($player->getName(), [true, true, true])) !== $data){
	 	    		$this->plugin->settings->setNested($player->getName() . ".hud", $data[0]);
	 	    		$this->plugin->settings->setNested($player->getName() . ".scoreboard", $data[1]);
	 	    		if(!$data[1]){
	 	    		    ScoreHudTask::getInstance()->rmScoreboard($player, "objektName");
	 	    		}
	 	    		if(!$data[2]){
	 	    			$this->plugin->getPlayerBossBar($player)->removePlayer($player);
	 	    		}else{
	 	    			$this->plugin->getPlayerBossBar($player)->addPlayer($player);
	 	    		}
	 	    		$this->plugin->settings->setNested($player->getName() . ".compass", $data[2]);
	 	    		LangManager::send("settings", $player, ($data[0] ? "{on}" : "{off}"), ($data[1] ? "{on}" : "{off}"), ($data[2] ? "{on}" : "{off}"));
	 	    	}
	 	    }
	 		switch(gettype($pk->formData)){
	 			case "array":
	 			    $data = json_decode($pk->formData);
	 			    foreach($data as &$label){
	 			    	if(is_string($label)){
	 			    		$this->plugin->interpretUnits($label);
	 			    	}
	 			    }
	 			    $pk->formData = json_encode($data);
	 			    break;
	 			case "string":
	 			    if(is_numeric($pk->formData)){
	 			    	break;
	 			    }
	 			    $data = $pk->formData;
	 			    $this->plugin->interpretUnits($data);
	 			    $pk->formData = $data;
	 			    break;
	 		}
			//var_dump($pk->formData);
	 	}
	}
	
	private function freezeMc(Player $player) : void{
		$form = new class implements \pocketmine\form\Form{
			public $data;
			public function handleResponse(Player $player, $data) : void{
			}
			public function jsonSerialize(){
				return $this->data;
			}
		};
		$form->data = $this->freezeFormData;
		$player->sendForm($form);
	}
	
	/**
	 * Possible fix for #42 ?
	 * @param EntityDeathEvent $event
	 * @priority MONITOR
	 */
	public function onEntityDeath(EntityDeathEvent $event){
		$entity = $event->getEntity();
		$drops = $event->getDrops();
		$event->setDrops(array_filter($drops, function(Item $drop) : bool{
			return !$drop->isNull();
		}));
	}
	
	/**
	 * @param PlayerLoginEvent $event
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		if(isset(Main::$giveawayStatus[0]) && Main::$giveawayStatus[0]){
			foreach($this->plugin->getServer()->getOnlinePlayers() as $pl){
				//PM checks username and Xbox UID
				if($pl->getAddress() === $player->getAddress() && $pl->getName() !== $player->getName()){
					$event->setCancelled();
					$event->setKickReason(LangManager::translate("giveaway-alt", $player));
					break;
				}
			}
		}
		if($this->plugin->isBanned($player)){
    		$msg = LangManager::translate("ban-kick", $player);
    		$time = $this->plugin->getBanTime($player);
    		if($time === -1){
    			$msg .= LangManager::translate("ban-kick-permanent", $player);
    		}else{
    			$msg .= LangManager::translate("ban-kick-temporary", $this->plugin->formatTime($this->plugin->getTimeLeft(time() + $time)));
    		}
    		$event->setKickMessage($msg);
    		$event->setCancelled();
    	}
	}
	
	/**
	 * @param PlayerKickEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerKick(PlayerKickEvent $event) : void{
		$player = $event->getPlayer();
		$reason = $event->getReason();
		if($event->getReason() === "Attempting to attack an invalid entity" || ($player->getGamemode() === Player::SPECTATOR && $reason === $this->plugin->getServer()->getLanguage()->translateString("kick.reason.cheat", ["%ability.flight"]))){
			$event->setCancelled();
		}
	}
	
	/**
	 * @param Player $player
	 */
	private function setRaining(Player $player) : void{
		//echo "[SET RAINING! " . $player->getName() . "]\n";
		$this->plugin->getScheduler()->scheduleDelayedTask(new ClosureTask(function(int $currentTick) use($player) : void{
			if(!$player->isOnline()){
				return;
			}
			$pk = new LevelEventPacket();
			$pk->evid = LevelEventPacket::EVENT_START_RAIN;
			$pk->position = $player->asVector3();
			$pk->data = 999999;
			$player->dataPacket($pk);
			
			$this->raining[$player->getName()] = true;
		}), 50);
	}
	
	/**
	 * @todo
	 * This will be removed in PM4
	 * @param EntityLevelChangeEvent $event
	 * @ignoreCancelled true
	 */
	public function onEntityLevelChange(EntityLevelChangeEvent $event) : void{
		$entity = $event->getEntity();
		if(!($entity instanceof Player)){
			return;
		}
		
		$origin = $event->getOrigin();
		$target = $event->getTarget();

			
		if(in_array($target->getFolderName(), $this->plugin->rainWorlds)){
			if(true){ //if(!isset($this->raining[$entity->getName()])){
				//Winter storm: Ice plains biome + empty world preset
				$this->setRaining($entity);
			}
		}elseif(isset($this->raining[$entity->getName()])){
			$pk = new LevelEventPacket();
			$pk->evid = LevelEventPacket::EVENT_STOP_RAIN;
			$pk->position = $entity->asVector3();
			$pk->data = 0;
			$entity->dataPacket($pk);
			unset($this->raining[$entity->getName()]);
		}
		if(!$this->plugin->isVip($entity) && $event->getTarget()->getFolderName() === "vipworld"){
			LangManager::send("only-vip", $entity);
			$event->setCancelled();
			return;
		}
		$this->sendCrystals($entity);
		$this->removeCrystalsNonLevel($entity, $target->getFolderName());
	}
	
	/**
	 * @param EntityTeleportEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		$to = $event->getTo();
		
		if($player instanceof Player){
			$vehicle = $this->plugin->getVehicle($player);
			if($vehicle !== null){
				$vehicle->removePlayer($player);
			}
		
			//TP Particles
			$random = new Random(\kenygamer\Core\Main::mt_rand());
		    $particle = new SmokeParticle(new Vector3($player->getX(), $player->getY() + 0.7, $player->getZ()), 200);
		    for ($i = 0; $i < 35; ++$i){
		    	$particle->setComponents($player->getX() + $random->nextSignedFloat(), $player->getY() + $random->nextSignedFloat(), $player->getZ() + $random->nextSignedFloat());
		    	$player->getLevel()->addParticle($particle);
		    }
		    
		    if($to->getLevel() !== null){
		    	$oldDimension = $this->currentDimension[$player->getName()] ?? DimensionIds::OVERWORLD;
		    	$newDimension = $this->plugin->getWorldDimension($toWorld = $to->getLevel()->getFolderName());
		    	
		    	if(!isset($this->changingDimension[$player->getName()]) && $oldDimension !== $newDimension && $toWorld !== $player->getLevel()->getFolderName()){
		    		$pk = new ChangeDimensionPacket();
		    		$pk->respawn = !$player->isAlive();
		    		$pk->dimension = $newDimension;
		    		$pk->position = $to->asVector3();
		    		$player->directDataPacket($pk);
		    		
		    		$this->changingDimension[$player->getName()] = $newDimension;
		    		$this->dimensionChangeCallbacks[$player->getName()][] = function() use($player){
	 			    	if($this->plugin->settings->getNested($player->getName() . ".compass", true)){
	 			    		$bossbar = $this->plugin->getPlayerBossBar($player);
							$bossbar->removePlayer($player);
	 			    		$bossbar->addPlayer($player);
	 			    	}
	 			    	$pk = new LevelEventPacket();
	 			    	$pk->position = $player->asVector3();
						$pk->evid = LevelEventPacket::EVENT_SOUND_PORTAL;
	 			    	$pk->data = 0;
	 			    	$player->dataPacket($pk);

	 			    };
		    	}
		    }
		    $this->plugin->scheduleDelayedCallbackTask(function() use($player){
				if($player->isOnline()){
					//TODO: Parkour
					if($player->getLevel()->getFolderName() === "hub" && $player->getGamemode() % 2 === 0){
						$player->setAllowFlight(false);
						$player->setFlying(false);
						unset(Fly::$hasFliedInHub[$player->getName()]);
					}
				}
			}, 20);
		}
	}
	
	/**
	 * @param PlayerMoveEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$player = $event->getPlayer();
		$to = $event->getTo();
		$from = $event->getFrom();
		$wasSolid = $player->isInsideOfSolid();
		
		//Sitting - wont disable on jump..
		if(isset($this->sitting[$player->getRawUniqueId()]) && time() - $this->sitting[$player->getRawUniqueId()][2] > 1 && $to->getY() !== $event->getFrom()->getY()){
		    $this->setSitting($player, false);
		}
		
		//Anti Cheat
		[$player->x, $player->y, $player->z] = [$to->getX(), $to->getY(), $to->getZ()]; //Set temporalily to check clipping through blocks
		if($player->isInsideOfSolid() && $player->getGamemode() !== Player::SPECTATOR){
			if($wasSolid){
				$spawn = $this->plugin->actuallySafeSpawn($player->asPosition());
				$y = 0;
				while($player->getLevel()->getBlock($spawn->add(0, $y, 0))->isSolid()){
					$y++;
					if($y > Level::Y_MAX){
						break;
					}
				}
				$spawn = $this->plugin->actuallySafeSpawn($player->getLevel());
				$event->setTo(Location::fromObject($spawn->asVector3(), $spawn->getLevel(), 0.0, 0.0));
			}
		}
		[$player->x, $player->y, $player->z] = [$from->getX(), $from->getY(), $from->getZ()];
		
		if(isset($this->plugin->freezes[$player->getName()])){
			if(!(time() >= $this->plugin->freezes[$player->getName()])){
            	$player->sendMessage("frozen", $this->plugin->formatTime($this->plugin->getTimeLeft($this->plugin->freezes[$player->getName()]), TextFormat::WHITE, TextFormat::WHITE));
            	$event->setCancelled();
            	return;
        	}
        	unset($this->plugin->freezes[$player->getName()]);
		}
		
		Main::$last_move[$player->getName()] = time();
		
		if($player->getY() < -2){
			if(isset($this->combatLogger[$player->getName()])){
				$player->kill();
				LangManager::send("combatlogger-escapevoid", $player);
			}else{
				if($player->getLevel()->getFolderName() === "minigames"){
					$data = $this->plugin->skyjump->get($player->getName(), []);
					$keys = array_keys($data);
					$lastSetpoint = !empty($data) ? end($keys) : null;
					if(isset($this->plugin->skyjumpSetpoints[$lastSetpoint - 1])){
						$player->teleport($this->plugin->skyjumpSetpoints[$lastSetpoint - 1]);
						$player->sendMessage("skyjump-voidtp");
						return;
					}
				}
				$player->teleport($this->plugin->actuallySafeSpawn($player->getLevel()));
				LangManager::send("void-tp", $player);
			}
		}
		
		//Bouncing blocks!
		if($player->getLevel()->getFolderName() === "hub" && $player->getLevel()->getBlock($player->subtract(0, 1, 0))->getId() === Block::REDSTONE_ORE){
			$v = $player->getDirectionVector();
			$v->y = 0;
			$player->setMotion($v->multiply(2));
		}
		
		/** @var string */
		$level = $player->getLevel()->getFolderName();
		/** @var ProtectedArea[] */
		$areas = Area::getInstance()->cmd->areas;
		
		$to = $event->getTo()->asVector3();
		$from = $event->getFrom()->asVector3();
		if(in_array($level, ["hub", "warzone", "prison"])){
			$valid = false;
			$validPrev = false;
			foreach($areas as $area){
				//Premium mine check. TODO: unhardcode and move to EntityTeleportEvent
				if(!$player->hasPermission("core.command.vip") && in_array($area->getName(), [
				    "premiummine", "premiumminedownside", "premiummineupside",
				    "premiummineside1", "premiummineside2", "premiummineside3",
				    "premiummineside4" 
				]) && $area->contains($to, "prison") && !$player->hasPermission("core.bypass.mines")){
					$event->setCancelled();
					LangManager::send("only-vip", $player);
					$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
					break;
				}
				
				//In-area check
				$bb = (Main::createBB($area->getFirstPosition(), $area->getSecondPosition()))->expandedCopy(1, 1, 1); //hack
				if($area->contains($to, $level) || ($area->getLevelName() === $level && $bb->isVectorInside($to))){
					$valid = true;
				}
				if($area->contains($from, $level) || ($area->getLevelName() === $event->getFrom()->getLevel()->getFolderName() && $bb->isVectorInside($from))){
					$validPrev = true;
				}
			}
			if(!$valid && Core::$snapshot !== ""){
				$event->setCancelled();
				$player->sendPopup(LangManager::translate("area-exit", $player));
				if(!$validPrev){
					$player->teleport($this->plugin->actuallySafeSpawn($player->getServer()->getDefaultLevel()));
				}
			}
		}
		if(!$event->isCancelled()){
			$this->plugin->questManager->getQuest("the_athlete")->progress($player, 1);
		}
	}
	
	/**
	 * @param DataPacketSendEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		
	//	ob_start();
		//echo "[HANDLING SEND]\n";
		$pk = $event->getPacket();
		/*var_dump(get_class($pk));
		try{
			throw new \Exception();
		
		}catch(\Exception $e){
			echo $e->getTraceAsString() . PHP_EOL;
		}
		*/
		$player = $event->getPlayer();
		$name = $player->getName();
		//echo "[AFTER GETTERS]\n";
		//Usually three SetTitlePacket's per Player::addTitle(): duration, subtitle and title
		if($pk instanceof SetTitlePacket && isset($this->changingDimension[$player->getName()])){
			//echo "[ SET TITLE]\n";
			$event->setCancelled();
			$this->dimensionChangeCallbacks[$player->getName()][] = function() use($player, $pk){
				$player->dataPacket($pk);
			};
		}
		//echo "[OK]\n";
		if($pk instanceof TextPacket && ($pk->type === TextPacket::TYPE_RAW or $pk->type === TextPacket::TYPE_TRANSLATION or $pk->type === TextPacket::TYPE_WHISPER)){
			//echo "[OK 2]\n";
			if(in_array($name, $this->plugin->fileReadMode)){
				$event->setCancelled();
			}
		}
		//echo "[OK 3]\n";
		if($pk instanceof StartGamePacket){
			//echo "[OK 4]\n";
			$newDimension = $this->changingDimension[$player->getName()] ?? null;
			if($newDimension === null){ //First spawn
			    $newDimension = $this->worldDimensions[$player->getLevel()->getFolderName()] ?? DimensionIds::OVERWORLD;
			}
			$prop = new \ReflectionProperty($pk->spawnSettings, "dimension");
			$prop->setAccessible(true);
			if($prop->getValue($pk->spawnSettings) !== $newDimension){
				//echo "[OK 5]\n";
				$prop->setValue($pk->spawnSettings, $newDimension);
				//If this is the StartGamePacket that is sent in the login sequence, we will have to set the dimension manually. 
				//We will not receive an ACK because that is for the DimensionChangePacket.
				$this->currentDimension[$player->getName()] = $newDimension;
				//echo "[OK 6]\n";
			}
		}
		//echo  "[HMM]\n";
	//	$buffer = ob_get_clean();
		if(Core::$snapshot !== ""){
		}
		//echo $buffer;
	}
	
	/**
	 * @param BlockPlaceEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$item = $event->getItem();
		
		if($this->plugin->landManager->getLand2($block) && !$player->isOp()){
			$event->setCancelled();
		}
		if(!$event->isCancelled() && $item->getId() === Item::MOB_HEAD and ($blockData = $item->getCustomBlockData()) !== null){
			$event->setCancelled();
			$chunk = $block->getLevel()->getChunk($block->getX() >> 4, $block->getZ() >> 4);
			
			$TILE_TO_BLOCK_MAPPING = [
				Chest::class => [Block::CHEST, Block::TRAPPED_CHEST],
				Bed::class => [Block::BED_BLOCK],
				Banner::class => [Block::WALL_BANNER],
				EnchantTable::class => [Block::ENCHANTING_TABLE],
				EnderChest::class => [Block::ENDER_CHEST],
				FlowerPot::class => [Block::FLOWER_POT],
				Furnace::class => [Block::FURNACE],
				ItemFrame::class => [Block::ITEM_FRAME],
				Sign::class => [Block::SIGN_POST],
				Skull::class => [Block::SKULL],
				BrewingStand::class => [Block::BREWING_STAND],
				ArmorStand::class => [Block::ARMOR_STAND],
				MobSpawner::class => [Block::MOB_SPAWNER],
				HeadEntity::class => [Block::SKULL]
			];
			
			$tileCount = 0;
			$isConstrained = 0;
			foreach($chunk->getTiles() as $tile){
				foreach($TILE_TO_BLOCK_MAPPING as $tile_ => $blocks){
					if($tile instanceof $tile_){
						$tileCount++;
					}
					foreach($blocks as $block_){
						if($block_->getId() === $block->getId()){
							$isConstrained = true;
							break;
						}
					}
				}
			}
			
			if($tileCount + ($isConstrained ? 1 : 0) > 64){
				LangManager::send("tile-limit", $player, $block->getVanillaName());
				$event->setCancelled();
				return;
			}
			
			$nbt = Entity::createBaseNBT($block->add(0.5, 0, 0.5), null, $this->plugin->getDirection($player->getYaw()));
            $blockData->setName("Skin");
			$nbt->setTag($blockData);
            $head = new HeadEntity($player->getLevel(), $nbt);
            $head->spawnToAll();
			if(!$player->isCreative()){
				$player->getInventory()->setItemInHand($item->setCount($item->getCount() - 1));
			}
		}
		if($item->getId() === Item::SKULL && $item->getNamedTagEntry("Skull")){
			$event->setCancelled();
		}
		
		foreach($this->raids as $area){
			$boundingBox = $area[0];
			$level = $area[1];
			$raider = $area[4];
			if($boundingBox->isVectorInside($block) && $level->getFolderName() === $player->getLevel()->getFolderName()){
				if($player->getName() !== $raider){
					LangManager::send("area-raided-2", $player, $raider);
					$event->setCancelled();
					return;
				}
			}
		}
		if(!$event->isCancelled()){
			$this->plugin->registerEntry($player->getName(), Main::ENTRY_BLOCKS_PLACED);
		}
	}
	
	/**
	 * @param BlockBreakEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	 public function onBlockBreak2(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$level = $block->getLevel();
		
		//Sitting
		foreach($this->sitting as $uuid => $d){
			if($d[0]->equals($event->getBlock()->floor())){
				foreach($this->plugin->getServer()->getOnlinePlayers() as $player){
					if($player->getRawUniqueId() === $uuid){
						$this->setSitting($player, false);
						break 2;
					}
				}
			}
		}
		
		//Quests
		if($block->getId() === Block::WHEAT_BLOCK){
			$this->plugin->questManager->getQuest("harvester")->progress($player, 1);
		}
		if($block->getId() === Block::WOOD){
			$this->plugin->questManager->getQuest("tree_cutter")->progress($player, 1);
		}
		
		// Disable EXP orbs
		$event->getPlayer()->addXp($event->getXpDropAmount());
		$event->setXpDropAmount(0);
		
		// Put drops in the player inventory
		if($block->getId() === Block::CHEST){
			$tile = $level->getTile($block);
			if($tile instanceof Chest){
				$event->setCancelled();
				$items = $tile->getRealInventory()->getContents(); 
				if($this->plugin->testSlot($player, count($items))){
					$tile->unpair();
					$tile->close();
					$level->setBlock($block, BlockFactory::get(Block::AIR));
					if(count($items) > 0){
						foreach($items as $i){
							$player->getInventory()->addItem($i);
						}
						LangManager::send("items-collected", $player, count($items));
					}
				}
			}
		}
		$drops = $event->getDrops();
		foreach($drops as $k => $drop){
			if($player->getInventory()->canAddItem($drop)){
				$player->getInventory()->addItem($drop);
            }
        }
        $event->setDrops([]);
		
		//Register block break
		$this->plugin->registerEntry($player, Main::ENTRY_BLOCKS_BROKEN);
	}
		
	/**
	 * @param BlockBreakEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	 public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		
		if($this->plugin->landManager->getLand2($block) && !$player->isOp()){
			$event->setCancelled();
		}
		
		foreach($this->raids as $area){
			$boundingBox = $area[0];
			$level = $area[1];
			$raider = $area[4];
			if($boundingBox->isVectorInside($block) && $level->getFolderName() === $player->getLevel()->getFolderName()){
				if($player->getName() !== $raider){
					LangManager::send("raid-raided-2", $player, $raider);
					$event->setCancelled();
					return;
				}
			}
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onPlayerInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$level = $block->getLevel();
		$item = $event->getItem();
		$x = $block->getX();
		$y = $block->getY();
		$z = $block->getZ();
		$action = $event->getAction();
		
		if(($item->getId() === Item::MOB_SPAWNER && $item->getNamedTag()->getInt("EntityId", -1) === Entity::POLAR_BEAR) || ($block->getId() === Block::MOB_SPAWNER && $item->getId() === Item::SPAWN_EGG && $item->getDamage() === 102)){ //Thx mojang for hardcoding damage
			$player->sendMessage("spawn-egg-only");
			$event->setCancelled();
			return;
		}
		
		//Sitting
		if(isset($this->sitting[$player->getRawUniqueId()])){
			$this->setSitting($player, false);
		}elseif($block instanceof Stair && $player->isSneaking()){
			$this->setSitting($player, true, $block->asVector3());
		}
		
		//Lands
		$land = $this->plugin->landManager->getLandBySign($block);
		if($land !== null && in_array($block->getId(), [Block::SIGN_POST, Block::WALL_SIGN]) && $this->plugin->getServer()->getLevelByName($land->world)->getTile($block) instanceof Sign && $land->sign !== null && !$land->isOwned()){
			$owned = 0;
			foreach($this->plugin->landManager->getAll() as $l){
				if($l->owner === mb_strtolower($player->getName())){
					$owned++;
				}
			}
			if($owned > 0 and ($owned >= $this->plugin->getConfig()->getNested("land.land-limit") && !$player->isOp())){
				$player->sendMessage("land-buy-limit");
		    }else{
		    	if($player->reduceMoney($land->price)){
		    		$land->owner = mb_strtolower($player->getName());
		    		$land->lastPayment = time();
					$player->sendMessage("land-buy", number_format($land->price));
		    	}else{
					$player->sendMessage("money-needed-more", number_format($land->price - $player->getMoney()));
				}
			}
			if(!$player->isOp()) $event->setCancelled();
		}
		
		$land = $this->plugin->landManager->getLand2($block);
		if($land !== null){
			if(!$land->isHelper($player->getName()) && !$player->isOp()){
				if($land->isOwned()){
					$player->sendMessage("land-notinvitee", $land->owner);
				}
				$event->setCancelled();
				return;
			}else{
				$event->setCancelled(false);
			}
			if($block->getId() === Block::CHEST && ($chest = $level->getTile($block)) instanceof Chest){
				$this->plugin->unlockChest($block);
				if(($pair = $chest->getPair()) instanceof Chest){
					$this->plugin->unlockChest($pair);
				}
			}
		}
		
		//Locked chests
		if($block->getId() === Block::CHEST){
			$info = $this->plugin->getChestInfo($block);
        	$pairChestTile = null;
        	if(($tile = $level->getTile($block)) instanceof Chest){
				$pairChestTile = $tile->getPair();
			}
        	if(isset(self::$pgQueue[$player->getName()])){
				$event->setCancelled();
            	$task = self::$pgQueue[$player->getName()];
            	$taskName = array_shift($task);
            	switch($taskName){
                	case "lock":
						var_dump($info);
                    	if($info->attribute === Main::PG_NOT_LOCKED){
                        	$this->plugin->lockChest($block, $player);
                        	if($pairChestTile instanceof Chest){
								$this->plugin->lockChest($pairChestTile, $player);
                   			}
							$player->sendMessage("pg-lock");
						}else{
							$player->sendMessage("pg-locked");
						}
                    	break;
                	case "unlock":
                    	if($info->owner === $player->getName() && $info->attribute === Main::PG_NORMAL_LOCK){
                        	$this->plugin->unlockChest($block);
                        	if($pairChestTile instanceof Chest){
								$this->plugin->unlockChest($pairChestTile);
							}
							$player->sendMessage("pg-unlock");
						}else{
                    		$player->sendMessage("pg-notnormal");
						}
						break;
                	case "info":
                    	if($info->attribute !== Main::PG_NOT_LOCKED){
                        	switch($info->attribute){
                            	case Main::PG_NORMAL_LOCK:
                                	$attribute = "Normal";
                                	break;
                            	case Main::PG_PASSCODE_LOCK:
                                	$attribute = "Passcode";
                                	break;
                        	}
                        	$player->sendMessage("pg-info", $info->owner, $attribute);
						}else{
							$player->sendMessage("pg-notlocked");
						}
                    	break;
                	case "passlock":
                    	if($info->attribute === Main::PG_NOT_LOCKED){
                        	$passcode = array_shift($task);
                        	$this->plugin->lockChest($block, $player, $passcode);
                        	if($pairChestTile instanceof Chest){
								$this->plugin->lockChest($pairChestTile, $player, $passcode);
							}
                        	$player->sendMessage("pg-passlock", $passcode);
                        }else{
                        	$player->sendMessage("pg-locked");
                    	}
                    	break;
                	case "passunlock":
                    	if($info->attribute === Main::PG_PASSCODE_LOCK){
                        	$passcode = array_shift($task);
                        	if($info->passcode === $passcode){
                            	$this->plugin->unlockChest($block);
                            	if($pairChestTile instanceof Chest){
									$this->plugin->unlockChest($pairChestTile);
								}
                            	$player->sendMessage("pg-unlock");
                        	}else{
                            	$player->sendMessage("pg-unlockerror");
							}
						}else{
							$player->sendMessage("pg-notpasscode");
						}
                    	break;
				}
            	unset(self::$pgQueue[$player->getName()]);
        	}elseif($info->attribute !== Main::PG_NOT_LOCKED && $info->owner !== $player->getName() && !$player->isOp()){
				$event->setCancelled();
            	$player->sendMessage("pg-open");
			}
		}
		
		foreach([1, 2, 3] as $pos){
			$property = "landPos" . $pos;
			if(isset($this->plugin->{$property}[$player->getName()]) && !is_array($this->plugin->{$property}[$player->getName()])){
				if($pos === 3){
					if(!($level->getTile($block->asVector3()) instanceof Sign)){
						$player->sendMessage("clicksign");
						break;
					}
				}
				if($x === 0 && $y === 0 && $z === 0){
					//TODO: break not supported
				}
				$this->plugin->{$property}[$player->getName()] = [$x, $y, $z];
				$player->sendMessage("pos" . $pos, $x . ":" . $y . ":" . $z);
				break;
			}
		}
		
		if(!$event->isCancelled() && in_array($player->getName(), $this->placeegg)){
			$entity = new EasterEgg($player->getLevel(), Entity::createBaseNBT($block->add(0.5, 0.5, 0.5), null, $this->plugin->getDirection($player->getYaw())));
			$this->plugin->easterEggs->set((string) $entity->eggId, []);
			$entity->spawnToAll();
			return;
		}
		
		if($level->getFolderName() === "hub"){
			if($item->getId() === Item::FIREWORKS || $block->getId() === Block::ANVIL){
				$event->setCancelled(false); //TODO: check if not pressing a trapdoor, door, etc. (like duels)?
			}elseif($block->getId() === Block::CHEST){
				//NOTE: crate listener must doc @ignoreCancelled false
				$event->setCancelled();
			}
		}
		
		//TODO: fix this nasty code in new parkour system
		if($x === 45057 && $y === 39 && $z === -42431 && $level->getFolderName() === "hub"){
			$player->sendMessage("poggers");
		}
		//Parkour
		if($x === 45011 && $y === 38 && $z === -42386 && $level->getFolderName() === "hub"){
			if(isset(Fly::$hasFliedInHub[$player->getName()])){
				$player->sendMessage("parkour-flying");
			}elseif(isset($this->lobbyparkour[$player->getName()])){
				$player->sendMessage("parkour-completed");
			}else{
				$player->teleport($player->getLevel()->getSpawnLocation());
				$item = ItemUtils::get("lucky_block")->setCount(\kenygamer\Core\Main::mt_rand(8, 16));
				if($this->plugin->testSlot($player, 1)){
					LangManager::broadcast("parkour-complete", $player->getName(), $item->getCount());
					$player->getInventory()->addItem($item);
					$this->lobbyparkour[$player->getName()] = true;
				}
			}
		}
		//Maze
		if($x === 100 && $y === 102 && $z === -3 && $level->getFolderName() === "maze"){
			if($this->plugin->maze->get($player->getName())){
				$player->sendMessage("maze-completed");
			}else{
				$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
				$item = ItemUtils::get("atlas_gem");
				if($this->plugin->testSlot($player, 1)){
					LangManager::broadcast("maze-complete", $player->getName());
					$player->getInventory()->addItem($item);
					$this->plugin->maze->set($player->getName(), time());
				}
			}
		}
		$clickPos = $block->floor();
		foreach($this->plugin->skyjumpSetpoints as $i => $pos){
			if($clickPos->equals($pos)){
				$setpointLevel = $i + 1;
				$maxLevel = count($this->plugin->skyjumpSetpoints);
				
				$data = $this->plugin->skyjump->get($player->getName(), []);
				$todayComplete = 0;
				foreach($data as $setpoint => $time){
					if(time() - $time < 86400){
						$todayComplete++;
					}
				}
				if($todayComplete >= 3){
					$player->sendMessage("skyjump-setpoint-max");
				}else{
					$lastSetpoint = $this->plugin->getLastSkyjumpSetpoint($player);
					if($setpointLevel > $lastSetpoint + 1){
						$player->sendMessage("skyjump-setpoint-skip");
					}elseif($setpointLevel <= $lastSetpoint){
						$player->sendMessage("skyjump-setpoint-past");
					}else{
						$register = false;
						if($i > 0){
							if($maxLevel === $setpointLevel){
								$reward = ItemUtils::get("tartarus_gem");
								if($player->getInventory()->canAddItem($reward)){
									$player->getInventory()->addItem($reward);
									LangManager::broadcast("skyjump-completeall", $player->getName());
									$player->teleport($player->getLevel()->getSpawnLocation());
									$register = true;
								}else{
									$player->sendMessage("inventory-nospace");
								}
							}else{
								LangManager::broadcast("skyjump-complete", $player->getName(), $i);
								$register = true;
								if($todayComplete + 1 >= 3){
									$player->sendMessage("skyjump-setpoint-max");
									$player->teleport($player->getLevel()->getSpawnLocation());
								}
							}
						}else{
							$register = true;
						}
						if($register){
							$data[$setpointLevel] = time();
							$this->plugin->skyjump->set($player->getName(), $data);
							$player->sendMessage("skyjump-setpoint", $setpointLevel);
						}
					}
				}
				break;
			}
		}
				
		//Raiding
		if(isset(Main::$raiding[$player->getName()])){
		    $event->setCancelled();
			if(!in_array($name = $level->getFolderName(), ["wild", "vipworld"])){
				$player->sendMessage("raid-notworld");
				return;
			}
			
			if($block->getBoundingBox() === null){ //This is sometimes null.
				return;
			}
			$size = $this->plugin->getConfig()->getNested("raiding.size");
			$boundingBox = $block->getBoundingBox()->expandedCopy($size, $size, $size);
			/** @var Vector3[] */
			$chests = [];
			
			$price = $this->plugin->getConfig()->getNested("raiding.price");
			$totalPrice = 0;
			foreach($level->getTiles() as $tile){
				if($boundingBox->isVectorInside($tile->asVector3()) && $tile instanceof Chest){
					$info = $this->plugin->getChestInfo($tile);
					if($info->attribute !== Main::PG_NOT_LOCKED && $info->owner !== $player->getName()){
						$totalPrice += count($tile->getInventory()->getContents()) * $price;
						$chests[] = $tile->asVector3();
					}
				}
			}
			
			if(count($chests) < 1){
				/*$player->sendMessage("raid-nochests");
				return;*/
			}
			if(!isset($this->confirmRaid[$player->getName()]) || microtime(true) - $this->confirmRaid[$player->getName()][0] > 1.5 || spl_object_hash($block) !== $this->confirmRaid[$player->getName()][1]){
				$player->sendMessage("raid-price", $totalPrice, count($chests));
				$this->confirmRaid[$player->getName()] = [microtime(true), spl_object_hash($block)];
				return;
			}
			unset($this->confirmRaid[$player->getName()]); //who can re-/raid in less than 1 second anyway XD
			if($this->plugin->myMoney($player) - $totalPrice <= 0){
				LangManager::send("money-needed", $player, $totalPrice);
				return;
			}
			
			$this->plugin->questManager->getQuest("billionaire_bandit")->progress($player, count($chests));
			$player->sendMessage("raid-raided", count($chests));
			$world = $level->getFolderName() === "vipworld" ? "VIP" : "wild";
			LangManager::broadcast("raid-broadcast", $player->getName(),  "(X: " . $block->getX() . ", Y:" . $block->getY() . ", Z:" . $block->getZ() . ")", $world);
				
			$this->raids[] = [$boundingBox, $level, time() + $this->plugin->getConfig()->getNested("raiding.timer"), \kenygamer\Core\Main::mt_rand(PHP_INT_MIN, PHP_INT_MAX), $player->getName()];
				
			$explosion = new Explosion($block->asPosition(), $size);
			$explosion->explodeA();
			
			//TODO: fix this nasty code
			
			unset(Main::$raiding[$player->getName()]);
			
			foreach($explosion->affectedBlocks as $i => $b){
				if(!Area::getInstance()->cmd->canEdit($player, $b)){
					unset($explosion->affectedBlocks[$i]); //Does not reindex, unlike array_splice() :=P
					continue;
				}
				if($b->getId() === Block::CHEST && ($tile = $b->getLevel()->getTile($b->asVector3())) instanceof Chest){
					foreach($tile->getInventory()->getContents(false) as $item){
						if($player->getInventory()->canAddItem($item)){
							$player->getInventory()->addItem($item);
						}else{
							$player->getLevel()->dropItem($b->asVector3(), $item);
						}
					}
					$tile->getInventory()->setContents([]);
					unset($explosion->affectedBlocks[$i]);
				}
			}
	        $explosion->explodeB();
				
			$level->setBlock($block->add(0, 1, 0), Block::get(Block::STONE));
		    $level->setBlock($block->add(0, 2, 0), Block::get(Item::SIGN_POST));
				
			$nbt = new CompoundTag("", [
			    "id" => new StringTag("id", Tile::SIGN),
			    "x" => new IntTag("x", $block->getX()),
			    "y" => new IntTag("y", $block->getY() + 2),
			    "z" => new IntTag("z", $block->getZ()),
			    "Text" => new StringTag("Text", LangManager::translate("raid-sign", $player->getName()))
			]);
			Tile::createTile("Sign", $level, $nbt);
		}
		
		foreach($this->raids as $area){
			$boundingBox = $area[0];
			$level = $area[1];
			$raider = $area[4];
			if($boundingBox->isVectorInside($block) && $level->getFolderName() === $player->getLevel()->getFolderName()){
				if($player->getName() !== $raider){
					LangManager::send("raid-raided", $raider);
					$event->setCancelled();
					return;
				}
				$event->setCancelled(\false); //If it was
			}
		}
		
		//Tags Redeemal
		if($item->getId() === Item::NAMETAG){
			if($item->getNamedTag()->hasTag("TagName", StringTag::class)){
				$tagName = $item->getNamedTag()->getString("TagName");
				$event->setCancelled();
				$tags = $this->plugin->tags->get($player->getName(), []);
				$i = array_search($tagName, $this->plugin->getConfig()->get("tags"));
				if($i === false){
					LangManager::send("tag-notfound", $player);
				}elseif(in_array($i, $tags)){
					LangManager::send("tag-unlocked", $player, $tagName);
				}else{
					$tags[] = $i;
					$this->plugin->tags->set($player->getName(), $tags);
					$item->setCount($item->getCount() - 1);
					$player->getInventory()->setItemInHand($item);
					LangManager::send("tag-unlock", $player, $tagName);
				}
			}
		}
		
		/*if($event->getAction() === PlayerInteractEvent::LEFT_CLICK_BLOCK){
			$this->startBreak[$player->getName()] = floor(microtime(true));
		}*/
	}
	
	/**
	 * @param PlayerJoinEvent $event
	 * @priority HIGHEST
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		$event->setJoinMessage("");
		
		$this->sendCrystals($player, $player->getLevel()->getFolderName());
		
        LangManager::broadcast($this->plugin->isStaff($player) ? "join-staff" : "join-player", $player->getName());
		
		$lwc = $player->getLowerCaseName();
		if(is_string($nickname = $this->plugin->getEntry($player, Main::ENTRY_NICKNAME))){
			$player->setDisplayName(str_replace($player->getName(), $nickname, $player->getDisplayName()) . "~");
			$player->setNameTag(str_replace($player->getName(), $nickname, $player->getNameTag()) . "~");
		}
		$bossbar = $this->plugin->getPlayerBossBar($player);
		
		$bossbar->setPercentage(1);
		if($this->plugin->settings->getNested($player->getName() . ".compass", true)){
			$bossbar->addPlayer($player);
		}
		
		//Log aliases.
		$data = $this->plugin->playerTrack->get($lwc);
		if($this->plugin->playerTrack->get($lwc) === false){
			$data = ["ips" => [], "cids" => []];
			$this->plugin->playerTrack->set($lwc, $data);
		}
		if(!in_array($ip = $player->getAddress(), $data["ips"])){
			$data["ips"][] = $ip;
		}
		if(!in_array($cid = $player->getUniqueId()->toString(), $data["cids"])){
			$data["cids"][] = $cid;
		}
		$this->plugin->playerTrack->set($lwc, $data);
		
		if($this->plugin->love->get($player->getName()) === false){
			$this->plugin->love->set($player->getName(), ["loving" => "", "nolove" => false]);
		}
		/*$freeTags = [9, 10, 11];
		$myTags = $oldTags = $this->plugin->tags->get($player->getName(), []);
		foreach($freeTags as $tag){
			if(!in_array($tag, $myTags)){
				$myTags[] = $tag;
			}
		}
		if($oldTags !== $myTags){
			$this->plugin->tags->set($player->getName(), $myTags);
		}*/
		
		if(in_array($player->getLevel()->getFolderName(), $this->plugin->rainWorlds)){
			$this->setRaining($player);
		}
		
		if($player->getLevel()->getFolderName() === "hub"){
		    //Lobby Parkour Anti-Cheat
		    if($player->isFlying()){
				Fly::$hasFliedInHub[$player->getName()] = true;
			}
		}
		
		if(!$player->hasPlayedBefore()){
		    /*$book = new WrittenBook();
			$pages = str_split($this->guide, 200);
			foreach($pages as $i => $page){
				$book->setPageText($i, TextFormat::colorize($page));
			}
			$book->setTitle(TextFormat::colorize("&r&aPlayer Guide"));
			$player->getInventory()->addItem($book);*/
			
			if($this->plugin->rankCompare($player, "Nightmare") === 0 || $this->plugin->rankCompare($player, "Universe") === 0){
				$player->getInventory()->addItem(ItemUtils::get("supreme_gem"));
			}
			LangManager::broadcast("broadcast-newjoin", $player->getName());
			
			foreach($this->plugin->referrals->getAll() as $referrer => $players){
				foreach($players as $pl => $claimData){
					if($claimData["ip"] === $player->getAddress()){
						$this->plugin->getLogger()->info($player->getName() . " is alt of " . $pl);
						return;
					}
				}
			}
			$this->plugin->getScheduler()->scheduleDelayedRepeatingTask(new JoinTask($player, true), 10, 150);
		}else{
		    $this->plugin->getScheduler()->scheduleDelayedTask(new JoinTask($player), 10);
		}
	}
	
	public function onPlayerRespawn(PlayerRespawnEvent $event) : void{
		$player = $event->getPlayer();
		if(isset($this->respawnExp[$player->getName()])){
			$exp = $this->respawnExp[$player->getName()];
			$this->plugin->scheduleDelayedCallbackTask(function() use($player, $exp){
				$player->addXp($exp);
			}, 1);
			unset($this->respawnExp[$player->getName()]);
		}
	} 
	
	/**
	 * @param PlayerDeathEvent $event
	 * @priority HIGHEST
	 *
	 * Assumed lower priority in {@link CustomEnchants\CustomListener::onDeath}
	 */
	public function onPlayerDeath(PlayerDeathEvent $event) : void{
		$event->setDeathMessage("");
		
		$player = $event->getPlayer();
		
		$vehicle = $this->plugin->getVehicle($player);
		if($vehicle !== null){
			$vehicle->removePlayer($player);
		}
		
		foreach($event->getDrops() as $i => $drop){
			//Player::dropItem() does not work?
			$player->getLevel()->dropItem($player->asPosition(), $drop);
			$player->getInventory()->removeItem($drop);
		}
		$event->setDrops([]);
		//Send to overworld before respawn
		$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
		
		$cause = $player->getLastDamageCause();
		
		$kill = false;
		if($cause !== null && $cause instanceof EntityDamageEvent){
			$server = $this->plugin->getServer();
			if($cause->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK && ($damager = $cause->getDamager()) instanceof Player){
				$kill = true;
			    $this->plugin->registerEntry($player, Main::ENTRY_DEATHS);
			    $this->plugin->registerEntry($damager, Main::ENTRY_KILLS);
			    
			    $this->plugin->resetEntry($player, Main::ENTRY_KILL_STREAK);
			    $this->plugin->registerEntry($damager, Main::ENTRY_KILL_STREAK);
			    
			    $manager = $this->plugin->permissionManager;
				//Barbarian
			    $compare = $this->plugin->rankCompare($damager, "Barbarian");
			    
			    if($this->plugin->getKDR($damager) >= 3){
			    	if($compare < 0){
			    	    $rank = $manager->getPlayerGroup($damager)->getName();
			    	    switch($rank){
			    	    	case "Shard":
			    	    		$notes = [ItemUtils::get("shard_note")];
			    	    		break;
			    	    	case "Harpy":
			    	    	 	$notes = [ItemUtils::get("harpy_note"), ItemUtils::get("shard_note")];
			    	    	 	break;
			    	    	case "Fury":
			    	    		$notes = [ItemUtils::get("fury_note"), ItemUtils::get("harpy_note"), ItemUtils::get("shard_note")];
			    	    		break;
			    	    	case "Knight":
			    	    	    $notes = [ItemUtils::get("knight_note"), ItemUtils::get("fury_note"), ItemUtils::get("harpy_note"), ItemUtils::get("shard_note")];
			    	    	    break;
			    	    	default:
			    	    		$notes = [ItemUtils::get("air")];
			    	    }
			    	    if(ItemUtils::addItems($damager->getInventory(), ...$notes)){
			    			$manager->setPlayerGroup($damager, "Barbarian");
			    		}
			    	}
			    }elseif($compare === 0){
			    	$manager->setPlayerGroup($damager, "Member");
			    }
			    
				//Kill Money
				$killer_rank = $manager->getPlayerGroup($damager)->getName();
				$murdered_rank = $manager->getPlayerGroup($player)->getName();
				if(($killer_i = array_search($killer_rank, $this->plugin->ranks)) !== false && ($murdered_i = array_search($murdered_rank, $this->plugin->ranks)) !== false){
					$killer_kdr = $this->plugin->getKDR($damager->getName());
					
					//% calculation = 0.11 base + rank diff 0.3 each + 0.01 per KDR level
					$diff = abs((1 / count($this->plugin->ranks)) + (($killer_i - $murdered_i) * 0.3) + (floatval($killer_kdr) * 0.01));
					$money_stolen = round($this->plugin->myMoney($player) * $diff / 100);
					if($money_stolen > 0){
						$this->plugin->addMoney($damager, $money_stolen);
						$this->plugin->reduceMoney($player, $money_stolen);
					    LangManager::send("killmoney-stolen", $damager, $money_stolen, $player->getName());
					    LangManager::send("killmoney-lost", $player, $money_stolen, $damager->getName());
					}
				}
				
				//Disable EXP orbs
				$damager->addXp($player->getCurrentTotalXp());
				$player->setCurrentTotalXp(0);
				
				/** @var string */
				$item = TextFormat::clean(explode("\n", $damager->getInventory()->getItemInHand()->getName())[0]);
				if($item === "Air"){
					$item = "punch";
				}
				$tag = $this->plugin->permissionManager->getPlayerSuffix($damager);
				$tagIndex = array_search($tag, $this->plugin->tagList);
				$msg = "death-msg";
				if($tagIndex !== false){
					switch($tagIndex){
						case 0:
						    $msg = "kill-tag-toxic";
						    break;
						case 1:
						    $msg = "kill-tag-tryhard";
						    break;
						case 2:
						    $msg = "kill-tag-noob";
						    break;
						case 3:
						    $msg = "kill-tag-king";
						    break;
						case 4:
						    $msg = "kill-tag-queen";
						    break;
						case 5:
						    $msg = "kill-tag-nolife";
						    break;
						case 6:
						    $msg = "kill-tag-slayer";
						    break;
						case 7:
						    $msg = "kill-tag-yeet";
						    break;
						case 8:
						    $msg = "kill-tag-assassin";
						    break;
						case 9:
						    $msg = "kill-tag-aboose";
						    break;
						case 10:
						    $msg = "kill-tag-santa";
						    break;
						//TODO: LeapAbuses
						case 12:
						    $msg = "kill-tag-elite4ever";
						    break;
						case 13:
						    $msg = "kill-tag-epicgames";
						    break;
						//TODO
					}
				}
			}else{
				switch($cause->getCause()){
					case EntityDamageEvent::CAUSE_CONTACT:
					    $damager = $cause->getDamager()->getName();
					    $msg = "death-contact";
					    break;
					case EntityDamageEvent::CAUSE_PROJECTILE:
					    $shooter = $cause->getDamager();
					    $damager = $shooter instanceof Player ? $shooter->getName() : "";
					    $msg = "death-projectile";
					    break;
					case EntityDamageEvent::CAUSE_SUFFOCATION:
					    $msg = "death-suffocation";
					    break;
					case EntityDamageEvent::CAUSE_FALL:
					    $msg = "death-fall";
					    break;
					case EntityDamageEvent::CAUSE_FIRE:
					    $msg = "death-fire";
					    break;
					case EntityDamageEvent::CAUSE_FIRE_TICK:
					    $msg = "death-firetick";
					    break;
					case EntityDamageEvent::CAUSE_LAVA:
					    $msg = "death-lava";
					    break;
					case EntityDamageEvent::CAUSE_DROWNING:
					    $msg = "death-drowning";
					    break;
					case EntityDamageEvent::CAUSE_BLOCK_EXPLOSION:
					case EntityDamageEvent::CAUSE_ENTITY_EXPLOSION:
					    $msg = "death-explosion";
					    break;
					case EntityDamageEvent::CAUSE_VOID:
					    $msg = "death-void";
					    break;
					case EntityDamageEvent::CAUSE_MAGIC:
					    $msg = "death-magic";
					    break;
					case EntityDamageEvent::CAUSE_STARVATION:
					    $msg = "death-starvation";
					    break;
				}
			}
			if(isset($msg)){
				foreach($player->getServer()->getOnlinePlayers() as $p){
					$p->sendMessage("death-broadcast", LangManager::translate($msg, $p, $player->getName(), isset($damager) ? ($damager instanceof Player ? $damager->getName() : (is_string($damager) ? $damager : "")) : "", $item ?? ""));
				}
			}
		}
		if(!$kill){
			$this->respawnExp[$player->getName()] = $player->getCurrentTotalXp();
			$player->setCurrentTotalXp(0);
		}
	}
	
	/**
	 * Generic Casino window
	 * @param Player $player
	 * @param string $title
	 * @param string $content
	 * @param int $game 0, 1 or 2
	 */
	public function casinoInfo(Player $player, string $title, string $content, int $game){
		$form = new ModalForm(function(Player $player, ?bool $opt) use($game){
			if($opt !== null){
				if(!$opt){
					$this->casinoUI($player);
					return;
				}
				switch($game){
					case 0:
					    $this->headsOrTailsUI($player);
					    break;
					case 1:
					    $this->guessTheColorUI($player);
					    break;
					case 2:
					    $this->slotsUI($player);
					    break;
				}
			}
		});
		$form->setTitle($title);
		$form->setContent($content);
		$form->setButton1(LangManager::translate("goback", $player));
		$form->setButton2(LangManager::translate("exit", $player));
		$form->sendToPlayer($player);
	}
	
	/**
	 * Subtract the coins that the casino game asks for
	 * @param Player $player
	 * @param int $game
	 * @return bool
	 * @throws \InvalidArgumentException
	 */
	public function subtractCoin(Player $player, int $game) : bool{
		$coins = 0;
		switch($game){
			case 2:
			    $coins = 3;
			    break;
			case 1:
			    $coins = 1;
			    break;
			case 0:
			    $coins = 2;
			    break;
			default:
			    throw new \InvalidArgumentException("Invalid game passed to MiscListener::subtractCoin()");
		}
		$item = (ItemUtils::get("casino_coin"))->setCount($coins);
		if(!$player->getInventory()->contains($item)){
			LangManager::send("casinocoins-needed", $player, $coins);
			return false;
		}
		$player->getInventory()->removeItem($item);
		return true;
	}
	
	
	/**
	 * Casino - Slots UI
	 * @param Player $player
	 */
	public function slotsUI(Player $player) : void{
		$form = new CustomForm(function(Player $player, ?array $data){
			if($data !== null){
				$money = abs(intval($data[1] ?? \null));
				if($money < 1 xor $money > 100000000){
					$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("invalid-bet", $player), 2);
				}elseif($this->plugin->myMoney($player) < $money){
					$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("money-needed", $player, $money), 2);
				}else{
					if(!$this->subtractCoin($player, 2)){
						return;
					}
					$menu = InvMenu::create(InvMenu::TYPE_HOPPER);
					$menu->setListener(InvMenu::readonly());
					$menu->setName(LangManager::translate("slots-title", $player));
					$menu->send($player);
					$menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory){
						if(isset($this->playingSlots[$player->getName()])){
							if(!in_array($player->getName(), $this->finishedSlots)){
								$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("slots-abandoned", $player), 2);
							}else{
								unset($this->finishedSlots[array_search($player->getName(), $this->finishedSlots)]);
							}
							unset($this->playingSlots[$player->getName()]);
						}
					});
					$this->playingSlots[$player->getName()] = [time(), BlockFactory::get(Block::AIR), BlockFactory::get(Block::AIR), BlockFactory::get(Block::AIR)];
					$this->plugin->getScheduler()->scheduleDelayedRepeatingTask(new SlotsTask($this, $player, $money, $menu), 60, 6);
				}
			}else{
				$this->casinoUI($player);
			}
		});
		$form->setTitle(LangManager::translate("slots-title", $player));
		$form->addLabel(LangManager::translate("slots-desc", $player));
		$form->addSlider(LangManager::translate("money-bet", $player), 1, 100000000);
		$player->sendForm($form);
	}
	
	/**
	 * Casino - Guess The Color UI
	 * @param Player $player
	 */
	public function guessTheColorUI(Player $player) : void{
		$opts = [
		    TextFormat::RED => "Red",
		    TextFormat::GOLD => "Orange",
		    TextFormat::YELLOW => "Yellow",
		    TextFormat::GREEN => "Green",
		    TextFormat::BLUE => "Blue",
		    TextFormat::DARK_PURPLE => "Purple"
		];
		$form = new CustomForm(function(Player $player, ?array $data) use($opts){
			if($data !== \null){
				$color = intval($data[1] ?? \null);
				$money = $data[2] ?? \null;
				$indexes = array_values($opts);
				if(!in_array($color, array_keys($indexes))){
					$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("invalid-bet", $player), 1);
				}else{
					$money = abs(intval($money));
					if($money < 1000000 xor $money > 100000000){
		 				$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("invalid-bet", $player), 1);
		 			}elseif($this->plugin->myMoney($player) < $money){
		 				$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("money-needed", $player, $money), 1);
		 			}else{
		 				if(!$this->subtractCoin($player, 1)){
		 					return;
		 				}
		 				$resultColor = array_rand($indexes);
		 				$resultColorName = $indexes[$resultColor];
		 				if($resultColor === $color){
		 					$this->plugin->questManager->getQuest("godly_gambler")->progress($player, 1);
		 					$percent = \kenygamer\Core\Main::mt_rand(1, 200);
		 					$won = $money * $percent / 100;
		 					$this->plugin->addMoney($player, $won);
		 					$won = $won;
		 					$this->casinoInfo($player, LangManager::translate("you-won", $player), LangManager::translate("guessthecolor-won", $player, array_search($resultColorName, $opts) . $resultColorName, $won, $percent, $this->plugin->myMoney($player)), 1);
		 				}else{
		 					$this->plugin->questManager->getQuest("godly_gambler")->progress($player, 0);
		 					$percent = \kenygamer\Core\Main::mt_rand(1, 50);
		 					$lost = $money * $percent / 100;
		 					$this->plugin->reduceMoney($player, $lost);
		 					$lost = $lost;
		 					$this->casinoInfo($player, LangManager::translate("you-lost", $player), LangManager::translate("guessthecolor-lost", $player, array_search($resultColorName, $opts) . $resultColorName, $lost, $percent, $this->plugin->myMoney($player)), 1);
		 				}
		 			}
		 		}
			}else{
				$this->casinoUI($player);
			}
		});
		$form->setTitle(LangManager::translate("guessthecolor-title", $player));
		$form->addLabel(LangManager::translate("guessthecolor-desc", $player));
		$colors = [];
		foreach($opts as $colorCode => $color){
			$colors[] = $colorCode . $color;
		}
		$form->addDropdown(LangManager::translate("bet-for", $player), $colors);
		$form->addSlider(LangManager::translate("money-bet", $player), 1000000, 100000000);
		$player->sendForm($form);
	}
	
	/**
	 * Casino - Heads Or Tails UI
	 * @param Player $player
	 */
	public function headsOrTailsUI(Player $player) : void{
		 $opts = ["Heads", "Tails"];
		 $form = new CustomForm(function(Player $player, ?array $data) use($opts){
		 	if($data !== \null){
		 		$bet = $data[1] ?? -1;
		 		$money = $data[2] ?? \null;
		 		if($bet < 0 xor $bet > count($opts) - 1){
		 			$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("invalid-bet", $player), 0);
		 		}elseif(!is_numeric($money)){
		 			$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("invalid-bet", $player), 0);
		 		}else{
		 			$money = abs(intval($money));
		 			if($money < 1000000 xor $money > 100000000){
		 				$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("invalid-bet", $player), 0);
		 			}elseif($this->plugin->myMoney($player) < $money){
		 				$this->casinoInfo($player, LangManager::translate("error", $player), LangManager::translate("money-needed", $player, $money), 0);
		 			}else{
		 				if(!$this->subtractCoin($player, 0)){
		 					return;
		 				}
		 				$result = array_rand($opts);
		 				if($result === $bet){
		 					$this->plugin->questManager->getQuest("godly_gambler")->progress($player, 1);
		 					$won = $money / 4;
		 					$this->plugin->addMoney($player, $won);
		 					$won = $won;
		 					$this->casinoInfo($player, LangManager::translate("you-won", $player), LangManager::translate("headsortails-won", $player, $opts[$result], $won, $this->plugin->myMoney($player)), 0);
		 				}else{
		 					$this->plugin->questManager->getQuest("godly_gambler")->progress($player, 0);
		 					$lost = $money / 2;
		 					$this->plugin->reduceMoney($player, $lost);
		 					$lost = $lost;
		 					$this->casinoInfo($player, LangManager::translate("you-lost", $player), LangManager::translate("headsortails-lost", $player, $opts[$result], $lost, $this->plugin->myMoney($player)), 0);
						}
					}
				}
			}else{
				$this->casinoUI($player);
			}
		});
		$form->setTitle(LangManager::translate("headsortails-title", $player));
		$form->addLabel(LangManager::translate("headsortails-desc", $player));
		$form->addDropdown(LangManager::translate("bet-for", $player), $opts);
		$form->addSlider(LangManager::translate("money-bet", $player), 1000000, 100000000);
		$player->sendForm($form);
	}
	
	/**
	 * Casino - Game Selector UI
	 * @param Player $player
	 */
	public function casinoUI(Player $player) : void{
		$form = new SimpleForm(function(Player $player, ?int $data){
			if($data !== null){
				switch($data){
					case 0:
					    $this->headsOrTailsUI($player);
					    break;
					case 1:
					    $this->guessTheColorUI($player);
					    break;
					case 2:
					    $this->slotsUI($player);
					    break;
			    }
			}
		});
		$form->setTitle(LangManager::translate("casino-title", $player));
		$form->setContent(LangManager::translate("casino-desc", $player));
		$form->addButton(LangManager::translate("casino-1", $player));
		$form->addButton(LangManager::translate("casino-2", $player));
		$form->addButton(LangManager::translate("casino-3", $player));
		$player->sendForm($form);
	}
	
	/**
	 * Enter player(s) in combat logger
	 * @param Player ...$players
	 */
	public function combatLogger(Player ...$players) : void{
		foreach($players as $player){
			if(!$this->plugin->getPlayerDuel($player)){
				if(!isset($this->combatLogger[$player->getName()])){
					LangManager::send("combatlogger-on", $player);
					$player->addTitle(LangManager::translate("combatlogger-title-1", $player), LangManager::translate("combatlogger-title-2", $player), 15, 15, 15);
					if($player->getGamemode() % 2 === 0){
						$player->setFlying(false);
						$player->setAllowFlight(false);
					}
				}
				$this->combatLogger[$player->getName()] = time() + 15;
			}
		}
	}
	
	/**
	 * @param EntityDamageByEntityEvent $event
	 * @priority LOWEST
	 */
	public function onPvPAntiCheat(EntityDamageByEntityEvent $event) : void{
		//TODO: Kill Aura and CPS Anti-Cheat should go here
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		if($entity instanceof Player && $damager instanceof Player){
			if(isset($this->startEatTick[$damager->getName()]) && ($damager->getServer()->getTick() % 20) === ($this->startEatTick[$damager->getName()] % 20)){
				$event->setCancelled();
			}
		}
	}
	
	/**
	 * @param EntityDamageByEntityEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		
		if($entity instanceof Human && !($entity instanceof Player) && $damager instanceof Player){ //TODO: Unhardcode this
			switch($entity->getName()){
				case "§l§aPlay a Game":
				    $this->casinoUI($damager);
				    break;
				case "§l§aBuy a Spawner":
				    $damager->getServer()->dispatchCommand($damager, "spawner");
				    break;
				case "§l§5Tinkerer":
					$exp = []; // min: ceshop price divided into 100, max: ceshop price divided into 10
					foreach($this->plugin->getConfig()->get("tinkerer") as $rarity => $price){
						list($min, $max) = explode("-", $price);
						$min = (int) round($min);
						$max = (int) round($max);
						$int = $this->plugin->getRarityByName($rarity);
						if($int === -1){
							continue;
						}
						$exp[$int] = \kenygamer\Core\Main::mt_rand($min, $max);
					}
					$item = $damager->getInventory()->getItemInHand();
					$rarity = -1;
					if($item->getId() === Item::BOOK){
						$rarity = $this->plugin->getRarityByDamage($item->getDamage());
	    			}
	    			if($item->getId() === Item::ENCHANTED_BOOK){
	    				if(!empty($enchantments = $item->getEnchantments())){
	    					$rarity = $enchantments[0]->getType()->getRarity();
	    				}
					}
					if($rarity !== -1){
	    				$damager->addXp($result = $exp[$rarity] * $item->getCount());
	    				$damager->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
	    				$damager->sendMessage("exp-given", $result);
	    			}else{
	    				$damager->sendMessage("hold-enchantedbook");
	    			}
					break;
				case "§l§6Wandering Trader":
					if(PlayerEvents::getPlayerData($damager)["DeviceOS"] !== "Windows 10" && $damager->getGamemode() % 2 === 0){
						$damager->addWindow(new TradeInventory($entity->getId()));
					}
					break;
				case "§l§fPostal Office":
					$damager->getServer()->dispatchCommand($damager, "mail");
					break;
				default: //Bosses and NPCs
				    if(!$event->isCancelled()){
				    	if($event->getFinalDamage() >= $entity->getHealth()){
				    		$this->plugin->questManager->getQuest("boss_slayer")->progress($damager, 1);
				    	}
				    	$this->combatLogger($damager);
				    }
			}
			return;
		}
		$pvp = $entity instanceof Player && $damager instanceof Player;
		if($pvp){
			if(($item = $entity->getInventory()->getItemInHand()) instanceof Shield){ //Shields
				if($entity->getGenericFlag(Entity::DATA_FLAG_BLOCKING)){
					$entity->getLevel()->broadcastLevelSoundEvent($e, LevelSoundEventPacket::SOUND_ITEM_SHIELD_BLOCK);
					if($event->getFinalDamage() >= 4){
						$item->applyDamage(1);
						$entity->getInventory()->setItemInHand($item);
						$event->setBaseDamage($event->getFinalDamage() * 0.3);
					}else{
						$event->setCancelled();
					}
				}
			}
			$microtime = microtime(true);
		    if(!isset($this->cps[$damager->getName()])){
		    	goto resetCPS;
		    }
			$this->cps[$damager->getName()][1]++;
			if($microtime - $this->cps[$damager->getName()][0] >= 1){
				resetCPS: {
					$this->cps[$damager->getName()] = [$microtime, 0];
				}
			}
			$cps = $this->cps[$damager->getName()][1];
			$alpha = ($damager->getYaw() - $entity->getYaw()) / 2;
			/*var_dump($damager->getYaw() - $entity->getYaw());
			if(!($alpha >= 50 and $alpha <= 110) || $cps > 15){ //CPS and Kill Aura Anti-Cheat
				$event->setCancelled();
			}else{*/
			if(true){
				$damager->sendPopup(TextFormat::RED . "\n\n\nCPS: " . strval($this->cps[$damager->getName()][1]));
			}
		}
		
		//Vehicles
		if($entity instanceof Vehicle){
			$event->setCancelled();
			if(!$damager->hasPermission("core.vehicle.drive")){
				$damager->sendMessage("noperm");
			}
			if($entity->getDriver() === null){
				if($entity->owner !== null && $entity->owner !== $damager->getRawUniqueId()){
					$damager->sendMessage("vehicle-not-owner");
				}else{
					$entity->setDriver($damager);
				}
			}elseif(!$entity->addPassenger($damager)){
				$damager->sendMessage("vehicle-full");
			}
		}
		
		if($event->isCancelled()){
			return;
		}
		
		if($pvp){
			if($land = $this->plugin->landManager->getLand2($entity)){
				if($land->isHelper($entity->getName())){
					$event->setCancelled();
					return;
				}
			}
			
			$damagerFacingDirection = $damager->getDirectionVector()->normalize();
			$damagedPlayerPositionXZ = new Vector3($entity->getX(), 0, $entity->getZ());
			$damagerPositionXZ = new Vector3($damager->getX(), 0, $damager->getZ());
			$hitAngle = rad2deg(acos($damagerFacingDirection->dot($damagedPlayerPositionXZ->subtract($damagerPositionXZ)->normalize())));
			if($hitAngle > 180 && $damager->distance($entity) >= 2.25 && (!isset(CustomListener::getInstance()->lastHexAttack[$entity->getName()]) || !(microtime() - CustomListener::getInstance()->lastHexAttack[$entity->getName()] > 0.1))){
				$event->setCancelled();
				$this->freezeMc($damager);
				//$this->plugin->registerWarn($damager, 2, "CONSOLE");
				//$this->plugin->safeKick($damager, LangManager::translate("killaura-disabled", $damager));
				return;
			}
			
		    unset($this->comboHits[$entity->getName()]); //Remove combo of punched player
			if(!isset($this->comboHits[$damager->getName()])){
				$this->comboHits[$damager->getName()] = [];
			}
			$this->comboHits[$damager->getName()][] = time();
			$lastHit = intval(end($this->comboHits[$damager->getName()]));
			if(time() - $lastHit <= 2){
				$combo = count($this->comboHits[$damager->getName()]);
				if($combo % 5 === 0){
					$percent = \kenygamer\Core\Main::mt_rand(20, 40) * ($combo / 3);
					$damager->addTitle(LangManager::translate("hitcombo-title-1", $damager, strval($combo)), LangManager::translate("hitcombo-title-2", $damager, $percent), 3, 3, 3);
					$event->setBaseDamage($event->getBaseDamage() + ($event->getBaseDamage() * $percent / 100));
				}
			}else{
				$this->comboHits[$damager->getName()] = [];
			}
			
			$this->combatLogger($entity, $damager);
			
			foreach($this->raids as $area){
				$boundingBox = $area[0];
				$level = $area[1];
				$raider = $area[4];
				if($boundingBox->isVectorInside($entity->asVector3()) && $level->getFolderName() === $entity->getLevel()->getFolderName() && $entity->getName() === $raider){
					LangManager::send("raid-invulnerable", $damager, $raider);
					$event->setCancelled();
					break;
				}
			}
		}
	}
	
	
	/**
	 * Replaces non-UTF8 characters from the message.
	 * Addresses some issues with TextFormat::clean(), etc.
	 * 
	 * @param PlayerChatEvent $event
	 *
	 * @priority LOWEST
	 * @ignoreCancelled true
	 */
	public function onChatFixEncoding(PlayerChatEvent $event) : void{
		$event->setMessage(mb_convert_encoding($event->getMessage(), "UTF-8", "UTF-8"));
	}
	
	/**
	 * @param PlayerChatEvent $event
	 *
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void{
		$message = $event->getMessage();
		$player = $event->getPlayer();
		
	    //Bragging
	    switch(mb_strtolower(TextFormat::clean($message))){
	    	case "[item]":
	    	    $event->setCancelled();
	    	    foreach(Main::$bragHouse as $brag){
	    	    	if($brag["player"] === $player->getName()){
	    	    		LangManager::send("in-cooldown", $player);
	    	    		break 2;
	    	    	}
	    	    }
	    	    Main::$bragHouse[] = [
	    	        "player" => $player->getName(),
	    	        "items" => [$item = $player->getInventory()->getItemInHand()],
	    	        "time" => time()
	    	    ];
	    	    LangManager::send("brag-bragged-item", $player, TextFormat::clean(explode("\n", $item->getName())[0]));
	    	    LangManager::broadcast("brag-posted-item", $player->getName());
	    	    break;
	    	case "[brag]":
	    	    $event->setCancelled();
	    	    foreach(Main::$bragHouse as $brag){
	    	    	if($brag["player"] === $player->getName()){
	    	    		LangManager::send("in-cooldown", $player);
	    	    		break 2;
	    	    	}
	    	    }
	    	    Main::$bragHouse[] = [
	    	        "player" => $player->getName(),
	    	        "items" => $items = ($player->getInventory()->getContents(false) + $player->getArmorInventory()->getContents(false)),
	    	        "time" => time()
	    	    ];
	    	    LangManager::send("brag-bragged-inventory", $player, count($items));
	    	    LangManager::broadcast("brag-posted-inventory", $player->getName());
	    	    break;
	    }
	    
		if(strpos($message, "§k")){
			LangManager::send("chatformat-banned", $player);
			$event->setCancelled();
			return;
		}
		//QuietPlease
		if(!$this->plugin->hasVotedToday($player) && !$player->isOp()){
			LangManager::send("quietplease", $player);
			$event->setCancelled();
		}else{
			//Anti-spam
			if(isset($this->lastChat[$player->getName()]) && time() - $this->lastChat[$player->getName()] < 3 && !$player->isOp()){
				LangManager::send("chatspam-flagged", $player);
				$event->setCancelled();
				return;
			}
			$this->lastChat[$player->getName()] = time();
			if(!$player->isOp()){
				$message = ucfirst(mb_strtolower($message)); //strip out capital letters from messages
			}
			//Chat Slugs
			$slugs = [
			   "[coords]" => "(" . $player->getFloorX() . ", " . $player->getFloorY() . ", " . $player->getFloorZ() . ", " . $player->getLevel()->getFolderName() . ")",
			   "[money]" => (string) $this->plugin->myMoney($player)
			];
			foreach($slugs as $slug => $eval){
				if(($result = str_replace($slug, $eval, TextFormat::clean($message))) !== TextFormat::clean($message)){
					$message = $result;
				}
			}
			$event->setMessage($message);
		}
	}
	
	/**
	 * @param PlayerCommandPreprocessEvent $event
	 *
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		$msg = $event->getMessage();
		$cmd = mb_strtolower(explode(" ", $msg)[0]);
		
		$isBypassing = in_array($cmd, self::CHAT_COMMANDS);
        $isChatting = !$isBypassing && strpos($cmd, "/") === false;
        if(($isChatting or $isBypassing)){
            if(isset($this->plugin->mutes[$player->getName()])){
				if(!(time() >= $this->plugin->mutes[$player->getName()])){
                	$player->sendMessage("muted", $this->plugin->formatTime($this->plugin->getTimeLeft($this->plugin->mutes[$player->getName()]), TextFormat::WHITE, TextFormat::WHITE));
                	$event->setCancelled();
                	return;
				}
            }
			unset($this->plugin->mutes[$player->getName()]);
        }
		
		if(in_array($cmd, self::CHAT_COMMANDS) && !$this->plugin->hasVotedToday($player) && !$player->isOp()){
			LangManager::send("quietplease", $player);
			$event->setCancelled();
			return;
		}
		
		$isCmd = ($slashIndex = strpos($msg, "/")) === 0 xor $slashIndex === 1; //Also checks ./ bypass
		
		if(isset($this->combatLogger[$player->getName()]) && $isCmd && !in_array($cmd, ["./inv", "/inv", "./inventory", "/inventory"])){
			LangManager::send("combatlogger-cmd", $player);
			$player->addTitle(LangManager::translate("combatlogger-title-1", $player), LangManager::translate("combatlogger-title-2", $player), 15, 15, 15);
			$event->setCancelled();
			return;
		}
		
		if($isCmd){
			if($player->isOp()){
				//Run multiple commands
				$commands = explode(";", preg_replace('/(\s+);(\s+)/', ';', $msg));
				array_shift($commands); //This command
				$this->plugin->getScheduler()->scheduleRepeatingTask(new class($player, $commands) extends Task{
					public function __construct(Player $player, array $commands){
						$this->player = $player;
						$this->commands = $commands;
					}
					public function onRun(int $currentTick){
						$command = array_shift($this->commands);
						if($command === null){
							Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
							return;
						}
						$this->player->chat($command);
					}
				}, 1);
			}
			if(isset($this->lastCmd[$player->getName()]) && microtime(true) - $this->lastCmd[$player->getName()] <= 0.4 && !$player->isOp()){
				LangManager::send("cmdspam-flagged", $player);
				$event->setCancelled();
				return;
			}
			$this->lastCmd[$player->getName()] = microtime(true);
			$this->plugin->interpretUnits($msg);
			if($player->getName() !== "XxKenyGamerxX"){
				$this->plugin->getServer()->getLogger()->info($snoop = TextFormat::GRAY . $player->getName() . " > " . $msg); //Snoop commands
			}
			$snoopers = $this->plugin->getPlugin("LegacyCore")->snoopers;
			foreach($snoopers as $pl => $_){
				$p = $this->plugin->getServer()->getPlayerExact($pl);
				if($p !== null){
					$p->sendMessage($snoop);
				}
			}
		}else{
			if($this->plugin->isAFK($player)){
				$this->plugin->isAFK($player, true);
			}
		}
		
		//Banned colors
		if(!$player->isOp()){
			$bannedColors = [TextFormat::OBFUSCATED, TextFormat::BLACK, TextFormat::DARK_BLUE];
			foreach($bannedColors as $color){
				$msg = str_replace($color, "", $msg);
			}
		}
		//Anti-Profanity
		if(!$isCmd){
			foreach($this->bannedWords as $word){
				if(stripos($msg, $word) !== false && !$player->isOp()){
					LangManager::send("profanity-flagged", $player);
					$event->setCancelled();
					break;
				}
			}
		}
		//Anti-advertising
		
		$advert = false;
		$nospace = str_replace(" ", "", $msg);
		//Check tlds - AA1
		foreach($this->tldList as $tld){
			if(stripos($nospace, $tld) !== false){
				$advert = true;
			}
		}
		$regex = '/\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/';
	    //Check IPv4s - AA2
	    if(preg_match($regex, $nospace)){ //filter_var() wont work because it must be the literal IP
	    	$advert = true;
	    }
	    //Intelligently remove banned adverts only if a server advert is found - AA3
	    if($advert && (in_array($cmd, self::CHAT_COMMANDS) || !$isCmd)){
	    	$exempted = [];
	    	foreach(self::EXEMPTED_ADVERTS as $str){
		   		if(($pos = stripos($nospace, $str)) !== false){
	    			similar_text($str, $msg, $percent);
	    			$exempted[$str] = $percent;
				}
			}
			if(!empty($exempted)){
				asort($exempted);
				$msg = array_search(end($exempted), $exempted);
				$advert = false;
			}
			if($advert && !$player->isOp()){
				LangManager::send("advert-flagged", $player);
				$event->setCancelled();
			}
		}
		
		$event->setMessage($msg);
	}
	
	/**
	 * @param ChunkLoadEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onChunkLoad(ChunkLoadEvent $event) : void{
		$chunk = $event->getChunk();
		//$this->snowstorm($chunk, $event->getLevel());
	}
	
	/**
	 * @param ChunkPopulateEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onChunkPopulate(ChunkPopulateEvent $event) : void{
		$chunk = $event->getChunk();
		//$this->snowstorm($chunk, $event->getLevel());
	}
	
	//@notHandler
	private function snowstorm(Chunk $chunk, Level $level) : void{
		$nbt = $level->getProvider()->getLevelData();
		$nbt->setString("generatorOptions", "");
		
		for($x = 0; $x < 16; ++$x){
			for($z = 0; $z < 16; ++$z){
				//We also need empty preset for snow storm to work
				$chunk->setBiomeId($x, $z, Biome::ICE_PLAINS); //PLAINS
				continue;
				
				//$id = $chunk->getHighestBlockAt($x, $z); //only returns the block ID
				$y = Level::Y_MAX; $block = null;
				while(true){
					$y--;
					if($y < 0 || ($id = $chunk->getBlockId($x, $y, $z)) !== Block::AIR){
						$block = [$id, $chunk->getBlockData($x, $y, $z)];
						break;
					}
				}
				if(BlockFactory::get($block[0], $block[1])->isTransparent()){ //Tall grass, signs, chests, etc etc
				   continue;
				}
				
				$chunk->setBlockId($x, $y + 1, $z, Block::SNOW_LAYER); //78
				$chunk->setBlockData($x, $y + 1, $z, \kenygamer\Core\Main::mt_rand(3, 5));
			}
		}
	}
		
	/**
	 * @param EntityDamageEvent $event
	 *
	 * @priority HIGH
	 * @ignoreCancelled true
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		$cancel = $event->isCancelled();
		
		if($event->getFinalDamage() >= $entity->getMaxHealth() && $entity instanceof Player){
			if($event->getCause() === EntityDamageEvent::CAUSE_MAGIC){
				$cancel = true;
			}
			if($event->getCause() === EntityDamageEvent::CAUSE_FALL && $entity->getArmorInventory()->getChestplate() instanceof Elytra){
				$cancel = true;
			}
			if(!$cancel){
				if($event->getCause() === EntityDamageEvent::CAUSE_ENTITY_ATTACK){
					if($entity instanceof Player){
					}else{
					}
				}
			}
		}
		
		//Christmas
		if($entity::NETWORK_ID === Entity::POLAR_BEAR && $entity->getLevel()->getFolderName() === "hub"){
		    $cancel = true;
		}
		
		if($event instanceof EntityDamageByEntityEvent && $entity->namedtag->getTag("EventBoss", IntTag::class)){
			$cancel = false;
			if(($player = $event->getDamager()) instanceof Player){
				foreach($entity->getArmorInventory()->getContents() as $slot => $i){
					if($i instanceof Durable){
						$i->setDamage(0);
						$entity->getArmorInventory()->setItem($slot, $i);
					}
				}
				foreach($entity->getInventory()->getContents() as $slot => $i){
					if($i instanceof Durable){
						$i->setDamage(0);
						$entity->getInventory()->setItem($slot, $i);
					}
				}
					
				$damage = $event->getFinalDamage();
				if($damage >= $entity->getHealth()){
					BossEventTask::$boss_killer = $player->getName();
					$cancel = true;
					$entity->flagForDespawn();
				}
				if(!isset(BossEventTask::$boss_damages[$player->getName()])){
					BossEventTask::$boss_damages[$player->getName()] = $damage;
				}else{
					BossEventTask::$boss_damages[$player->getName()] += $damage;
				}
			}
		}
		
		if($cancel){
			$event->setCancelled();
		}
	}
	
	/**
	 * @param PlayerPreLoginEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void{
		$player = $event->getPlayer();
		if($this->plugin->maintenanceMode && !$this->plugin->getServer()->isOp($player->getName())){
			$event->setCancelled();
			$event->setKickMessage(TextFormat::colorize($this->plugin->getMaintenanceReason()));
		}
	}
	
	/**
	 * @todo
	 * @param PlayerItemConsumeEvent $event
	 * @priority LOWEST
	 */
	public function onFastEatAntiCheat(PlayerItemConsumeEvent $event) : void{
		$player = $event->getPlayer();
		if(isset($this->startEatTick[$player->getName()])){
			if(($diff = $this->plugin->getServer()->getTick() - $this->startEatTick[$player->getName()]) < 20){
				//TODO: not working anymore
				//$event->setCancelled();
			}
			unset($this->startEatTick[$player->getName()]);
			//Tests: 24 ticks avg.
		}
	}
	
	/**
	 * @param PlayerItemConsumeEvent $event
	 *
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerItemConsume(PlayerItemConsumeEvent $event) : void{
		$player = $event->getPlayer();
		$item = $event->getItem();
		if($item->getId() === Item::COOKIE && $item->getNamedTag()->getTag("HealingCookie")){
			if(!isset($this->cookie[$player->getName()])){
			    $this->cookie[$player->getName()] = true;
				$player->setMaxHealth($player->getMaxHealth() + 40);
				LangManager::send("absorbed-cookie", $player);
			}else{
				LangManager::send("in-cooldown", $player);
				$event->setCancelled();
			}
		}
		if($item->getId() === Item::FISH && $item->getNamedTag()->hasTag("Lemon")){
			$this->lemon[$player->getName()] = time();
			LangManager::send("absorbed-lemon", $player);
		}
	}
	
}