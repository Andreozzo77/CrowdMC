<?php

declare(strict_types=1);

namespace kenygamer\Core\duel;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockInteractEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\level\Position;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\protocol\LoginPacket;
use pocketmine\Player;
use pocketmine\block\Air;
use pocketmine\block\Block;
use pocketmine\inventory\CraftingGrid;
use pocketmine\utils\TextFormat;

use BlockHorizons\BlockPets\pets\BasePet;
use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use LegacyCore\Events\PlayerEvents;

/**
 * The event listener for the duels system
 *
 * @class DuelListener
 */
final class DuelListener implements Listener{
	/** @var Main */
	private $plugin;
	/** @var array */
	public static $brokenBlocks;
	/** @var array */
	public static $lastStep = [];
	/** @var array string => int */
	private $deviceOS = [];
	
	public const RESULT_LOST = "&c&lGAME OVER";
	public const RESULT_WON = "&a&lYOU WON";
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	
	private function registerWin(Player $winner, int $duel) : void{
		$scores = (array) $this->plugin->getEntry($winner, Main::ENTRY_DUEL_SCORE);
		$scores[$duel] = ($scores[$duel] ?? 0) + 1;
		$this->plugin->registerEntry($winner, Main::ENTRY_DUEL_SCORE, $scores);
	}
	
	/**
	 * Called when the spleef/tnrun arena resets.
	 *
	 * @param string $arenaHash
	 * @param int $duelType
	 */
	public static function fillBrokenBlocks(string $arenaHash, int $duelType) : void{
		switch($duelType){
			case Main::DUEL_TYPE_SPLEEF:
			    $block = Block::SNOW_BLOCK;
			    break;
			case Main::DUEL_TYPE_TNTRUN:
			    $block = Block::TNT;
			    break;
			default:
			    $block = Block::AIR;
		}
		foreach(self::$brokenBlocks[$arenaHash] ?? [] as $pos){
			$pos->getLevel()->setBlockIdAt($pos->x, $pos->y, $pos->z, $block);
		}
	}
	
	/**
	 * Falls the TNT below.
	 *
	 * @param Position $pos
	 * @param string $arenaHash
	 * @param int $depth if the player has i.e jump effects this may need to be increased.
	 */
	public static function fallTNT(Position $pos, string $arenaHash, int $depth = 3) : void{
		for($i = 0; $i < $depth; $i++){
			$v = clone $pos;
			$v->setComponents($v->getFloorX(), $v->getFloorY() - $i, $v->getFloorZ());
			$block = $pos->getLevel()->getBlock($v);
			if($block->getId() === Block::TNT){
				$block->getLevel()->setBlock($block, new Air());
				self::$brokenBlocks[$arenaHash][] = $block;
			}
		}
	}
	
