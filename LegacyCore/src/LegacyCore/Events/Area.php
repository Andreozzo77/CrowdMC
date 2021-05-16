<?php

namespace LegacyCore\Events;

use pocketmine\block\Block;
use pocketmine\block\ItemFrame;
use pocketmine\entity\object\Painting;
use pocketmine\event\Listener;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockBurnEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityShootBowEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Entity;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\math\AxisAlignedBB;
use pocketmine\utils\TextFormat;
use pocketmine\item\Item;

use LegacyCore\Core;
use LegacyCore\Commands\Area\Area as AreaCMD;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class Area implements Listener{
	public $cmd;
	public static $instance = null;
	
	public function __construct(AreaCMD $cmd) {
		$this->cmd = $cmd;
		self::$instance = $this;
	}
	
	public static function getInstance() : self{
		return self::$instance;
	}
	
	/**
	 * @param EntityShootBowEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onEntityShootBow(EntityShootBowEvent $event) : void{
		$entity = $event->getEntity();
		if(!($entity instanceof Player)){
			$event->setCancelled();
			return;
		}
		foreach($this->cmd->areas as $area){
			$bb = (Main::createBB($area->getFirstPosition(), $area->getSecondPosition()))->expand(121, 121, 121);
			if($bb->isVectorInside($entity) && !$this->cmd->canEdit($entity, Position::fromObject($area->getFirstPosition(), $entity->getLevel()))){
				LangManager::send("core-area", $entity);
				$event->setCancelled();
				break;
			}
		}
	}
	
	/**
	 * @param BlockBurnEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onBlockBurn(BlockBurnEvent $event) : void{
		$block = $event->getBlock();
		foreach($this->cmd->areas as $area){
			if($area->contains($block, $block->getLevel()->getFolderName())){
				$event->setCancelled();
				break;
			}
		}
		
	}
	/**
     * @param PlayerInteractEvent $event
     * @priority NORMAL
     * @ignoreCancelled true
	 */
	public function onPlayerInteract(PlayerInteractEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$item = $event->getItem();
		$action = $event->getAction();
		if($action !== PlayerInteractEvent::RIGHT_CLICK_AIR && (!$this->cmd->canTouch($player, $block) || (!$this->cmd->canEdit($player, $block) && ($block instanceof ItemFrame || $item->getId() === Item::FLINT_AND_STEEL)))){
			$event->setCancelled();
		}
	}
	
	/**
     * @param BlockPlaceEvent $event
     * @priority NORMAL
	 * @ignoreCancelled true
     */
	public function onBlockPlace(BlockPlaceEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$playerName = mb_strtolower($player->getName());
		if (isset($this->cmd->selectingFirst[$playerName])) {
			unset($this->cmd->selectingFirst[$playerName]);
			$this->cmd->firstPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 1 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		} elseif(isset($this->cmd->selectingSecond[$playerName])) {
			unset($this->cmd->selectingSecond[$playerName]);
			$this->cmd->secondPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 2 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		} else {
			if (!$this->cmd->canEdit($player, $block)) {
				LangManager::send("core-area", $player);
				$event->setCancelled();
			}
		}
	}

	/**
	 * @param BlockBreakEvent $event
	 * @priority NORMAL
	 * @ignoreCancelled true
	 */
	public function onBlockBreak(BlockBreakEvent $event) : void{
		$block = $event->getBlock();
		$player = $event->getPlayer();
		$playerName = mb_strtolower($player->getName());
		if (isset($this->cmd->selectingFirst[$playerName])) {
			unset($this->cmd->selectingFirst[$playerName]);
			$this->cmd->firstPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 1 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		} elseif(isset($this->cmd->selectingSecond[$playerName])) {
			unset($this->cmd->selectingSecond[$playerName]);
			$this->cmd->secondPosition[$playerName] = $block->asVector3();
			$player->sendMessage(TextFormat::GREEN . "Position 2 set to: (" . $block->getX() . ", " . $block->getY() . ", " . $block->getZ() . ")");
			$event->setCancelled();
		} else {
			if(!$this->cmd->canEdit($player, $block)){
				//Personal Mine Blocks
				if(!(in_array($block->getId(), [Block::EMERALD_BLOCK, Block::PRISMARINE, Block::SEA_LANTERN]) && $player->getLevel()->getFolderName() === "prison")){
	    			$event->setCancelled();
	    		}
				if(!in_array($player->getLevel()->getFolderName(), ["prison", "duels"])){
				    LangManager::send("core-area", $player);
				}
			}
		}
	}
	
	/**
     * @param EntityDamageEvent $event
     * @priority NORMAL
	 * @ignoreCancelled true
     */
	public function onEntityDamage(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
			if(!$this->cmd->canGetHurt($entity)){
				$event->setCancelled();
			}
		}elseif($entity instanceof Painting && $event instanceof EntityDamageByEntityEvent && ($damager = $event->getDamager()) instanceof Player){
			if(!$this->cmd->canEdit($damager, $entity->asPosition())){
				$event->setCancelled();
			}
		}
	}
	
}