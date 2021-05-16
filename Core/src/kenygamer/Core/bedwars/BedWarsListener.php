<?php

declare(strict_types=1);

namespace kenygamer\Core\bedwars;

use pocketmine\block\Block;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryOpenEvent;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\level\Position;
use pocketmine\Player;

use kenygamer\Core\Main2;
use kenygamer\Core\Main;
use kenygamer\Core\bedwars\BedWarsManager;

class BedWarsListener implements Listener{
	
	public function __construct(Main $plugin){
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	
	/**
	 * @param EntityDamageEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			if(Main2::getBedWarsManager()){
				$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
				if($arena !== null){
					if($arena->isInvulnerable($player)){
						if(!$event->isCancelled()){
							$event->setCancelled();
						}
						return;
					}
					if($arena->getGameStatus() !== BedWarsArena::GAME_STATUS_RUNNING){
						$event->setCancelled();
					}else{
		                 $event->setCancelled($event->getFinalDamage() >= $player->getHealth());
		                 
		                 if(!in_array($event->getCause(), [
						   EntityDamageEvent::CAUSE_ENTITY_ATTACK, EntityDamageEvent::CAUSE_FIRE, EntityDamageEvent::CAUSE_FIRE_TICK,
						   EntityDamageEvent::CAUSE_LAVA, EntityDamageEvent::CAUSE_MAGIC, EntityDamageEvent::CAUSE_STARVATION
						])){
							$event->setCancelled();
						}
						
						if($event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player && $event->getFinalDamage() >= $player->getHealth()){
							$players = $arena->getPlayersSave();
							$i = array_search($player->getName(), $players);
							$player->teleport(Position::fromObject($arena->getSpawns()[$i]->asVector3(), $arena->getLevel()));
							//$arena->broadcastMessage("bedwars-kill", $player->getDisplayName(), $damager->getDisplayName());
							//$arena->removePlayer($player);
						}
					}
				}
			}
		}
	}
	
	/**
	 * @param PlayerMoveEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerMove(PlayerMoveEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getY() < 50){ //Handle it first
			if(Main2::getBedWarsManager()){
				$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
				if($arena !== null){
					//$arena->broadcastMessage("bedwars-void", $player->getDisplayName());
					//$arena->removePlayer($player);
					$players = $arena->getPlayersSave();
					$i = array_search($player->getName(), $players);
					$v = $arena->getSpawns()[$i]->asVector3();
					$player->teleport($pos = (Position::fromObject($arena->getSpawns()[$i]->asVector3(), $arena->getLevel())));
					//$event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * @param PlayerInteractEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$player = $event->getPlayer();
		if(Main2::getBedWarsManager()){
			$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
			if($arena !== null){
				switch($arena->getGameStatus()){
					case BedWarsArena::GAME_STATUS_RUNNING:
					    $event->setCancelled(false);
					    break;
					default:
					    $event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * @param BlockBreakEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$player = $event->getPlayer();
		if(Main2::getBedWarsManager()){
			$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
			if($arena !== null){
				switch($arena->getGameStatus()){
					case BedWarsArena::GAME_STATUS_RUNNING:
					    $block = $event->getBlock();
					    $event->setCancelled(false);
					    if($block->getId() === Block::BED_BLOCK){
					    	$closestSpawn = null;
					    	$spawns = $arena->getSpawns();
					    	foreach($spawns as $i => $spawn){
					    		if($closestSpawn === null || $spawn->distance($block) < $spawns[$closestSpawn]->distance($block)){
					    			$closestSpawn = $i;
					    		}
					    	}
					    	$playerByBed = $arena->getPlayersSave()[$closestSpawn];
					    	$bedTeam = $arena->getPlayerTeam($playerByBed);
					    	$playerTeam = $arena->getPlayerTeam($player);
					    	if($bedTeam === $playerTeam){
					    		$player->sendMessage("bedwars-bedteam");
					    		$event->setCancelled();
					    	}else{
					    		$pk = new PlaySoundPacket();
					    		[$pk->x, $pk->y, $pk->z] = [$player->x, $player->y, $player->z];
					    		$pk->volume = 1;
					    		$pk->pitch = 0.0;
					    		$pk->soundName = "mob.enderdragon.end";
					    		$player->dataPacket($pk);
					    		
					    		$arena->destroyBed($playerByBed);
					    		$event->setDrops([]);
					    	}
					    }
					    break;
					default:
					    $event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * @param BlockPlaceEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled false
	 */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$player = $event->getPlayer();
		if(Main2::getBedWarsManager()){
			$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
			if($arena !== null){
				switch($arena->getGameStatus()){
					case BedWarsArena::GAME_STATUS_RUNNING:
					    $event->setCancelled(false);
					    break;
					default:
					    $event->setCancelled();
				}
			}
		}
	}
	
	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(Main2::getBedWarsManager()){
			Main2::getBedWarsManager()->dequeuePlayer($player);
			Main2::getBedWarsManager()->removeSpectator($player);
			$arena = Main2::getBedWarsManager()->removeFromArena($player);
			if($arena !== null){
				$arena->broadcastMessage("bedwars-quit", $player->getDisplayName());
			}
		}
	}
	
	/**
	 * @param PlayerCommandPreprocessEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerCommandPreprocess(PlayerCommandPreprocessEvent $event) : void{
		$player = $event->getPlayer();
		$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
		if($arena !== null){
			$event->setCancelled();
			if(($slashIndex = strpos($event->getMessage(), "/")) === 0 xor $slashIndex === 1){
				$player->sendMessage("bedwars-cmd");
			}else{
				$player->sendMessage("bedwars-chat");
			}
		}
	}
	
	/**
	 * @param EntityTeleportEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onEntityTeleport(EntityTeleportEvent $event) : void{
		$player = $event->getEntity();
		if($player instanceof Player){
			$fromLevel = $event->getFrom()->getLevel()->getFolderName();
			$targetLevel = $event->getTo()->getLevel() !== null ? $event->getTo()->getLevel()->getFolderName() : $fromLevel;
			$arena = Main2::getBedWarsManager()->getArenaByPlayer($player);
			if($arena !== null && $targetLevel !== $arena->getWorldName() && $arena->getGameStatus() === BedWarsArena::GAME_STATUS_RUNNING){
				$event->setCancelled();
			}
		    Main2::getBedWarsManager()->removeSpectator($player);
		}
	}
	
	/**
	 * @param PlayerChatEvent $event
	 * @priority HIGHEST
	 * @ignoreCancelled true
	 */
	public function onPlayerChat(PlayerChatEvent $event) : void{
		$recipients = $event->getRecipients();
		foreach($recipients as $index => $recipient){
			if($recipient instanceof Player && ($manager = Main2::getBedWarsManager()) && $manager->getArenaByPlayer($recipient) !== null){
				unset($recipients[$index]);
			}
		}
		$event->setRecipients($recipients);
	}
	
	/**
	 * @param InventoryOpenEvent $event
	 * @ignoreCancelled true
	 * @priority HIGHEST
	 */
	public function onInventoryOpen(InventoryOpenEvent $event) : void{
		$player = $event->getPlayer();
		if(Main2::getBedWarsManager() && ($arena = Main2::getBedWarsManager()->getArenaByPlayer($player)) !== null){
			if($arena->getGameStatus() === BedWarsArena::GAME_STATUS_COUNTDOWN){
				$event->setCancelled();
			}
		}
	}
}