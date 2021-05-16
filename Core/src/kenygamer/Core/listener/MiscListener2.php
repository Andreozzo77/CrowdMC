<?php

declare(strict_types=1);

namespace kenygamer\Core\listener;

use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCreationEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerChangeSkinEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerEditBookEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerItemHeldEvent;
use pocketmine\inventory\PlayerInventory;
use pocketmine\event\inventory\actionEvent;
//use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntitySpawnEvent; 
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\inventory\transaction\{
	InventoryTransaction,
	action\SlotChangeAction,
	TransactionValidationException
};
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\level\ChunkPopulateEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\server\DataPacketSendEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InventorySlotPacket;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelChunkPacket;
use pocketmine\network\mcpe\protocol\GameRulesChangedPacket;
use pocketmine\network\mcpe\protocol\MobEffectPacket;
use pocketmine\network\mcpe\protocol\LevelEventPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\network\mcpe\protocol\AvailableCommandsPacket;
use pocketmine\network\mcpe\protocol\ContainerClosePacket;
use pocketmine\network\mcpe\protocol\ContainerOpenPacket;
use pocketmine\network\mcpe\protocol\EmotePacket;
use pocketmine\network\mcpe\protocol\ShowProfilePacket;
use pocketmine\network\mcpe\protocol\MapInfoRequestPacket;
use pocketmine\network\mcpe\protocol\ClientboundMapItemDataPacket;
use pocketmine\network\mcpe\protocol\BlockActorDataPacket;
use pocketmine\network\mcpe\protocol\UpdateBlockPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\CommandData;
use pocketmine\network\mcpe\protocol\types\CommandEnum;
use pocketmine\network\mcpe\protocol\types\CommandParameter;
use pocketmine\network\mcpe\protocol\types\NetworkInventoryAction;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\types\ContainerIds;
use pocketmine\network\mcpe\protocol\types\DimensionIds;
use pocketmine\network\mcpe\protocol\types\StructureEditorData;
use pocketmine\network\mcpe\protocol\types\MapDecoration;

use pocketmine\entity\Entity;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\entity\Skin;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\TextFormat;
use pocketmine\item\ItemFactory;
use pocketmine\item\Item;
use pocketmine\item\WrittenBook;
use pocketmine\item\TieredTool; 
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\ItemBlock;
use pocketmine\Player;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use pocketmine\tile\Sign;
use pocketmine\tile\Container;
use pocketmine\tile\ItemFrame;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\SimpleChunkManager;
use pocketmine\block\Block;
use pocketmine\block\BlockFactory;
use pocketmine\scheduler\ClosureTask;
use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\utils\Color;
use pocketmine\utils\UUID;
use pocketmine\utils\BinaryStream;
use pocketmine\math\Vector3;

use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\RelayThread;
use kenygamer\Core\LangManager;
use kenygamer\Core\ElitePlayer;
use kenygamer\Core\block\MonsterSpawner;
use kenygamer\Core\map\MapData;
use kenygamer\Core\item\FilledMap;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\util\MapImageEngine;
use kenygamer\Core\util\CustomItems;
use kenygamer\Core\task\AddItemQueueTask;
use kenygamer\Core\task\SendEffectsTask;
use kenygamer\Core\task\SpawnerTask;
use kenygamer\Core\util\FactionMap;
use kenygamer\Core\map\MapFactory;
use kenygamer\Core\entity\Minecart;
use kenygamer\Core\tile\BeaconTile;

use LegacyCore\Core;
use LegacyCore\Events\Area;
use LegacyCore\Events\PlayerEvents;
use LegacyCore\Commands\Sell;
use LegacyCore\Tasks\ScoreHudTask;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\tile\MobSpawner;
use specter\api\DummyPlayer;
use muqsit\invmenu\InvMenu;
use muqsit\invmenu\inventory\InvMenuInventory;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;

final class MiscListener2 implements Listener{
	/** @var Main */
	private $plugin;
	
	/** @var int[] */
	private $bombyCooldown = [];
	/** @var float[]  */
	private $closePacketRateLimit = [];
	/** @var float[] */
	private $fixSpamBug = [];
	/** @var LittleEndianNBTStream */
	private static $nbtSerializer;
	
	/** @var array */
	public static $effects = [];
	/** @var Item[] */
	public static $items = [];
	/** @var CompoundTag[] */
	public static $addNbt = [];
	
	public static $fishing = [];
	
	/*
	 * {@link self::$usingAnvil} and {@link self::$usingEnchantingTable} must be unset when the inventory is closed.
	 * (Inventory close handlers not triggered in MCPE 1.16)
	 */
	/** @var array  */
	public static $usingAnvil = [];
	/** @var array */
	public static $usingEnchantingTable = [];
	
	/** @var CompoundTag */
	public static $tradeOffers = [];
	/** @var int[] */
	public static $trading = [];
	/** @var string[] */
	public static $switcher = [];
	/** @var array */
	public static $spawnerBoosters = [];
	/** @var array */
	public static $spawnerEntities = [];
	/** @var string[] */
	public static $lastMessages = [];
	/** @var array */
	public static $pgQueue = [];
	
	//MCPE v1.16
	public static $containerOpen = [];
	/** @var int[] */
	private $fixProfileCrash = [];
	/** @var float[] */ 
	public static $closedCraftingTable = [];
	
	//Images
	/** @var array */
	public static $placeImage = [];
	
	//Experimental
	/** @var Minecart[] */
	public static $riding = [];
	
	//My mistake:
	/** @var ChestInventory[] */
	public static $changingInventory = [];
	public static $lastInventoryOpen = []; //used for InventoryCommand.php.stub
	/** @var int[] */
	public static $fixWin10SpamBug = [];
	/** @var string[] loc => name */
	private static $usingChest = [];
	/** @var Position[] string => Position */
	public static $usingBeacon = []; 
	
	/** @var array */
	private $migrationShops = [];
	/** @var string[] */
	private $migrationLines = [];
	/** @var int */
	private $migrationIndex = 0;
	
