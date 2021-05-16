<?php

declare(strict_types=1);

namespace kenygamer\Core\text;

use pocketmine\event\Listener;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\Player;
use pocketmine\level\Position;
use pocketmine\level\Level;

use kenygamer\Core\Main;

/**
 * Handles despawning and spawning of text particles on join, quit and level change (if there are world-specific)
 */
class FloatingTextListener implements Listener{
	/** @var Main */
	private $plugin;
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
		$plugin->getServer()->getPluginManager()->registerEvents($this, $plugin);
	}
	
	public function onPlayerJoin(PlayerJoinEvent $event) : void{
		$player = $event->getPlayer();
		foreach(FloatingText::getTexts() as $text){
			if($text->isDynamic()){
				$text->updateForAndSendTo($player);
			}else{
				$text->spawnTo($player);
			}
		}
	}
	
	public function onPlayerQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		foreach(FloatingText::getTexts() as $text){
			$text->despawnFrom($player, false);
		}
	}
	
	/**
	 * @param EnttyLevelChangeEvent $event
	 * @priority MONITOR
	 * @ignoreCancelled true
	 */
	public function onEntityLevelChange(EntityLevelChangeEvent $event) : void{
		$entity = $event->getEntity();
		if($entity instanceof Player){
			/** @var Level */
			$target = $event->getTarget();
			foreach(FloatingText::getTexts() as $text){
				if($text->getPosition() instanceof Position){
					if($text->getPosition()->getLevel()->getFolderName() !== $target->getFolderName()){
						$text->despawnFrom($entity);
					}else{
						if($text->isDynamic()){
							$text->updateForAndSendTo($entity);
						}else{
							$text->spawnTo($entity);
						}
					}
				}
			}
		}
	}
	
}