	/**
	 * Fix use of crafting grinds to hold items in a duel to when it finishes.
	 *
	 * @param InventoryTransactionEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onInventoryTransaction(InventoryTransactionEvent $event) : void{
		$transaction = $event->getTransaction();
		$player = $transaction->getSource();
		if($this->plugin->getPlayerDuel($player) instanceof DuelArena){
			$inventories = $transaction->getInventories();
			foreach($inventories as $inventory){
				if($inventory instanceof CraftingGrid){
					$event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * Disable item dropping before ClearInvListener::onPlayerDropItem handles it
	 * so players can restore the items cleared.
	 *
	 * @param PlayerDropItemEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerDropItem(PlayerDropItemEvent $event) : void{
		$player = $event->getPlayer();
		if($this->plugin->getPlayerDuel($player) instanceof DuelArena){
			$event->setCancelled();
		}
	}
	
	
	/**
	 * Handle Spleef and TNT Run Duels.
	 * Prevent fall damage.
	 *
	 * @param EntityDamageEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled false
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		if(!($entity instanceof Player) || ($arena = $this->plugin->getPlayerDuel($entity)) === null){
			return;
		}
		if(!in_array($arena->getDuelType(), [Main::DUEL_TYPE_SPLEEF, Main::DUEL_TYPE_TNTRUN]) && $event->getFinalDamage() >= $entity->getHealth() && $event->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK){
			$event->setCancelled(); //Fix dying in duels
			return;
		}
		if($event->getCause() === EntityDamageEvent::CAUSE_FALL){ //Also suffocation? (but with teleport to player's spawn???)
			$event->setCancelled();
			return;
		}
		if(!in_array($event->getCause(), [EntityDamageEvent::CAUSE_ENTITY_ATTACK, EntityDamageEvent::CAUSE_CUSTOM]) && ($arena->getDuelType() === Main::DUEL_TYPE_SPLEEF xor $arena->getDuelType() === Main::DUEL_TYPE_TNTRUN)){
			if($event->getFinalDamage() >= $entity->getHealth()){
				$event->setCancelled();
			}
			if(!($arena->gameStatus === DuelArena::GAME_STATUS_ACTIVE)){
				return;
			}
			
		    self::fillBrokenBlocks(spl_object_hash($arena), $arena->getDuelType());
			$arena->gameStatus = DuelArena::GAME_STATUS_INACTIVE;
			$players = $arena->getPlaying();
			if($players[0]->getName() === $entity->getName()){
				$winner = $players[1];
			}else{
				$winner = $players[0];
			}
			$entity->addTitle(LangManager::translate("duel-ended", $entity), TextFormat::colorize(self::RESULT_LOST), 15, 15, 15);
			$this->sendResult($entity, self::RESULT_LOST, $winner, $arena->getDuelType());
			$winner->addTitle(LangManager::translate("duel-ended", $winner), TextFormat::colorize(self::RESULT_WON), 15, 15, 15);
			$this->sendResult($winner, self::RESULT_WON, $entity, $arena->getDuelType());
			LangManager::broadcast("duel-result", $winner->getName(), $this->plugin->getDuelName($arena->getDuelType()), $entity->getName());
			
			$this->registerWin($winner, $arena->getDuelType());
		}
	}
	
	/**
	 * Handle Normal, Vanilla and Custom Duel.
	 * Disadvantage Xbox and Windows 10 players.
	 *
	 * @param EntityDamageByEntityEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onEntityDamageByEntity(EntityDamageByEntityEvent $event) : void{
		$entity = $event->getEntity();
		$damager = $event->getDamager();
		if($damager instanceof BasePet && $damager->getPetOwner() instanceof Player){
			$damager = $damager->getPetOwner();
		}
		if($entity instanceof Player && $damager instanceof Player){
			/** @var DuelArena|null */
			$arena = $this->plugin->getPlayerDuel($entity);
			if($arena instanceof DuelArena){
				if($arena->getDuelType() === Main::DUEL_TYPE_SPLEEF xor $arena->getDuelType() === Main::DUEL_TYPE_TNTRUN){
					$event->setCancelled();
					return;
				}
				if($arena->isPlaying($damager) === false){
					$event->setCancelled(); //how did the damager get into there??
					return;
				}
				if($arena->getDuelType() === Main::DUEL_TYPE_VANILLA){
					$event->setBaseDamage($event->getBaseDamage() / 2);
				}
				
				$os = PlayerEvents::getPlayerData($damager->getName())["DeviceOS"];
				$osString = PlayerEvents::OS_LIST[$os];
				switch($osString){
					case "macOS":
					case "Windows 10":
					   $event->setBaseDamage($event->getBaseDamage() - ($event->getBaseDamage() * 16 / 100));
					   break;
					case "Xbox":
					    $event->setBaseDamage($event->getBaseDamage() - ($event->getBaseDamage() * 7 / 100));
					    break;
			    }
			    
				if($event->getFinalDamage() >= $entity->getHealth()){
					$event->setCancelled();
					if(!($arena->gameStatus === DuelArena::GAME_STATUS_ACTIVE)){
						return;
					}
					$arena->gameStatus = DuelArena::GAME_STATUS_INACTIVE; //takes effect in the next arena tick
					
					LangManager::broadcast("duel-broadcast-" . \kenygamer\Core\Main::mt_rand(1, 15), $damager->getName(), $entity->getName(), $this->plugin->getDuelName($arena->getDuelType()));
					
					$entity->addTitle(LangManager::translate("duel-ended", $entity), TextFormat::colorize(self::RESULT_LOST), 15, 15, 15);
					if($arena->getDuelType() === Main::DUEL_TYPE_NORMAL){
						$this->plugin->registerEntry($entity, Main::ENTRY_DEATHS);
						$this->plugin->resetEntry($entity, Main::ENTRY_KILL_STREAK);
					}
					$this->sendResult($entity, self::RESULT_LOST, $damager, $arena->getDuelType());
					
					$damager->addTitle(LangManager::translate("duel-ended", $damager), TextFormat::colorize(self::RESULT_WON), 15, 15, 15);
					if($arena->getDuelType() === Main::DUEL_TYPE_NORMAL){
						$this->plugin->registerEntry($damager, Main::ENTRY_KILLS);
						$this->plugin->registerEntry($damager, Main::ENTRY_KILL_STREAK);
					}
					$this->sendResult($damager, self::RESULT_WON, $entity, $arena->getDuelType());
					$this->registerWin($damager, $arena->getDuelType());
					
					if(!$arena->isKitpvp() && $arena->getDuelType() !== Main::DUEL_TYPE_FRIENDLY){ //Non KitPvP duel
						$this->swipeInventory($entity, $damager);
					}
				}else{
					$event->setCancelled(false); //This should normally override pvp protection for faction allies/members
				}
			}
		}
	}
	
	/**
	 * Handle TNT Run.
	 * Don't let player move until the duel started.
	 *
	 * @param PlayerMoveEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$player = $event->getPlayer();
		$arena = $this->plugin->getPlayerDuel($player);
		if($arena !== null){
			
			if($arena->gameStatus === DuelArena::GAME_STATUS_COUNTDOWN){
				$event->setCancelled();
			}
			
			if($arena->getDuelType() === Main::DUEL_TYPE_TNTRUN && $arena->gameStatus == DuelArena::GAME_STATUS_ACTIVE){
				if(!isset(self::$lastStep[$player->getName()])){
					ResetLastStep: {
						self::$lastStep[$player->getName()] = [$player->asPosition(), time()];
					}
				}else{
					$to = $event->getTo();
					$lastStep = self::$lastStep[$player->getName()][0];
					if($to->distance($lastStep) >= 1){
						self::fallTNT($lastStep, spl_object_hash($arena));
						goto ResetLastStep;
					}
				}
			}
		}
	}
	 
	
	/**
	 * Disable messages in the duel.
	 *
	 * @param PlayerChatEvent $event
	 * @ignoreCancelled true
	 */
	public function onPlayerChat(PlayerChatEvent $event){
		$recipients = $event->getRecipients();
		foreach($recipients as $i => $recipient){
			if(!($recipient instanceof Player)){
				continue;
			}
			if(($duel = $this->plugin->getPlayerDuel($recipient)) !== null && $duel->gameStatus === DuelArena::GAME_STATUS_ACTIVE){
				unset($recipients[$i]);
			}
		}
		$event->setRecipients($recipients);
	}
	
	/**
	 * No commands or chat.
	 *
	 * @param PlayerCommandPreprocessEvent $event
	 * @priority NORMAL
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		if($this->plugin->getPlayerDuel($player)){
			if(($slashIndex = strpos($event->getMessage(), "/")) === 0 xor $slashIndex === 1){
				LangManager::send("duel-nocmd-player", $player);
			}else{
				LangManager::send("duel-nochat", $player);
			}
			$event->setCancelled();
		}elseif($this->plugin->getPlayerSpectating($player)){
			if(in_array(substr($event->getMessage(), 0, 1), ["/", "./"])){
				if(!in_array(mb_strtolower(explode(" ", $event->getMessage())[0]), ["/duel", "./duel"])){
					LangManager::send("duel-nocmd-spectator", $player);
					$event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * Area protection.
	 *
	 * @param BlockBreakEvent $event
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		foreach($this->plugin->duelArenas as $arena){
			if($arena->getLevel()->getFolderName() === $block->getLevel()->getFolderName() && $arena->getArea()->isVectorInside($block)){
				if($arena->getDuelType() === Main::DUEL_TYPE_SPLEEF && $block->getId() === Block::SNOW_BLOCK && $arena->gameStatus === DuelArena::GAME_STATUS_ACTIVE && $arena->isPlaying($player)){
					self::$brokenBlocks[spl_object_hash($arena)][] = $block->asPosition();
					$event->setDrops([]);
				}elseif(!$player->isOp()){
					$event->setCancelled();
				}
				return;
			}
		}
	}
	
	/**
	 * Area protection.
	 *
	 * @param BlockPlaceEvent $event
	 * @ignoreCancelled true
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		foreach($this->plugin->duelArenas as $arena){
			if($arena->getLevel()->getFolderName() === $block->getLevel()->getFolderName() && $arena->getArea()->isVectorInside($block) && !$player->isOp()){
				$event->setCancelled();
				return;
			}
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 * @priority HIGH
	 * @ignoreCancelled false
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		$block = $event->getBlock();
		$item = $event->getItem();
		foreach($this->plugin->duelArenas as $arena){
			if($arena->getLevel()->getFolderName() === $block->getLevel()->getFolderName() && $arena->getArea()->isVectorInside($block)){
				
				$cancel = true;
				
				if($arena->getDuelType() === Main::DUEL_TYPE_SPLEEF && $block->getId() === Block::SNOW_BLOCK && $arena->gameStatus === DuelArena::GAME_STATUS_ACTIVE && $arena->isPlaying($player) !== false){
					self::$brokenBlocks[spl_object_hash($arena)][] = $block->asPosition();
					$block->getLevel()->setBlock($block, new Air());
				}
				if($event->getAction() === PlayerInteractEvent::RIGHT_CLICK_AIR){
					$cancel = false;
				}
				$event->setCancelled(!$player->isOp() ? $cancel : $event->isCancelled());
				return;
			}
		}
	}
	
	/**
	 * When a player quits, award the player that remained online.
	 *
	 * @param PlayerQuitEvent $event
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if($this->plugin->inQueue($player)){
			$this->plugin->quitQueue($player);
		}elseif(($arena = $this->plugin->getPlayerDuel($player)) !== null){
			
			if($arena->gameStatus !== DuelArena::GAME_STATUS_INACTIVE){
				if($arena->getDuelType() === Main::DUEL_TYPE_SPLEEF xor $arena->getDuelType() === Main::DUEL_TYPE_TNTRUN){
					self::fillBrokenBlocks(spl_object_hash($arena), $arena->getDuelType());
				}
				
				$arena->gameStatus = DuelArena::GAME_STATUS_INACTIVE; //takes effect in the next arena tick
				
				$players = $arena->getPlaying();
				if($players[0]->getName() === $player->getName()){
					$winner = $players[1];
				}else{
					$winner = $players[0];
				}
				
				//When player is not destructed yet, so DuelArena::checkMembers() won't able to help in this scenario
				$arena->removePlayer($player);
				
				//Must teleport manually
				$spawn = $this->plugin->getServer()->getDefaultLevel()->getSpawnLocation();
				$winner->teleport($spawn);
				$player->teleport($spawn);
				
				$arena->removePlayer($winner); //Must remove manually
				
				foreach($arena->getSpectating() as $spectator){
					$arena->removeSpectator($spectator);
				}
				LangManager::send("duel-offline", $winner, $player->getName());
				
				$duelName = $this->plugin->getDuelName($arena->getDuelType());
				LangManager::broadcast("duel-result", $winner->getName(), $duelName, $player->getName());
				if($arena->getDuelType() === Main::DUEL_TYPE_NORMAL){
					$this->plugin->registerEntry($player, Main::ENTRY_DEATHS);
					$this->plugin->resetEntry($player, Main::ENTRY_KILL_STREAK);
				}
					
				$winner->addTitle(TextFormat::colorize("&9&lDuel ended"), TextFormat::colorize(self::RESULT_WON), 15, 15, 15);
				if($arena->getDuelType() === Main::DUEL_TYPE_NORMAL){
					$this->plugin->registerEntry($winner, Main::ENTRY_KILLS);
					$this->plugin->registerEntry($winner, Main::ENTRY_KILL_STREAK);
				}
			    $this->sendResult($winner, self::RESULT_WON, $player, $arena->getDuelType());
				$this->registerWin($winner, $arena->getDuelType());
					
				if(!$arena->isKitpvp() && $arena->getDuelType() !== Main::DUEL_TYPE_FRIENDLY){ //Non KitPvP duel
				    $this->swipeInventory($player, $winner);
				}
			}
		}elseif(($arena = $this->plugin->getPlayerSpectating($player)) !== null){
			$arena->removeSpectator($player);
		}
	}

    /**
     * Swipe inventory, NOT switch.
     * Use this in non-kit pvp duels (PlayerDeathEvent->keepInventory = false)
     *
     * @param Player $from
     * @param Player $to
     */
	private function swipeInventory(Player $from, Player $to) : void{
		$inv = $from->getInventory()->getContents();
		$armorInv = $from->getArmorInventory()->getContents();
						
		$from->getInventory()->setContents([]);
		$from->getArmorInventory()->setContents([]);
		
		foreach($armorInv as $i => $item){
			if($to->getInventory()->canAddItem($item)){
				$to->getInventory()->addItem($item);
			}else{
				if($from->getInventory()->canAddItem($item)){
					$from->getInventory()->addItem($item);
				}else{
					//just went poof...
				}
			}
		}
		foreach($inv as $i => $item){
			if($to->getInventory()->canAddItem($item)){
				$to->getInventory()->addItem($item);
			}else{
				if($from->getInventory()->canAddItem($item)){
					$from->getInventory()->addItem($item);
				}else{
					//just went poof...
				}
			}
		}
	}
	
	/**
	 * @param Player $player
	 * @param string $result
	 * @param Player $opponent
	 * @param int $duelType
	 */
	public function sendResult(Player $player, string $result, Player $opponent, int $duelType) : void{
		if($result === self::RESULT_WON){
			$this->plugin->questManager->getQuest("duel_king")->progress($player, 1, $duelType);
		}
		$this->plugin->getScheduler()->scheduleDelayedTask(new DuelNotifyResultTask($player, $result, ($opponent->getInventory()->getContents() + $opponent->getArmorInventory()->getContents()), $opponent->getName(), $duelType), 60);
	}
	
}