	/**
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		self::$nbtSerializer = new NetworkLittleEndianNBTStream();
		
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	    $plugin->getScheduler()->scheduleRepeatingTask(new SendEffectsTask(), 16);
		
		self::$tradeOffers = new CompoundTag("Offers", [
			new ListTag("Recipes", [])
		]);
		foreach($plugin->getConfig()->get("trade-offers") as $offer){
			$buyA = ItemUtils::parseItems([$offer["buy"]])[0];
			$sell = ItemUtils::parseItems([$offer["sell"]])[0];
			self::$tradeOffers->getTag("Recipes")->push(new CompoundTag("", [
				$buyA->nbtSerialize(-1, "buyA"),
				new IntTag("maxUses", 32767),
				new ByteTag("rewardExp", 0),
				$sell->nbtSerialize(-1, "sell"),
				new IntTag("uses", 0),
				new StringTag("label", "gg")
			]));
		}
		MapImageEngine::getInstance();
		/*$this->migrationShops = json_decode(file_get_contents($this->plugin->getServer()->getDataPath() . "shops.json"), true);
		var_dump(count($this->migrationShops));*/
	}
	
	/**
	 * Send a fake block to a specific player
	 * rather than the whole server.
	 *
	 * @param Player  $player
	 * @param Vector3 $position
	 * @param Block   $block
	 */
	private function sendFakeBlock(Player $player, Vector3 $position, Block $block) : void{
		$pk = new UpdateBlockPacket();
		$pk->x = $position->getX();
		$pk->y = $position->getY();
		$pk->z = $position->getZ();
		$pk->blockRuntimeId = $block->getRuntimeId();
		$pk->flags = UpdateBlockPacket::FLAG_NETWORK;
		$player->sendDataPacket($pk);
	}
	
	/**
	  * Attempts to remove the Player's current chunk border and then
	  * creates a new chunk border in their current chunk.
	  *
	  * @param PlayerMoveEvent $event
	  * @priority MONITOR
	  * @ignoreCancelled false
	  */
	public function onChunkBorderMove(PlayerMoveEvent $event) : void{
		$player = $event->getPlayer();
		//https://minecraft.gamepedia.com/Structure_Block
		//Minecraft creates a green line on the Y axis coming out of the tile, and a red and blue line on the X/Z
		//axis coming out of the tile as well. The other lines not connected to the tile are all white.
		
		$level = $player->getLevel();
		if($level === null){
			return; //On quit >:(
		}
		$chunk = $level->getChunkAtPosition($player);
		if($chunk !== null){
			$pos = new Position($chunk->getX() << 4, 0, $chunk->getZ() << 4); //X/Z coordinate pair
			$originalBlock = $level->getBlock($pos);
			
			$chunkXZ = $pos->getX() . ":" . $pos->getZ();
			
			
			$send = $this->plugin->settings->getNested($player->getName() . ".chunkborders", true);
			if(isset($this->chunkBorder[$player->getName()])){
				if(($oldChunkXZ = $this->chunkBorder[$player->getName()]) !== $chunkXZ || !$send){
					
					$oldChunkPos = new Position((int) explode(":", $oldChunkXZ)[0], 0, (int) explode(":", $oldChunkXZ)[1]);
					//Remove the old chunk border from the player
					$this->sendFakeBlock($player, $oldChunkPos, BlockFactory::get(Block::STRUCTURE_BLOCK));
					if($player->getLevel()->getChunk($oldChunkPos->getX() >> 4, $oldChunkPos->getZ() >> 4, false)){
						$player->getLevel()->sendBlocks([$player], [$oldChunkPos]);
					}
					if(!$send){
						unset($this->chunkBorder[$player->getName()]);
					}
					//var_dump(compact("oldChunkXZ", "chunkXZ"));
				}else{
					//NOPE
					return;
				}
			}
			$this->chunkBorder[$player->getName()] = $chunkXZ;
			//echo "Sending chunk border " . $chunkXZ . PHP_EOL;
			//If player is not viewing any chunk border or the player changed from chunk
			$this->plugin->scheduleDelayedCallbackTask(function() use($player, $chunk, $pos, $originalBlock){
				
				//Remove the current chunk border from the player
				$this->sendFakeBlock($player, $pos, BlockFactory::get(Block::STRUCTURE_BLOCK));
				if($player->getLevel() === null){
					return;
				}
				if($player->getLevel()->getChunk($pos->getX() >> 4, $pos->getZ() >> 4, false)){
					$player->getLevel()->sendBlocks([$player], [$pos]);
				}

				//Sends the fake block
				$this->sendFakeBlock($player, $pos, BlockFactory::get(Block::STRUCTURE_BLOCK));
				
				//Sends the structure block tile to the player with a size of 16x16x256 starting from $pos.
				$nbt = new CompoundTag();
				$nbt->setString("id", "StructureBlock");

				$nbt->setInt("data", StructureEditorData::TYPE_EXPORT);
				$nbt->setString("dataField", "");
				$nbt->setByte("ignoreEntities", 0);
				$nbt->setByte("includePlayers", 1);
				$nbt->setFloat("integrity", 1.0);
				$nbt->setByte("isMovable", 0);
				$nbt->setByte("isPowered", 1);
				$nbt->setByte("mirror", 0);
				$nbt->setByte("removeBlocks", 0);
				$nbt->setByte("rotation", 0);
				$nbt->setLong("seed", 0);
				$nbt->setByte("showBoundingBox", 1);
				$nbt->setString("structureName", "Chunk Border");
		
				$nbt->setInt("x", $pos->getX());
				$nbt->setInt("y", $pos->getX());
				$nbt->setInt("z", $pos->getX());

				$nbt->setInt("xStructureOffset", 0);
				$nbt->setInt("yStructureOffset", 0);
				$nbt->setInt("zStructureOffset", 0);
		
				$nbt->setInt("xStructureSize", 16);
				$nbt->setInt("yStructureSize", 256);
				$nbt->setInt("zStructureSize", 16);
		
				$pk = new BlockActorDataPacket();
				$pk->x = $pos->getX();
				$pk->y = $pos->getY();
				$pk->z = $pos->getZ();
				$pk->namedtag = self::$nbtSerializer->write($nbt);
				$player->sendDataPacket($pk);
				
				//Use a chunk manager to replace a block at a certain position whilst not overriding the tile already there
				/*$manager = new SimpleChunkManager($player->getLevel()->getSeed());
				$manager->setChunk($chunk->getX(), $chunk->getZ(), $chunk);
				$manager->setBlockIdAt($originalBlock->getX(), $originalBlock->getY(), $originalBlock->getZ(), $originalBlock->getId());
				$manager->setBlockDataAt($originalBlock->getX(), $originalBlock->getY(), $originalBlock->getZ(), $originalBlock->getDamage());
				$player->getLevel()->setChunk($chunk->getX(), $chunk->getZ(), $manager->getChunk($chunk->getX(), $chunk->getZ()));
			*/
			}, 1);
		}
	}
	
	/**
	 * @param PlayerItemHeldEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerItemHeld(PlayerItemHeldEvent $event){
		$player = $event->getPlayer();
		$item =  $event->getItem();
		if(isset(self::$fishing[$player->getName()])){
			$inventory = $event->getPlayer()->getInventory();
			$oldItem = $inventory->getItemInHand();
			$newItem = $event->getItem();
			if ($oldItem !== $newItem){
				//self::unsetFishing($player);
			}
		}
		if($item instanceof FilledMap){
			$mapData = MapFactory::getInstance()->getMapData($item->getMapId());
			if($mapData instanceof MapData){
				$this->sendExplorerMap($player, $mapData->getMapId(), $mapData);
			}
		}
	}
	
	/**
	 * @api
	 * @param Player $player
	 */
	public static function unsetFishing(Player $player) : void{
		if(isset(self::$fishing[$player->getName()])){
			if(self::$fishing[$player->getName()][1] instanceof FishingHook){
				self::$fishing[$player->getName()][1]->broadcastEntityEvent(ActorEventPacket::FISH_HOOK_TEASE, null, self::$fishing[$player->getName()][1]->getViewers());
				if(!self::$fishing[$player->getName()][1]->isFlaggedForDespawn()){
					self::$fishing[$player->getName()][1]->flagForDespawn();
				}
			}
			unset(self::$fishing[$player->getName()]);
		}
	}
	
	/**
	 * 
	 * @param EntityDamageByEntityEvent $event
	 * @ignoreCancelled true
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		if($damager->namedtag->getTag("EntityCount")){
			$event->setBaseDamage($event->getBaseDamage() * \kenygamer\Core\Main::mt_rand(1, $damager->namedtag->getTag("EntityCount")->getValue()));
		}
		if($damager instanceof Player && ($name = $entity->namedtag->getString("TopPlayerName", "")) !== "" && (!isset($this->fixProfileCrash[$damager->getName()]) || time() - $this->fixProfileCrash[$damager->getName()] > 2)){
			$xuids = array_map("strtolower", Main2::$xuids->getAll());
			if(($xuid = array_search(strtolower($name), $xuids)) !== false){
				$this->fixProfileCrash[$damager->getName()] = time();
				
				//Very very nasty hack to not crash the client re-clicking the NPC (ASSUMING they're lined up :joy:)
				$damager->lookAt($entity);
				$yaw = $damager->getYaw() - 180;
				if($yaw < 0){
					$yaw += 360;
				}
				$pos = $damager->asLocation();
				$pos->yaw = $yaw;
				$damager->teleport($pos);
				$pk = new ShowProfilePacket();
				$pk->xuid = (string) $xuid;
				$damager->dataPacket($pk);
			}
		}
	}
	
	/**
	 * @param EntityDeathEvent $event
	 * @priority NORMAL
	 */
	public function onEntityDeath(EntityDeathEvent $event){
		$entity = $event->getEntity();
		if($entity instanceof Player){
			return;
		}
		foreach($this->plugin->spawners as $spawnerName => $data){
			if($entity::NETWORK_ID === $this->plugin->getEntityId($spawnerName)){
				if(!(($cause = $entity->getLastDamageCause()) instanceof EntityDamageByEntityEvent && ($player = $cause->getDamager()) instanceof Player && $this->plugin->getAffordableSpawner($player) === count($this->plugin->spawners) - 1)){
					$head = ItemFactory::get(Item::SKULL, 0, 1);
					$head->setCustomName(TextFormat::colorize("&r&a" . $spawnerName . " Head"));
					$head->setLore([TextFormat::colorize("&r&7This head gives you " . $data["headhunting"] . " headhunting experience.")]);
					$nbt = $head->getNamedTag();
					$nbt->setInt("EntityId", $entity::NETWORK_ID);
					$head->setNamedTag($nbt);
					$drops[] = $head;
				}
				$tag = $entity->namedtag->getTag("EntityCount");
				
				foreach($data["drops"] as $drop => $chances){
					$item = ItemFactory::fromString((string) $drop);
					$baseDropChance = $chances["baseDropChance"];
					list($min, $max) = explode("-", $chances["dropRange"]);
				    if(\kenygamer\Core\Main::mt_rand(0, 99) <= $baseDropChance){
				    	$drops[] = $item->setCount(\kenygamer\Core\Main::mt_rand((int) $min, (int) $max));
					}
				}
				if($tag instanceof IntTag){
					$multiplier = $tag->getValue();
					foreach($drops as $i => $item){
						$remainder = $item->getCount() * $multiplier;
						while($remainder > $item->getMaxStackSize()){
							$remainder -= $item->getMaxStackSize();
							$drop = ItemFactory::get($item->getId(), $item->getDamage(), $item->getMaxStackSize(), $item->getNamedTag());
							$drops[] = $drop;
						}
						$item->setCount($remainder);
					}
				}
				$event->setDrops($drops);
				break;
			}
		}
	}
	
	/**
	 * @param EntityDamageEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled false
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player){
			if(isset(self::$addNbt[$damager->getName()])){
				foreach(self::$addNbt[$damager->getName()]->getValue() as $tag){
					$entity->namedtag->setTag($tag);
				}
				unset(self::$addNbt[$damager->getName()]);
			}
		}
	}
	
	
	/**
	 * @param PlayerBucketEmptyEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerBucketEmpty(PlayerBucketEmptyEvent $event) : void{
		$player = $event->getPlayer();
		$bucket = $event->getBucket();
		$block = $event->getBlockClicked();
		if($bucket->getNamedTag()->hasTag("GenBucket", IntTag::class)){
			$event->setCancelled();
			$menu = InvMenu::create(InvMenu::TYPE_CHEST);
			$menu->setName(LangManager::translate("genbucket", $player));
			$clickX = $block->getX();
			$clickY = $block->getY();
			$clickZ = $block->getZ();
			$menu->setInventoryCloseListener(function(Player $player, InvMenuInventory $inventory) use($bucket, $clickX, $clickY, $clickZ){
				$error = false;
				$all = $inventory->getContents(false);
				if(empty($all)){
					return;
				}
				foreach($all as $item){
					if(!($item instanceof ItemBlock)){
						$player->sendMessage("genbucket-notblock", $item->getName());
						$error = true;
						break;
					}
					foreach($all as $i){
						if(!$i->equals($item)){
							$player->sendMessage("genbucket-notsame");
							$error = true;
							break 2;
						}
					}
				}
				if($error){
					foreach($all as $item){
						$player->getInventory()->addItem($item);
					}
				}else{
					if($player->getInventory()->contains($bucket)){
					    $level = $player->getLevel();
					    
					    //Stack all items
					    $any = $all[array_key_first($all)];
					    $one = ItemFactory::get($any->getId(), $any->getDamage(), 0);
					    foreach($all as $i){
					    	$one->setCount($one->getCount() + $i->getCount());
					    }
					    $blockWallId = $one->getBlock()->getId();
					    $blockWallData = $one->getBlock()->getDamage();
					    //$clickY is already one block up
						for($y = $clickY; ($y < Level::Y_MAX && $level->getBlockIdAt($clickX, $y, $clickZ) === Block::AIR); $y++){
							$level->setBlockIdAt($clickX, $y, $clickZ, $blockWallId);
							$level->setBlockDataAt($clickX, $y, $clickZ, $blockWallData);
							$one->setCount($one->getCount() - 1);
							if($one->getCount() <= 0){
								break;
							}
						}
						
						$player->getInventory()->addItem($one); //Residue
						$oldBucket = clone $bucket;
						$nbt = $bucket->getNamedTag();
						$nbt->setInt("UsesLeft", $usesLeft = $bucket->getNamedTagEntry("UsesLeft")->getValue() - 1);
						if($usesLeft <= 0){
							$player->getInventory()->removeItem($oldBucket);
						}else{
							$bucket->setNamedTag($nbt);
							$player->getInventory()->setItem($player->getInventory()->first($oldBucket, true), $bucket);
						}
						$player->sendMessage("genbucket-used", $usesLeft);
					}
				}
			});
			$menu->send($player);
		}
	}
	
	/**
	 * @param EntitySpawnEvent $event
	 */
	public function onEntitySpawn(EntitySpawnEvent $event) : void{
		$entity = $event->getEntity();
		if(($rank = $entity->namedtag->getInt("TopPlayer", 0)) > 0){
			$player = Main2::$topPlayersCache[$rank - 1] ?? null;
			if($player === null) return;
			
			$cfg = new Config($this->plugin->getPlugin("LegacyCore")->getDataFolder() . "player/" . $player . ".yml", Config::YAML);
			if($cfg->get("Skin") !== null){
				$entity->setSkin(new Skin(uniqid("", true), base64_decode($cfg->get("Skin"))));
				$entity->setNameTag(LangManager::translate("topplayer-tag", $rank, $player));
				$entity->namedtag->setString("TopPlayerName", $player);
			}
		}
	}

    /**
     * @param PlayerQuitEvent $event
     */
	public function onPlayerQuit(PlayerQuitEvent $event){
		$player = $event->getPlayer();
		self::unsetFishing($player);
		if(class_exists(ScoreHudTask::class)){
			unset(ScoreHudTask::getInstance()->fmaps[$player->getName()]);
		}
		unset(self::$effects[$player->getId()]);
		//Fuck mojang - maybe implement our own onClose() and pill up this code there?
		unset(self::$usingBeacon[$player->getName()]); //This just blocks from other transactions
		foreach(self::$usingEnchantingTable[$player->getName()] ?? [] as $item){
			if($player->getInventory()->canAddItem($item)){
				$player—>getInventory()->addItem($item);
			}else{
				$player->getLevel()->dropItem($player->asVector3(), $item);
			}
		}
		unset(self::$usingEnchantingTable[$player->getName()]);
		foreach(self::$usingAnvil[$player->getName()] ?? [] as $item){
			if($player->getInventory()->canAddItem($item)){
				$player—>getInventory()->addItem($item);
			}else{
				$player->getLevel()->dropItem($player->asVector3(), $item);
			}
		}
		unset(self::$usingAnvil[$player->getName()]);
		
		//My mistake
		foreach(self::$usingChest as $loc => $name){
			if($name === $player->getName()){
				unset(self::$usingChest[$loc]);
			}
		}
		
		RelayThread::relay(
			"[-] " . $player->getName(),
			"",
			[],
			RelayThread::RELAY_THREAD_IN,
			date("[H:i:s]", time()) . " [" . Main::getInstance()->permissionManager->getPlayerGroup($player)->getName() . "] [-] " . $player->getName()
		);
	}
	
	/**
	 * @param PlayerChangeSkinEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerChangeSkin(PlayerChangeSkinEvent $event){
		$player = $event->getPlayer();
		if($player->hasPermission("core.command.cape")){
			Main2::updateCape($player); //TODO: reduce it in one method call?
			Main2::updateCosmetic($player, "", true);
			Main2::updateCosmetic($player, "", false);
		}
	}
	
	/**
	 * Fix exploit by books.
	 *
	 * @param PlayerEditBookEvent $event
	 */
	public function onPlayerEditBook(PlayerEditBookEvent $event) : void{
		if(count($event->getNewBook()->getPages()) >= 50){
            $event->setCancelled();
        }
    }
	
	/**
	 * @param PlayerCommandPreprocessEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		$cmd = explode(" ", $event->getMessage())[0];
		if(in_array(mb_strtolower($cmd), MiscListener::CHAT_COMMANDS)){
			$messages = self::$lastMessages[$player->getName()] ?? [];
			$messages[] = $event->getMessage();
			self::$lastMessages[$player->getName()] = $messages;
		}
	}
	
	/**
	 * @param PlayerChatEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		$message = $event->getMessage();
		$message = preg_replace("/[^A-Za-z0-9 !-_+-=()*\"'.,#€&%§\$£¥^{}[\]]/", "", $event->getMessage()); //Fix PCRE exception (ñ crashes)
		$event->setFormat($this->plugin->permissionManager->getChatFormat($player, $message));
		foreach($event->getRecipients() as $recipient){
			if($recipient instanceof Player){
				$messages = self::$lastMessages[$recipient->getName()] ?? [];
				if(count($messages) === 50){
					array_shift(self::$lastMessages[$recipient->getName()]);
					$messages = self::$lastMessages[$recipient->getName()];
				}
				$messages[] = $event->getMessage();
				self::$lastMessages[$recipient->getName()] = $messages;
			}
		}
	}
	
	/**
	 * @param PlayerChatEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onPlayerChat2(PlayerChatEvent $event) : void{
		$player = $event->getPlayer();
		
		
		RelayThread::relay(
			str_replace($message = $event->getMessage(), "{%0}", $event->getFormat()),
			$event->getMessage(),
			$event->getRecipients(),
			RelayThread::RELAY_THREAD_IN,
			date("[H:i:s]", time()) . " [" . Main::getInstance()->permissionManager->getPlayerGroup($player)->getName() . "] " . $player->getName() . "] > " . TextFormat::clean($message)
		);
		//TODO
		//$event->setCancelled(); //This event will always be canceled in MONITOR, but it will always "actually execute".
		//We will just do the message sending ourselves to localize the messages.
	}
	
	/**
	 * @param PlayerLoginEvent $event
	 * @priority LOWEST
	 */
	public function onPermPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		$this->plugin->permissionManager->updatePermissions($player, true);
	}
	
	public function onPlayerPreLogin(PlayerPreLoginEvent $event) : void{
		$player = $event->getPlayer();
		if(PlayerEvents::getPlayerData($player)["UIProfile"] !== 0){
			$event->setCancelled();
			$event->setKickMessage(LangManager::translate("pocketui", $player));
		}
		$ip = $this->plugin->getConfig()->getNested("staff.ip");
		//var_dump($player->getAddress());
		/*if($this->plugin->isStaff($player) && $player->getAddress() !== $ip){
			$event->setCancelled();
			$event->setKickMessage(TextFormat::colorize("&eGo to Device Network Settings and enable staff VPN."));
		}*/
		self::$fishing[$player->getName()] = [false, null];
	}
    
	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		foreach($this->plugin->blacklist as $server => $arr){
			foreach($arr as $pl){
				if(strcasecmp($pl, $player->getName()) === 0 && !$this->plugin->whitelist->exists($player->getName())){
					$player->sendMessage("blacklist-warning", $server);
				}
			}
		}
		
		MapImageEngine::getInstance()->sendMapsToPlayer($player, true);
		
		$player->setNametag($this->plugin->permissionManager->getNametag($player));
		
		if(Main::getInstance()->getEntry($player, Main::ENTRY_COORDINATES)){
			$player->chat("/coords on");
		}
		if($player->hasPermission("core.command.cape")){
			Main2::updateCape($player);
		}
		if(!isset(ScoreHudTask::getInstance()->fmaps[$player->getName()])){
			ScoreHudTask::getInstance()->fmaps[$player->getName()] = new FactionMap($player);
		}
		//if(!$player->hasPlayedBefore()){
			$this->plugin->playSong(null, $player, 460);
		//}
		RelayThread::relay(
			"[+] " . $player->getName(),
			"",
			[],
			RelayThread::RELAY_THREAD_IN,
			date("[H:i:s]", time()) . " [" . Main::getInstance()->permissionManager->getPlayerGroup($player)->getName() . "] [+] " . $player->getName()
		);
	}

    /**
     * @param PlayerCreationEvent $event
     */
	public function onPlayerCreation(PlayerCreationEvent $event) : void{
		$event->setPlayerClass(ElitePlayer::class);
	}
	
	/**
	 * Attempts to stack the items.
	 *
	 * @param Item $itemClicked
	 * @param Item $itemClickedWith
	 *
	 * @param SlotChangeAction $action
	 * @param SlotChangeAction $otherAction
	 * @param InventoryTransactionEvent $event
	 *
	 * @return bool|Item 
	 */
	public static function attemptStack(Item $itemClicked, Item $itemClickedWith, SlotChangeAction $action = null, SlotChangeAction $otherAction = null, InventoryTransactionEvent $event = null){
		$ret = false;
		if($itemClicked->equals($itemClickedWith, true, false)){
			foreach(["experience_bottle" => ["IsValidBottle", "EXPValue"], "bank_note" => ["IsValidNote", "NoteValue"]] as $item => $tags){
				list($validTag, $valueTag) = $tags;
					    		
		        if($itemClicked->getNamedTag()->hasTag($validTag) && $itemClickedWith->getNamedTag()->hasTag($validTag)){
		        	$sumValue = $itemClicked->getNamedTag()->getInt($valueTag) + $itemClickedWith->getNamedTag()->getInt($valueTag);
		        	$newItem = ItemUtils::get($item . "(" . $sumValue . ")");
		        	if($newItem->getId() !== Item::AIR){
		        		$ret = true;
		        	}
		        	break;
		        }
		    }
		    if($ret){
		    	if($event !== null){
		    		$event->setCancelled();
		    		$action->getInventory()->setItem($action->getSlot(), $newItem);
		    		$otherAction->getInventory()->setItem($otherAction->getSlot(), ItemFactory::get(Item::AIR));
		    	}else{
		    		return $newItem;
		    	}
		    }
		}
		return $ret;
	}
	
	/**
	 * @param InventoryTransactionEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();
		$actions = array_values($transaction->getActions());
		if(count($actions) === 2){
			foreach($actions as $i => $action){
			    if($action->getTargetItem() instanceof WrittenBook || $action->getSourceItem() instanceof WrittenBook){
			     	$bytes = 0;
			    	foreach($player->getLevel()->getChunk($player->getX() >> 4, $player->getZ() >> 4)->getTiles() as $tile){
			    		if($tile instanceof Container){
                			foreach($tile->getRealInventory()->all(ItemFactory::get(Item::WRITTEN_BOOK)) as $book){
                    			$bytes += count($book->getPages()) * 255;
                    		}
                    	}
                    }
                    if($bytes > 50 * 50 * 255){ //50 characters * 50 pages * 50 books = 637500, about 2/3 of a chunk's storage size
                     //... (255 << 12 = 1044480)
                    	$event->setCancelled();
                    }
                }
                
				if($action instanceof SlotChangeAction && ($otherAction = $actions[($i + 1) % 2]) instanceof SlotChangeAction && $action->getInventory() instanceof PlayerInventory && $otherAction->getInventory() instanceof PlayerInventory){
					$itemClicked = $action->getSourceItem();
					$itemClickedWith = $action->getTargetItem();

                    if(self::attemptStack($itemClicked, $itemClickedWith, $action, $otherAction, $event)){
                    	break;
					}
				}
			}
		}
	}
	
	/**
	 * @param Player $player
	 * @param int $mapId
	 * @param MapData $data
	 * @param array $includePlayers
	 */
	public function sendExplorerMap(Player $player, int $mapId, MapData $mapData, array $includePlayers = []) : void{
		$pk = new ClientboundMapItemDataPacket();
		$pk->mapId = $mapId;
		$pk->colors = $mapData->getColors();
		if(count($includePlayers) > 0){
			if($mapData->getDisplayPlayers()){
				foreach($includePlayers as $playerId){
					$target = $player->getServer()->findEntity($playerId);
					$pk->decorations[] = $this->getMapDecoration($mapData, $target);
				}
			}
		}
		$pk->type = ClientboundMapItemDataPacket::BITFLAG_TEXTURE_UPDATE;
		$pk->width = $pk->height = 128;
		$pk->scale = 1;
		$player->dataPacket($pk);
	}
	
	/**
	 * @param MapData $data
	 * @param Player $player
	 * @return MapDecoration
	 */
	private function getMapDecoration(MapData $data, Player $player) : MapDecoration{
		$rotation = $player->getYaw();
		$f1 = $player->getFloorX() - $data->getCenter()->getX();
		$f2 = $player->getFloorZ() - $data->getCenter()->getZ();
		$b1 = (int) (($f1 * 2.0) + 0.5);
		$b2 = (int) (($f2 * 2.0) + 0.5);
		$j = 63;
		
		$rotation = $rotation + ($rotation < 0.0 ? -8.0 : 8.0);
		$b3 = ((int) ($rotation * 16.0 / 360.0));
		if($f1 <= -$j){
			$b1 = (int) (($j * 2) + 2.5);
		}
		if($f2 <= -$j){
			$b2 = (int) (($j * 2) + 2.5);
		}
		if($f1 >= $j){
			$b1 = (int) ($j * 2 + 1);
		}
		if($f2 >= $j){
			$b2 = (int) ($j * 2 + 1);
		}
		return new MapDecoration(0, $b3, $b1, $b2, $player->getName(), $color ?? new Color(255, 255, 255));
	}
	
	/**
	 * @param DataPacketReceiveEvent $event
	 * @ignoreCancelled true
	 */
	public function onDataPacketReceive(DataPacketReceiveEvent $event) : void{
		$player = $event->getPlayer();
		$pk = $event->getPacket();
		
		//Image Engine
		if($pk instanceof MapInfoRequestPacket){
			//MapInfoRequestPacket->mapId is int
			
			MapImageEngine::getInstance()->sendMapsToPlayer($player);
			
			$mapData = MapFactory::getInstance()->getMapData($pk->mapId);
			if($mapData !== null){
				$event->setCancelled();
				$this->sendExplorerMap($player, $pk->mapId, $mapData);
			}

		}
		
		if($pk instanceof ActorEventPacket && $pk->event === ActorEventPacket::COMPLETE_TRADE){
			$recipe = self::$tradeOffers->getListTag("Recipes")->get($pk->data/*int*/);
			if($recipe instanceof CompoundTag){
				$buy = Item::nbtDeserialize($recipe->getCompoundTag("buyA"));
				$sell = Item::nbtDeserialize($recipe->getCompoundTag("sell"));
				if($player->getInventory()->contains($buy)){
					$player->getInventory()->removeItem($buy);
					$player->getInventory()->addItem($sell);
				}
			}
		}
		
		if($pk instanceof LoginPacket){
 			if($pk->clientUUID !== null && isset(self::$switcher[$pk->clientUUID])){
 				$pk->username = self::$switcher[$pk->clientUUID];
 			}
			
			$pk->protocol = ProtocolInfo::CURRENT_PROTOCOL;
 		}
		if($pk instanceof EmotePacket){
			$emote = EmotePacket::create($player->getId(), $pk->getEmoteId(), 0 << 1);
			$player->getServer()->broadcastPacket($player->getViewers(), $pk);
		}
		
		// Minecarts (Experimental)
		if($pk instanceof PlayerInputPacket){
			if(isset(self::$riding[$player->getName()])){
				self::$riding[$player->getName()]->setCurrentSpeed($pk->motionY);
				$event->setCancelled();
			}
		}
		
		if($pk instanceof ContainerClosePacket){
			//Anvil / Enchanting Table
			if(array_key_exists($pk->windowId, $player->openHardcodedWindows)){
				$player->doCloseInventory();
				$pk2 = new ContainerClosePacket();
				$pk2->windowId = $pk->windowId;
				$player->sendDataPacket($pk2);
				unset($player->openHardcodedWindows[$pk->windowId]);
				
				//TODO 1.16 hacks!
				if($pk->windowId === ElitePlayer::HARDCODED_ANVIL_WINDOW_ID){
					unset(self::$usingAnvil[$player->getName()]);
				}
				if($pk->windowId === ElitePlayer::HARDCODED_ENCHANTING_TABLE_WINDOW_ID){
					unset(self::$usingEnchantingTable[$player->getName()]);
				}
			}
			
			//Container Close Rate-Limit
			if(isset(self::$containerOpen[$player->getName()]) && self::$containerOpen[$player->getName()] === WindowTypes::WORKBENCH){//2
				self::$closedCraftingTable[$player->getName()] = microtime(true);
			}
			unset(self::$containerOpen[$player->getName()]);
			$this->closePacketRateLimit[$player->getName()] = microtime(true);
			
			if(isset(self::$trading[$player->getName()])){
				$pk = new ContainerClosePacket();
				$pk->windowId = 255;
				$player->dataPacket($pk);
				unset(self::$trading[$player->getName()]);
			}
		}
		
		// MCPE v1.16
		if($pk instanceof InventoryTransactionPacket){
			$actions = $pk->actions; //NetworkInventoryAction[]
			foreach($actions as $action){
				/*var_dump("---------- NetworkInventoryAction ----------");
				var_dump($action->sourceType . ":" . $action->windowId . ":" . $action->inventorySlot);
				var_dump("Old item: " . $action->oldItem->getId());
				var_dump("New item: " . $action->newItem->getId());
				var_dump("----------                        ----------");*/
			}
			
			$inventoryActions = [];
			$isCraftingPart = false;
			foreach($actions as $action){
				if($action->sourceType === NetworkInventoryAction::SOURCE_TODO && ($action->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_RESULT || $action->windowId === NetworkInventoryAction::SOURCE_TYPE_CRAFTING_USE_INGREDIENT)){
					$isCraftingPart = true;
				}
				if($isCraftingPart){
					goto skipCraftingBug;
				}
				try{
					$inventoryAction = $action->createInventoryAction($player);
					if($inventoryAction !== null){
						$inventoryActions[] = $inventoryAction;
					}
				}catch(\UnexpectedValueException $e){
					goto skipCraftingBug;
				}
			}
			//Crafting bug
			switch($pk->transactionType){
				case InventoryTransactionPacket::TYPE_NORMAL:
					$player->setUsingItem(false);
					$transaction = new InventoryTransaction($player, $inventoryActions);
					try{
						$transaction->execute();
					}catch(TransactionValidationException $e){
						/*var_dump($e->getMessage());
						var_dump($e->getTraceAsString());*/
						$player->sendMessage("inventory-nospace");
					}
					echo "[CANCELED THE EVENT]\n";
					$event->setCancelled();
					break;
			}
			skipCraftingBug:
			//My mistake god
			/*if(count($actions) === 2){
				if($actions[0]->windowId === 0 && $actions[1]->windowId === 0){
					goto skip; //Do not handle within player inventory
			    }
			    if($actions[0]->windowId === $actions[1]->windowId){
					//TODO: Filter out crafting grid, etc.
			        //$event->setCancelled();
					
			    	goto skip; //Cancel and do not handle within menu
			    }
			}
			//My mistake - chests
			foreach(self::$usingChest as $loc => $name){
				if($name === $player->getName()){
				   $event->setCancelled();
				    
				   
					list($x, $y, $z) = explode(":", $loc);
					$tile = $player->getLevel()->getTile(new Vector3((int) $x, (int) $y, (int) $z));
					if($tile === null){
						continue;
					}
					foreach($actions as $i => $action){
						
						if(!$action->newItem->isNull() && ($otherAction = ($actions[($i + 1) % 2] ?? null)) && $otherAction->newItem->isNull()){
							$item = $action->newItem;
							
							$isTakeout = $actions[0]->windowId !== 0;
							$checkInventory = $isTakeout ? $tile->getInventory() : $player->getInventory();
							$slot = ItemUtils::findItem($checkInventory, $item);
							if($slot > -1){
								if(!$isTakeout){
									$player->getInventory()->setItem($slot, ItemFactory::get(Item::AIR));
									$tile->getInventory()->addItem($item);
								}else{
									$tile->getInventory()->setItem($slot, ItemFactory::get(Item::AIR));
									$player->getInventory()->addItem($item);
								}
								break 2;
							}
						}
					}
				}
			}
			//My mistake - inventory
			if(isset(self::$changingInventory[$player->getName()])){
			    $event->setCancelled();
				
				foreach($actions as $i => $action){
					if(!$action->newItem->isNull() && ($otherAction = ($actions[($i + 1) % 2] ?? null)) && $otherAction->newItem->isNull()){
						$item = $action->newItem;
						$isTakeout = $actions[0]->windowId !== 0;
						
					    $menuInventory = self::$changingInventory[$player->getName()][0];
						$checkInventory = $isTakeout ? $menuInventory : $player->getInventory();
						$slot = ItemUtils::findItem($checkInventory, $item);
						if($slot > -1){
							if(!$isTakeout){
							     //Fix index jndex invalid or out of range
							    if($action->inventorySlot <= 3 && $menuInventory->getItem($action->inventorySlot)->isNull() && $player->getArmorInventory()->getItem($action->inventorySlot)->isNull()){
									$player->getInventory()->setItem($slot, ItemFactory::get(Item::AIR));
									$menuInventory->setItem($action->inventorySlot, $item);
									self::$changingInventory[$player->getName()][1][strval($action->inventorySlot)] = $item;
								}else{
									//NOPE
								}
							}else{
								//If you can't put items in out of range you can't take out
								$menuInventory->setItem($slot, ItemFactory::get(Item::AIR));
								$player->getInventory()->addItem($item);
								
								$slot = ItemUtils::findItem(self::$changingInventory[$player->getName()][1], $item);
								if($slot > -1){
									unset(self::$changingInventory[$player->getName()][1][$slot]);
								}
							}
							break;
						}
					}
				}
			}
			skip:
			if($pk->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM && $pk->trData->actionType === InventoryTransactionPacket::USE_ITEM_ACTION_CLICK_BLOCK && (!isset(self::$fixWin10SpamBug[$player->getName()]) || time() - self::$fixWin10SpamBug[$player->getName()] > 2)){
				foreach(self::$usingChest as $loc => $name){
					if($name === $player->getName()){
						unset(self::$usingChest[$loc]);
					}
				}
				unset(self::$changingInventory[$player->getName()]);
			}*/
			
			//Trading
			if(isset(self::$trading[$player->getName()]) && $pk->transactionType === InventoryTransactionPacket::TYPE_NORMAL){
				$event->setCancelled();
				foreach($pk->actions as $action){
					if(self::$trading[$player->getName()] === $action->windowId){
						//Idk
					}
				}
			}
			
			//Beacon
			if(isset(self::$usingBeacon[$player->getName()])){	
				$event->setCancelled();
				Main::getInstance()->closeWindow($player, WindowTypes::CONTAINER, true);
				foreach($pk->actions as $action){
					if(true){
						if(($slot = $player->getInventory()->first($action->newItem)) !== -1){
							$player->getInventory()->setItem($slot, $player->getInventory()->getItem($slot)->pop());
							$pos = self::$usingBeacon[$player->getName()];
							if(!$pos->getLevel()->isClosed() && ($tile = $pos->getLevel()->getTile($pos)) instanceof BeaconTile){
								$tile->getInventory()->setItem(0, $action->newItem->setCount(1)); //should be 1 anyways
							}
							break;
						}
					}	
				}
			}
			unset(self::$usingBeacon[$player->getName()]);
			
			// Enchanting Table
			if(isset(self::$usingEnchantingTable[$player->getName()])){
				$event->setCancelled();
				for($i = 1; $i <= 2; $i++){
					if(!isset(self::$usingEnchantingTable[$player->getName()][$i])){
						foreach($actions as $i => $action){
							if(!$action->newItem->isNull()){
								self::$usingEnchantingTable[$player->getName()][$i] = $action->newItem;
								return;
							} 
						}
					}
				}
				foreach($actions as $action){
					if($action->sourceType === NetworkInventoryAction::SOURCE_TODO && $action->windowId === NetworkInventoryAction::SOURCE_TYPE_ENCHANT_OUTPUT){ //Validation
						$result = $action->oldItem;
						
						foreach([$item1 = self::$usingEnchantingTable[$player->getName()][1], $item2 = self::$usingEnchantingTable[$player->getName()][2]] as $item){
							if($player->getInventory()->first($item, true) < 0){
								Main::getInstance()->closeWindow($player, WindowTypes::CONTAINER, true);
								unset(self::$usingEnchantingTable[$player->getName()]);
								break 2;
							}
						}
						if($item1->getId() === Item::DYE && $item1->getId() === 4){ //Lapis
						    $lapis = $item1;
						    $item = $item2;
						}else{
							$lapis = $item2;
							$item = $item1;
						}
						
						foreach($result->getEnchantments() as $enchantment){
							$found = false;
							foreach($item->getEnchantments() as $_enchantment){
								if($enchantment->getId() === $_enchantment->getId()){
									$found = true;
									break;
								}
							}
							if(!$found){
								$eid = $enchantment->getId();
								if(!in_array($eid, Main2::VANILLA_ENCHANT_LIST)){
									$eid = Main2::VANILLA_ENCHANT_LIST[array_rand(Main2::VANILLA_ENCHANT_LIST)];
								}
								$itemSlot = $player->getInventory()->first($item, true);
								
								if(!($item instanceof TieredTool || $item->getId() === Item::BOOK)){
									break; //Close
								}
								if(Main::getInstance()->rankCompare($player, "Ultimate") >= 0){
									$lvl = \kenygamer\Core\Main::mt_rand(1, min($enchantment->getType()->getMaxLevel(), intval(21 / self::$usingEnchantingTable[$player->getName()][0])));
								}else{
									$lvl = 1;
								}
								if($item->getCount() > 1){
									break;
								}
								if($item->getId() === Item::BOOK){
									$enchantedItem = ItemFactory::get(Item::ENCHANTED_BOOK, 0, 1);
									$enchantedItem->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($eid), $lvl));
									if($player->getInventory()->canAddItem($enchantedItem)){
										$player->getInventory()->setItem($itemSlot, $item->setCount($item->getCount() - 1));
										$player->getInventory()->addItem($enchantedItem);
									}else{
										break; //Close
									}
								}else{
									$item->addEnchantment(new EnchantmentInstance(Enchantment::getEnchantment($eid), $lvl));
									$player->getInventory()->setItem($itemSlot, $item);
								}
								$player->getInventory()->setItem($player->getInventory()->first($lapis, true), $lapis->setCount($lapis->getCount() - \kenygamer\Core\Main::mt_rand(1, 3)));
								Main::getInstance()->closeWindow($player, WindowTypes::CONTAINER, true);
								unset(self::$usingEnchantingTable[$player->getName()]);
								break 2;
							}
						}
						Main::getInstance()->closeWindow($player, WindowTypes::CONTAINER, true);
						unset(self::$usingEnchantingTable[$player->getName()]);
						break;
					}
				}
			}
			
			// Anvil
			//if(count($actions) === 2 && $action->windowId === ($otherAction = $actions[($i + 1) % 2])->windowId ||
			if(isset(self::$usingAnvil[$player->getName()])){
				$event->setCancelled();
				$repairItem = CustomItems::getBrokenKey();
				$inputs = [CustomItems::getYellowCrystal(), CustomItems::getRedCrystal(), CustomItems::getGreenCrystal(), CustomItems::getBlueCrystal(), CustomItems::getPurpleCrystal()];
				$outputs = [CustomItems::getYellowKey(), CustomItems::getRedKey(), CustomItems::getGreenKey(), CustomItems::getBlueKey(), CustomItems::getPurpleKey()];
				
				if(!isset(self::$usingAnvil[$player->getName()][1])){
					foreach($actions as $action){
						if(!$action->newItem->isNull()){
							self::$usingAnvil[$player->getName()][1] = $action->newItem;
						}
					}
				}else{ //Validation
				    $item2 = null;
				    foreach($actions as $action){
				    	if(!$action->newItem->isNull()){
				    		$item2 = $action->newItem;
				    	}
					}
					if($item2 !== null){
						$item1 = self::$usingAnvil[$player->getName()][1];
						unset(self::$usingAnvil[$player->getName()][1]);
						
						$repair = null;
						$input = null;
						foreach([$item1, $item2] as $item){
							if($item->equals($repairItem)){
								$repair = $item;
								continue;
							}
							foreach($inputs as $crystal){
								if($item->equals($crystal)){
									$input = $item;
									break;
								}
							}
						}
						if($repair === null || $input === null){
							$player->sendMessage("brokenkey-inputs");
							if(isset(self::$usingAnvil[$player->getName()])){
								Main::getInstance()->closeWindow($player, WindowTypes::CONTAINER, true);
								unset(self::$usingAnvil[$player->getName()]);
							}
						}else{
							if(($repairSlot = $player->getInventory()->first($repair, true)) >= 0 && ($inputSlot = $player->getInventory()->first($input, true)) >= 0){ //Prevent exploit
								$success = 0;
								while(!$repair->isNull()){
									$inputCount = $input->getCount();
									if($inputCount - 5 < 0){
										break;
									}
									$input->setCount($input->getCount() - 5);
									$repair->setCount($repair->getCount() - 1);
									if(\kenygamer\Core\Main::mt_rand(0, 9) === 0){
										$success++;
									}
								}
								
								//Find index
								foreach($inputs as $index => $item){
									if($item->equals($input)){
										break;
									}
								}
								$result = $outputs[$index]->setCount($success);
								if($player->getInventory()->canAddItem($result)){
									$player->getInventory()->addItem($result);
									if($success > 0){
										$player->sendMessage("brokenkey-repair", $success);
									}else{
										$player->sendMessage("brokenkey-repairnone");
									}
									$player->getInventory()->setItem($repairSlot, $repair);
									$player->getInventory()->setItem($inputSlot, $input);
								}else{
									$player->sendMessage("inventory-nospace");
								}
								Main::getInstance()->closeWindow($player, WindowTypes::CONTAINER, true);
								unset(self::$usingAnvil[$player->getName()]);
								Main::getInstance()->questManager->getQuest("blacksmith")->progress($player, 1);
							}
						}
					}
				}
			}
		}
	}
	
	private static function getNetworkCommandParamType(?string $type) : int{
		$bitmask = AvailableCommandsPacket::ARG_FLAG_VALID;
		switch($type ?? "rawtext"){
			case "string":
			   return $bitmask | AvailableCommandsPacket::ARG_TYPE_STRING;
			   break;
			case "int":
			   return $bitmask | AvailableCommandsPacket::ARG_TYPE_INT;
			   break;
			case "float":
			   return $bitmask | AvailableCommandsPacket::ARG_TYPE_FLOAT;
			   break;
			case "target":
			   return $bitmask | AvailableCommandsPacket::ARG_TYPE_TARGET;
			   break;
			case "text":
			case "rawtext":
			   return $bitmask | AvailableCommandsPacket::ARG_TYPE_RAWTEXT;
			   break;
			case "enum":
			   return $bitmask | AvailableCommandsPacket::ARG_FLAG_ENUM;
			   break;
			default:
			   throw new \InvalidArgumentException("Command param type " . $type . " not recognized");
		}
	}
	
	/**
	 * @param DataPacketSendEvent $event
	 * @ignoreCancelled true
	 */
	public function onDataPacketSend(DataPacketSendEvent $event) : void{
		$player = $event->getPlayer();
		$pk = $event->getPacket();
		
		//MCPE v1.16 Fix https://github.com/pmmp/PocketMine-MP/issues/3676
		if($pk instanceof InventorySlotPacket){
			
			$event->setCancelled();
			/*$pk->inventorySlot = 0;
			$wrapper = $pk->item; //ItemStackWrapper
			$property = new \ReflectionProperty($wrapper, "itemStack");
			$property->setAccessible(true);
			$item = $property->getValue($wrapper);
			if($item->getId() === Item::NETHERITE_PICKAXE){ //This is in fork
				$item = $item->clearNamedTag();
				$item = ItemFactory::get(Item::AIR);
				$property->setValue($wrapper, $item);
			}
			//var_dump($pk);*/
		}
		
		//Item Cases
		if($pk instanceof LevelChunkPacket){
			$level = $player->getLevel();
        	if(isset($this->plugin->itemCases[$level->getFolderName()])){
        		$chunkX = $pk->chunkX;
        		$chunkZ = $pk->chunkZ;
        		foreach(array_keys($this->plugin->itemCases[$level->getFolderName()]) as $cid){
            		$pos = explode(":", $cid);
            		if($pos[0] >> 4 == $chunkX && $pos[2] >> 4 == $chunkZ){
                		$this->plugin->spawnItemCase($level, $cid, $player);
					}
				}
            }
        }
		
		if($pk instanceof ContainerOpenPacket){//$pk->x, $pk->y, $pk->z
			//$pk->type = -1 is inventory, 0 is chest
			//$pk->windowId = inventory is always 92
			self::$containerOpen[$player->getName()] = $pk->type;
		}
		
		// Container Close Rate-Limit
		if($pk instanceof ContainerClosePacket){
			$diff = microtime(true) - ($this->closePacketRateLimit[$player->getName()] ?? 0);
			//var_dump($diff);
			if($diff > 0.05	&& $diff < 0.6){
				$event->setCancelled();
			}elseif($diff > 0.05){
				$this->closePacketRateLimit[$player->getName()] = microtime(true);
			}
		}
		
		// Coordinates
		if($pk instanceof GameRulesChangedPacket){
			if(isset($pk->gameRules["showcoordinates"])){
			    $showcoordinates = $pk->gameRules["showcoordinates"];
				if($showcoordinates === [1, true]){
					Main::getInstance()->registerEntry($player, Main::ENTRY_COORDINATES, true);
				}elseif($showcoordinates === [1, false]){
					Main::getInstance()->registerEntry($player, Main::ENTRY_COORDINATES, false);
				}
			}
		}
		// Effect Queue
		if($pk instanceof MobEffectPacket){ // && $player->isOnline()){
		    $event->setCancelled();
			$id = $player->getId();
			$pk->duration += 15; //Send compensation
			
			if($pk->entityRuntimeId === $id){
				if(isset(self::$effects[$id][$pk->effectId])){
					$queue = self::$effects[$id][$pk->effectId];
					
					switch($pk->eventId){
						case MobEffectPacket::EVENT_ADD:
						case MobEffectPacket::EVENT_MODIFY:
						    if($queue->eventId === MobEffectPacket::EVENT_ADD || $queue->eventId === MobEffectPacket::EVENT_MODIFY){
						    	if($pk->duration > $queue->duration){
						    		$add = $pk->duration - $queue->duration;
						    		if($queue->duration + $add > 0x7fffffff){
						    			$queue->duration = 0x7fffffff;
						    		}else{
						    			$queue->duration += $add;
						    		}
						    	}
						    }else{ //Remove
						    	$queue->eventId = MobEffectPacket::EVENT_MODIFY;
						    	$queue->duration = $pk->duration; //New duration
						    }
						    break;
						case MobEffectPacket::EVENT_REMOVE:
						    $queue->eventId = MobEffectPacket::EVENT_REMOVE;
						    break;
					}
				}else{
					$queue = new \StdClass();
					$queue->eventId = $pk->eventId;
					$queue->effectId = $pk->effectId;
					
					$queue->amplifier = $pk->amplifier ?? 0;
					$queue->particles = $pk->particles ?? false;
					$queue->duration = $pk->duration ?? 0;
					self::$effects[$id][$pk->effectId] = $queue;
				}
			}
			$event->setCancelled();
		}
		
		// Command Autocompletion
		if($pk instanceof AvailableCommandsPacket){
			$commands = array_unique(Main::getInstance()->getConfig()->get("commands", []), SORT_REGULAR);
			$map = Server::getInstance()->getCommandMap();
			//$pk->commandData = [];
			foreach($commands as $cmd => $arguments){
				$command = $map->getCommand($cmd);
				if($command !== null && $command->testPermissionSilent($player)){
					$commandData = new CommandData();
					$commandData->commandName = mb_strtolower($command->getName());
					$commandData->commandDescription = $command->getDescription();
					$commandData->flags = 0;
					$commandData->permission = 0;
					
					$commandAliases = new CommandEnum();
					$commandAliases->enumName = ucfirst($command->getName()) . "Aliases";
					
					if(!empty($command->getAliases())){
						foreach($command->getAliases() as $alias){
							$commandAliases->enumValues[] = $alias;
						}
						$commandAliases->enumValues[] = $command->getName();
						$commandData->aliases = $commandAliases;
					}
					
					$i = 0;
					
					$hasSubCommands = null;
					$overloads = [];
					foreach($arguments as $argument => $sub){
						
						if(isset($sub["type"])){
							if($hasSubCommands === true){
								throw new \LogicException($command->getName() . " has subcommands in the root, it cannot have parameters");
							}
							$hasSubCommands = false;
							
							$commandParam = new CommandParameter();
							$commandParam->paramName = $argument;
							$commandParam->paramType = self::getNetworkCommandParamType(($type = $sub["type"] ?? null));
							$commandParam->isOptional = boolval($sub["isOptional"] ?? false);
							$commandParam->flags = 0;
							//Overlaps the no subcommands-subcommands validation rule?
							if($type === "enum"){
								$paramEnum = new CommandEnum();
								$paramEnum->enumName = $argument;
								$paramEnum->enumValues = array_map("strval", $sub["enum"] ?? []);
								$commandParam->enum = $paramEnum;
							}
							$overloads[$i][] = $commandParam;
						
						}else{
							$genericParam = new CommandParameter();
							$genericParam->paramName = $argument; //subcommand
							$genericParam->paramType = self::getNetworkCommandParamType("string");
							$genericParam->isOptional = false;
							$genericParam->flags = 0;
						
							$genericEnum = new CommandEnum();
							$genericEnum->enumName = $argument; //subcommand
							$genericEnum->enumValues = [$argument]; //subcommand
							$genericParam->enum = $genericEnum;
						
							$overloads[$i][] = $genericParam;
						    
						    if($hasSubCommands === false){
						    	throw new \LogicException($command->getName() . " has parameters in the root, it cannot have subcommands");
						    }
						    $hasSubCommands = true;
						    if(count($sub) > 0){
						    	
						    	foreach($sub as $arg => $data){
						    		$commandParam = new CommandParameter();
									$commandParam->paramName = $arg;
									$commandParam->paramType = self::getNetworkCommandParamType($data["type"] ?? null);
									$commandParam->isOptional = boolval($data["isOptional"] ?? false);
									$commandParam->flags = 0;
							
									$overloads[$i][] = $commandParam;
								}
							}
						}
						
						//If this command doesn't have subcommands only the first CommandData->overloads index will be used.
						if($hasSubCommands){ 
							$i++;
						}
					}
					$commandData->overloads = $overloads;
					$pk->commandData[$command->getName()] = $commandData;
				}
			}
		}
	}
	
	/**
	 * @param EntityTeleportEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
			$to = $event->getTo();
			$areas = Area::getInstance()->cmd->areas;
			$toLevel = $to->getLevel() === null ? $entity->getLevel() : $to->getLevel();
			foreach($areas as $area){
				if(strpos($area->getName(), str_replace("{player}", "", Main2::PMINE_AREA)) !== false && $area->contains($to->asVector3(), $toLevel->getFolderName()) && $area->getName() !== str_replace("{player}", mb_strtolower($entity->getName()), Main2::PMINE_AREA) && !$entity->isOp()){
					$entity->sendMessage("pmine-tperror");
					$event->setCancelled();
					break;
				}
			}
			if($toLevel !== $entity->getLevel()){
				$this->plugin->despawnItemCases($entity->getLevel(), $entity);
			}
		}
	}
    
	/**
	 * @param ExplosionPrimeEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onExplosionPrime(ExplosionPrimeEvent $event) : void{
		$entity = $event->getEntity();
		if($entity::NETWORK_ID === Entity::CREEPER && $entity->namedtag->hasTag("Bomby", StringTag::class)){
			$event->setCancelled(); //Override block-breaking
			
			$player = $entity->namedtag->getString("Bomby");
			$p = $this->plugin->getServer()->getPlayerExact($player);
			if($p !== null){
				$p->kill();
				LangManager::broadcast("death-explosion", $player);
			}
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 * @priority MONITOR
	 */
	public function onInteractFixSign(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if($block->getId() === Block::SIGN_POST){
			$this->fixSignBug[$player->getName()] = $event->getFace();
		}
	}
	
	/**
	 * @param BlockPlaceEvent $event
	 * @priority MONITOR
	 */
	public function onPlaceFixSign(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		//Fix bug: Tile not being created when tile block is placed
		if(!$event->isCancelled() && isset($this->fixSignBug[$player->getName()])){
			//Null because what block data?
			//Tile::createTile(Tile::SIGN, $player->getLevel(),  Sign::createNBT($block->asVector3(), $this->fixSignBug[$player->getName()], null, $player));
			unset($this->fixSignBug[$player->getName()]);
		}
	}
		
	/**
	 * @param BlockPlaceEvent $event
	 * @priority NORMAL
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		
		$item = $event->getItem();
		if($item->getId() === Item::SKULL){
			$event->setCancelled();
		}
		$block = $event->getBlock();
		
		if($item->getId() === Item::CHEST){
			$chests = []; 
			if(($aChest = $block->getLevel()->getBlock($block->add(1)))->getId() === Block::CHEST){
				$chests[] = $aChest;
			}
			if(($aChest = $block->getLevel()->getBlock($block->add(-1)))->getId() === Block::CHEST){
				$chests[] = $aChest;
			}
			if(($aChest = $block->getLevel()->getBlock($block->add(0, 0, 1)))->getId() === Block::CHEST){
				$chests[] = $aChest;
			}
			if(($aChest = $block->getLevel()->getBlock($block->add(0, 0, -1)))->getId() === Block::CHEST){
				$chests[] = $aChest;
			}
			foreach($chests as $chest){
				if($this->plugin->getChestInfo($chest)->attribute !== Main::PG_NOT_LOCKED){
					$event->setCancelled();
					break;
				}
            }
		}
		
		/*for($x = -4; $x < 4; $x++){
			for($y = -4; $y < 4; $y++){
				for($z = -4; $z < 4; $z++){
					if(($pos = $player->getLevel()->getBlock($block->add($x, $y, $z)))->getId() === Item::ENCHANTING_TABLE){
						$diff = intval(round($pos->x - $block->x)) . ":";
						$diff .= intval(round($pos->y - $block->y)) . ":";
						$diff .= intval(round($pos->z - $block->z));
						file_put_contents("/home/coords.txt", $diff . "\n", FILE_APPEND);
						break 3;
					}
				}
			}
		}*/
	}
	
	/**
	 * @param BlockBreakEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		
		//Item Cases
        $yOff = $block->getId() !== Block::GLASS ? 1 : 0;
        $cid = implode(":", [$block->getX(), $block->getY() + $yOff, $block->getZ()]);
        if(isset($this->plugin->itemCases[$player->getLevel()->getFolderName()][$cid])){
            if(!$player->isOp()){
				$event->setCancelled();
            }else{
				$this->plugin->despawnItemCase($player->getLevel(), $cid);
				unset($this->plugin->itemCases[$cid]);
            	$player->sendMessage("ic-destroy", $cid);
			}
        }
		
		//Spawners
		$tile = $block->getLevel()->getTile($block->asVector3());
		if($tile instanceof MobSpawner){
			if(!empty($tile->boosterTimes)){
				if(!(isset($this->fixSpamBug[$player->getName()]) && !(microtime(true) - $this->fixSpamBug[$player->getName()] > 0.01))){
					$this->fixSpamBug[$player->getName()] = microtime(true);
					$loc = $tile->asPosition()->__toString();
					SpawnerTask::removeText($loc, $block->getLevel());
					$player->sendMessage("spawners-booster-paused", count($tile->boosterTimes));
				}
			}
		}
		
		//Shops
		
		if(Main2::$shop->get($block->getX() . ":" . $block->getY() . ":" . $block->getZ(). ":" . $block->getLevel()->getFolderName())){
			if(!$player->isOp()){
				$event->setCancelled();
			}else{
				Main2::$shop->remove($block->getX() . ":" . $block->getY() . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName());
				$player->sendMessage("shop-removed");
			}
		}
		
		//Bomby
		if(\kenygamer\Core\Main::mt_rand(1, 30000) < 2 && !$player->isOp()){
			$points = 0;
			$armor = $player->getArmorInventory();
			if($armor !== null){
				foreach($armor->getContents() as $piece){
					if($piece->hasEnchantment(CustomEnchantsIds::BOMBYPROTECTION)){
						$points += 1;
					}
				}
				if(!($points >= 4)){
					$entity = PureEntities::create(Entity::CREEPER, $block->asPosition());
					if($entity !== null){
						$entity->namedtag->setString("Bomby", $player->getName());
						$entity->setNameTag(TextFormat::BOLD . TextFormat::DARK_GREEN . "Bomby");
						$entity->setNameTagAlwaysVisible(true);
						$entity->setTargetEntity($player);
						$entity->spawnToAll();
					}
				}
			}
		}
		
		//Locked chests
		if($block->getId() === Block::CHEST){
			$info = $this->plugin->getChestInfo($block);
			if($info->attribute !== Main::PG_NOT_LOCKED){
            	$pairChestTile = null;
            	if(($tile = $block->getLevel()->getTile($block)) instanceof Chest){
					$pairChestTile = $tile->getPair();
				}
            	if($info->owner === $player->getName()){
                	$this->plugin->unlockChest($block);
					if($pairChestTile instanceof Chest){
						$this->plugin->unlockChest($pairChestTile);
					}
				}
				$player->sendMessage("pg-unlock");
			}elseif($info->owner !== "" && $info->owner !== $player->getName() && !$player->isOp()){
                $player->sendMessage("pg-info");
                $event->setCancelled();
            }
        }
	}
	
	/**
	 * @param PlayerItemConsumeEvent $event
	 */
	public function onPlayerItemConsume(PlayerItemConsumeEvent $event) : void{
		$player = $event->getPlayer();
		$item = $event->getItem();
		if(in_array($item->getId(), [Item::APPLE, Item::COOKIE, Item::MELON, Item::COOKED_CHICKEN, Item::BAKED_POTATO, Item::PUMPKIN_PIE])){
			$effect = new EffectInstance(Effect::getEffect(Effect::REGENERATION), 10 * 20, 0, true);
			$player->addEffect($effect);
			$effect = new EffectInstance(Effect::getEffect(Effect::ABSORPTION), 30 * 20, 0, true);
			$player->addEffect($effect);
		}
	}
	
	/**
	 * @param PlayerLoginEvent $event
	 * @priority HIGHEST
	 *
	 * Assumed
	 */
	public function onPlayerLogin(PlayerLoginEvent $event) : void{
		$player = $event->getPlayer();
		$xuid = $player->getXuid();
		if($xuid !== ""){ //Logged in to Xbox Live
		    if(Main2::$xuids->get($xuid) === false){ //Do not override. This makes the /transfer command possible.
		    	Main2::$xuids->set($xuid, $player->getName());
		    }
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled false
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$item = $player->getInventory()->getItemInHand();
		if($block->getId() !== Block::ITEM_FRAME_BLOCK){
			ItemUtils::attemptRedeemGemContainer($player);
		}
		
		if(!$event->isCancelled()){
			//Image Place
			if(isset(self::$placeImage[$player->getName()])){
				$event->setCancelled();
				if($block->getId() !== Block::ITEM_FRAME_BLOCK){
					$player->sendMessage(TextFormat::GRAY . "Click an item frame.");
					goto skipImagePlace;
				}
				
				$tile = $block->getLevel()->getTile($block->asVector3());
				if(!($tile instanceof ItemFrame)){
					$player->sendMessage(TextFormat::GRAY . "Item frame is invalid.");
					goto skipImagePlace;
				}
				if(!$tile->getItem()->isNull()){
					$player->sendMessage(TextFormat::GRAY . "The item frame is already filled.");
					goto skipImagePlace;
				}
				
				list($images, $current) = self::$placeImage[$player->getName()];
				$colors = $images[$current];
				$map_uuid = (string) \kenygamer\Core\Main::mt_rand(-0x7fffffff, 0x7fffffff);
				
				$item = ItemFactory::get(Item::FILLED_MAP, 0, 1);
				$nbt = $item->getNamedTag();
				$nbt->setString("map_uuid", $map_uuid);
				$item->setNamedTag($nbt);
				$tile->setItem($item);
				
				MapImageEngine::getInstance()->saveMap($map_uuid, $colors);
				MapImageEngine::getInstance()->regenerateCache($player->asPosition());
				if(!isset($images[$current + 1])){
					$player->sendMessage(TextFormat::GREEN . "Image placed successfully.");
					unset(self::$placeImage[$player->getName()]);
					goto skipImagePlace;
				}
				self::$placeImage[$player->getName()][1]++;
				$player->sendMessage(TextFormat::GREEN . "Placed image segment " . ($current + 1) . " of " . (count($images)));
			}
			skipImagePlace:
			
			//Item Cases
        	if(isset(self::$itemCase[$player->getName()])){
				$event->setCancelled();
				$caseBlock = $block;
				$error = false;
            	if($caseBlock->getId() !== Block::GLASS){
					$error = true;
                	if($caseBlock->getId() === Block::STONE_SLAB){
                    	$caseBlock = $caseBlock->getSide(Vector3::SIDE_UP);
						$error = false;
                	}else{      
						$error = true;
                    }
                }
				if($error){
					$player->sendMessage("ic-error");
				}else{
        			$cid = implode(":", [$caseBlock->getX(), $caseBlock->getY(), $caseBlock->getZ()]);
        			$item = $player->getInventory()->getItemInHand();
        			if($item->isNull()){
            			$player->sendMessage("hold-item");
					}else{
						$this->plugin->itemCases[$player->getLevel()->getFolderName()][$cid] = [
							"item" => implode(":", [$item->getId(), $item->getDamage()]),
							"count" => 1
						];
						$this->plugin->spawnItemCase($player->getLevel(), $cid, $player);
            			$player->sendMessage("ic-placed");
					}
				}
			}
			
			//Spawners
			if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_BLOCK && !$player->isSneaking()){
				$loc = $block->asPosition()->__toString();
				if(isset(self::$spawnerEntities[$loc])){
					if(!(isset($this->fixSpamBug[$player->getName()]) && !(microtime(true) - $this->fixSpamBug[$player->getName()] > 0.01))){
						$boosters = self::$spawnerBoosters[$loc] ?? [];
						$form = new SimpleForm(function(Player $player, ?string $name) use($loc, $block){
							if($name !== null){
								$spawnerName = $this->plugin->getSpawnerName(self::$spawnerEntities[$loc]);
								$price = $this->plugin->getBoosterPrice($name, intval($this->plugin->spawners[$spawnerName]["exp"] * 1000));
								$boosterId = constant(MobSpawner::class . "::" . $name);
								$boost = $this->plugin->parseBoosters($boosterId)[0];
								$tile = $block->getLevel()->getTile($block->asVector3());
								if($tile instanceof MobSpawner){
									if($player->reduceMoney($price)){
										$tile->applyBoosters([$boosterId => 1800]);
										$player->sendMessage("spawners-booster-bought", $boost, $spawnerName);
									}else{
										$player->sendMessage("money-needed-more", $price - $player->getMoney());
									}
								}
							}
						});
						$form->setTitle(TextFormat::colorize("&e&lManage Boosters"));
						if(empty(self::$spawnerBoosters[$loc])){
							$activeBoosters = [];
							$content = LangManager::translate("spawners-booster-none", $player);
						}else{
							$boosters = 0;
							foreach(array_keys(self::$spawnerBoosters[$loc]) as $booster){
								$boosters |= $booster;
							}
							$activeBoosters = $this->plugin->parseBoosters($boosters);
							$content = LangManager::translate("spawners-booster-list", $player, count($activeBoosters), implode(", ", $activeBoosters));
						}
						$form->setContent($content . "\n\n" . LangManager::translate("spawners-booster-help", $player));
						foreach((new \ReflectionClass(MobSpawner::class))->getConstants() as $name => $value){
							if(strpos($name, "BOOSTER_") !== false){
								if(strpos($name, "_COUNT") !== false){
									continue; //TODO
								}
								$booster = $this->plugin->parseBoosters($value)[0]; //only to get the user friendly name
								if(true){ //count($booster) === 1
									$price = $this->plugin->getBoosterPrice($name, intval($this->plugin->spawners[$this->plugin->getSpawnerName(self::$spawnerEntities[$loc])]["exp"] * 1000));
									$form->addButton(LangManager::translate("spawners-booster", $player, $booster, round($price)), -1, "", $name);
								}
							}
						}
						$form->sendToPlayer($player);
						$this->fixSpamBug[$player->getName()] = microtime(true);
					}
				}
			}
			
			if($block->getId() === Block::CHEST xor $block->getId() === Block::TRAPPED_CHEST){
				if($item->equals(ItemUtils::get("broken_key"))){
					$player->sendMessage("brokenkey-1");
					$event->setCancelled();
				}
			}
			
	        //Map Protector
			$frame = $block->getLevel()->getTile($block->asVector3());
			if($frame instanceof ItemFrame && $frame->getItem() instanceof FilledMap && !$player->isOp()){
				$event->setCancelled();
			}
		}
	}
	
	/**
	 * @notHandler
	 * @param BlockPlaceEvent $event
	 */
	public function onMigrationBlockPlace(BlockPlaceEvent $event)  : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if($this->migrationIndex >= count($this->migrationShops)){
		}else{
			$keys = array_values($this->migrationShops);
			$shop = $keys[$this->migrationIndex] ?? null;
			if($shop  === null && $this->migrationIndex !== -1){
				return;
			}
			if($shop["vip"]){
				$this->migrationIndex++;
			}
			$shop = $keys[$this->migrationIndex] ?? null;
			if($shop === null){
				return;
			}
			$this->migrationLines = array_map("strval", [
					"shop", $shop["price"], $shop["item"] . ":" . $shop["meta"], $shop["amount"]
			]);
			$this->migrationIndex++;
		}
	}
	
	/**
	 * Shops.
	 * @param SignChangeEvent $event
	 * @ignoreCancelled false
	 */
	public function onSignChange(SignChangeEvent $event){
		$player = $event->getPlayer();
		$block = $event->getBlock();
		if(!empty($this->migrationLines)){
			$event->setLines($this->migrationLines);
		}else{
		}
		
		if(Main2::$shop->exists($block->getX() . ":" . ($block->getY() - 2) . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName())){
			$shop = Main2::$shop->get($block->getX() . ":" . ($block->getY() - 2) . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName());
			$price = $shop["price"] * 2;
			$item = $shop["item"];
			$itemName = $shop["itemName"];
			$meta = $shop["meta"];
			$amount = $shop["amount"];
			Main2::$shop->set($block->getX(). ":" . $block->getY() . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName(), [
				"x" => $block->getX(),
				"y" => $block->getY(),
				"z" => $block->getZ(),
				"level" => $block->getLevel()->getFolderName(),
				"price" => $price,
				"item" => $item,
				"itemName" => $itemName,
				"meta" => $meta,
				"amount" => $amount,
				"vip" => false
			]);
			$player->sendMessage("shop-created", $item, $meta, $price);
			$event->setLine(0, TextFormat::DARK_BLUE . "[SHOP]");
			$event->setLine(1, strval($price));
			$event->setLine(2, $itemName);
			$event->setLine(3, "Amount: " . TextFormat::BOLD . $amount);
		}
		if($event->getLine(0) === "shop" && $player->isOp()){
			try{
				$item = ItemFactory::fromString($event->getLine(2));
				
			}catch(\InvalidArgumentException $e){
				echo $event->getLine(2) . "\n";
				$player->sendMessage(LangManager::translate("shop-item-not-supported", $player, $event->getLine(2)));
				return;
			}
			$block = $event->getBlock();
			Main2::$shop->set($block->getX() . ":" . $block->getY() . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName(), [
				"x" => $block->getX(),
				"y" => $block->getY(),
				"z" => $block->getZ(),
				"level" => $block->getLevel()->getFolderName(),
				"price" => ($price = (int) $event->getLine(1)),
				"item" => (int) $item->getID(),
				"itemName" => $item->getName(),
				"meta" => (int) $item->getDamage(),
				"amount" => (int) $event->getLine(3),
				"vip" => true
			]);
			
			if(isset(Sell::$market[$item->getId()]) && ($sellPrice = Sell::$market[$item->getId()]) > $price){
				$player->sendMessage(LangManager::translate("shop-item-price", $sellPrice));
				echo "[set]\n";
				return;
			}
			$event->setLine(0, TextFormat::WHITE . "VipShop");
			$event->setLine(1, TextFormat::GOLD . intval($event->getLine(1)) . TextFormat::GREEN . "\$"); 
			$event->setLine(2, TextFormat::RED . $item->getName());
			$event->setLine(3, TextFormat::AQUA . "Amount : " . TextFormat::BOLD . intval($event->getLine(3)));
			$player->sendMessage("shop-created", $item->getID(), $item->getDamage(), $event->getLine(1));
		}
	}
	
	/**
	 * @param BlockPlaceEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 * @notHandler
	 */
	public function onBlockPlaceFix(BlockPlaceEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if($block->getId() === Block::CHEST){
			foreach([2, 3, 4, 5] as $side){
				$loc = $block->getSide($side);
				if(isset(self::$usingChest[$loc->x . ":" . $loc->y . ":" . $loc->z])){
					$player->sendMessage(TextFormat::RED . "You can't place a chest next to a chest being used.");
					$event->setCancelled();
					return;
				}
			}
		}
	}
	
	/**
	 * @param BlockBreakEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 * @notHandler
	 */
	public function onBlockBreakFix(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		if($block->getId() === Block::CHEST){
			foreach([2, 3, 4, 5] as $side){
				$loc = $block->getSide($side);
				if(isset(self::$usingChest[$loc->x . ":" . $loc->y . ":" . $loc->z])){
					$player->sendMessage(TextFormat::RED . "You can't break a chest that is being used.");
					$event->setCancelled();
					return;
				}
			}
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onInteractFix(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		/*if($block->getId() === Block::ANVIL){
			$menu = InvMenu::create(InvMenu::TYPE_ANVIL);
			$menu->setListener(InvMenu::readonly());
			$menu->send($player);
			self::$usingAnvil[$player->getName()][0] = true;
			//$player->sendMessage(TextFormat::RED . "Enchant Table / Anvils are temporalily not available.");
			$event->setCancelled();
		}
		if($block->getId() === Block::ENCHANTING_TABLE){
			$menu = InvMenu::create(InvMenu::TYPE_ENCHANTING_TABLE);
			$menu->setListener(InvMenu::readonly());
			$menu->send($player);
			self::$usingEnchantingTable[$player->getName()][0] = true;
			$event->setCancelled();
		}*/
		
		return;
		//My mistake
		if($block->getId() === Block::CHEST && ($tile = $block->getLevel()->getTile($block->asVector3())) instanceof Chest){
		    $event->setCancelled();
		    $check1 = $tile->x . ":" . $tile->y . ":" . $tile->z;
		    $check2 = $tile->isPaired() ? ($tile->getPair()->x . ":" . $tile->getPair()->y . ":" . $tile->getPair()->z) : $check1;
		    if((isset(self::$usingChest[$check1]) && self::$usingChest[$check1] !== $player->getName()) || (isset(self::$usingChest[$check2]) && self::$usingChest[$check2] !== $player->getName())){
		    	$player->sendMessage(TextFormat::RED . "This chest is being used!");
		    	return;
		    }
		    self::$fixWin10SpamBug[$player->getName()] = time();
			$menu = InvMenu::create($tile->isPaired() ? InvMenu::TYPE_DOUBLE_CHEST : InvMenu::TYPE_CHEST);
			$menu->getInventory()->setContents($tile->getInventory()->getContents());
			$menu->setInventoryCloseListener(function(Player $player, $inventory) use($check1, $check2, $tile){
				unset(self::$usingChest[$check1]);
				unset(self::$usingChest[$check2]);		
			});
			$menu->send($player);
			self::$usingChest[$check1] = $player->getName();
			self::$usingChest[$check2] = $player->getName();
		}
	}
	
    /**
     * Shops.
     * @param PlayerInteractEvent $event
     * @ignoreCancelled false
     */
	public function onShopInteract(PlayerInteractEvent $event){
		if($event->getAction() !== PlayerInteractEvent::RIGHT_CLICK_BLOCK){
			return;
		}
		$block = $event->getBlock();
		$loc = $block->getX() . ":" . $block->getY() . ":" . $block->getZ() . ":" . $block->getLevel()->getFolderName();
		
		/*$tile = $block->getLevel()->getTile($block->asVector3());
		
		if($tile !== null){
			echo "[SIGN]" . PHP_EOL;
			if(TextFormat::clean($tile->getLine(0)) === "VipShop"){
				$vip = true;
			}else{
				$vip = false;
			}
			if(true){
				
				$price = intval(TextFormat::clean($tile->getLine(1)));
				$item = TextFormat::clean($tile->getLine(2));
				var_dump("[NAME: " . $item);
				$items = new \ReflectionClass(\pocketmine\item\ItemIds::class);
				foreach($items->getConstants() as $key => $value){
					for($variant = 0; $variant < 20; $variant++){
						$it = \pocketmine\item\ItemFactory::get($value, $variant, 1);
						if($it->getName() === "Unknown"){
							continue;
						}
						if($it->getName() === $item){
							
							$amount = intval(str_replace(["Amount : ", "Amount: "], "", TextFormat::clean($tile->getLine(3))));
							echo "[FOUND " . $item . ", AMOUNT " . $amount . ", PRICE " . $price . PHP_EOL;
							Main2::$shop->set($loc, [
								"x" => $block->getX(),
								"y" => $block->getY(),
								"z" => $block->getZ(),
								"level" => $block->getLevel()->getFolderName(),
								"price" => $price,
								"item" => $value,
								"itemName" => $it->getName(),
								"meta" => $it->getDamage(),
								"amount" => $amount,
								"vip" => $vip
							]);
							if($block->getLevel()->getBlockIdAt($block->getFloorX(), $block->getFloorY() - 1, $block->getFloorZ()) === Block::GLASS){
								echo "[IC MADE]" . PHP_EOL;
								$cid = implode(":", [$block->getX(), $block->getY() - 1, $block->getZ()]);
								
								$this->plugin->itemCases[$block->getLevel()->getFolderName()][$cid] = [
									"item" => implode(":", [$it->getId(), $it->getDamage()]),
									"count" => 1
								];
								$this->plugin->spawnItemCase($block->getLevel(), $cid, $event->getPlayer());
							}
							break;
						}
					}
				}
			}
		}*/
		if(Main2::$shop->exists($loc)){
			$event->setCancelled();
			$shop = Main2::$shop->get($loc);
			$player = $event->getPlayer();
			if($shop["vip"]){
				if(!$this->plugin->isVip($player)){
					$player->sendMessage("only-premium");
					return;
				}
			}
			$form = new CustomForm(function(Player $player, ?array $data) use($shop){
				if(isset($data[1]) && ($amount = $data[1]) > 0 && $player->isOnline()){
					$price = $shop["price"] * $amount;
					$amount = $shop["amount"] * $amount;
					
					$form = new ModalForm(function(Player $player, ?bool $confirm) use($shop, $amount, $price){
						if(!$confirm){
							return;
						}
						$item = (ItemFactory::get($shop["item"], $shop["meta"]))->setCount((int) $amount);
						if(!$player->getInventory()->canAddItem($item)){
							$player->sendMessage("inventory-nospace");
							return;
						}
						$money = Main::getInstance()->myMoney($player);
						if(!Main::getInstance()->reduceMoney($player, $price)){
							$player->sendMessage("money-needed", $price);
							return;
						}
						$player->getInventory()->addItem($item);
						$player->sendMessage("shop-bought-item", $amount, $shop["itemName"], $price);
					});
					$form->setTitle($shop["vip"] ? TextFormat::WHITE . "VipShop" : TextFormat::DARK_BLUE . "Shop");
					$form->setContent(LangManager::translate("shop-confirm-buy", $player, $amount, $shop["itemName"], $price));
					$form->setButton1(TextFormat::GREEN . "Confirm");
					$form->setButton2(TextFormat::RED . "Cancel");
					$player->sendForm($form);
				}
			});
			$form->setTitle($shop["vip"] ? TextFormat::WHITE . "VipShop" : TextFormat::DARK_BLUE . "Shop");
			$form->addLabel(TextFormat::RED . "x" . $shop["amount"] . " " . $shop["itemName"]);
			$form->addSlider(TextFormat::AQUA . "Amount", 1, 64);
			$player->sendForm($form);
		}
	}

}