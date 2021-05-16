<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityEffectAddEvent;
use pocketmine\event\entity\EntityLevelChangeEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityExplodeEvent;
use pocketmine\event\entity\ExplosionPrimeEvent;
use pocketmine\event\player\PlayerBedEnterEvent;
use pocketmine\event\player\PlayerChatEvent;
use pocketmine\event\player\PlayerCommandPreprocessEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerLoginEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\Listener;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Explosion;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class WorldProtect implements Listener{
	
	/** @var array */
	public $enderpearl;
	/** @var array */
	public $plugin;

    public function __construct(Core $plugin){
        $this->plugin = $plugin;
	}
	
	/**
     * @param EntityExplodeEvent $event
     */
	public function onExplode(EntityExplodeEvent $event) : void{
		$entity = $event->getEntity();
		if($entity->getLevel()->getFolderName() == "prison"){
			$event->setCancelled();
		}
	}
	
	/**
     * @param ExplosionPrimeEvent $event
     */
	public function onTNT(ExplosionPrimeEvent $event) : void{
		$entity = $event->getEntity();
		if($entity->getLevel()->getFolderName() == "prison"){
	    	$event->setBlockBreaking(false);
		}
	}
	
	/**
     * @param PlayerBedEnterEvent $event
     */
    public function onSleep(PlayerBedEnterEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getLevel()->getName() == "vipworld"){
			if(!$player->hasPermission("core.command.vip")){
                $event->setCancelled();
			}
		}
	}
	
	/**
     * @param PlayerMoveEvent $event
     */
	public function onMove(PlayerMoveEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getLevel()->getFolderName() === "prison"){
	    	if($player->y > 256){
				if(!$player->hasPermission("core.border.bypass")){
					$player->sendPopup("\n\n\n" . LangManager::translate("core-fly-height", $player, 256));
					$event->setCancelled();
				}
			}
		}
		if($player->getLevel()->getFolderName() == "wild" || $player->getLevel()->getFolderName() == "vipworld"){
	    	if($player->y > 300){
				if(!$player->hasPermission("core.border.bypass")){
					$player->sendPopup("\n\n\n" . LangManager::translate("core-fly-height", $player, 300));
			    	$event->setCancelled();
				}
			}
		}
		// World Border
	   	$level = $this->plugin->getServer()->getLevelByName("wild");
		if($level !== null){
	        $survival = $level->getSpawnLocation()->distance($player);
	    	if($player->getLevel()->getFolderName() == "wild"){
		    	if($survival >= 15000){
		    	    if(!$player->hasPermission("core.border.bypass")){
				    	$player->sendPopup("\n\n\n" . LangManager::translate("core-world-border", $player));
				        $event->setCancelled();
					}
				}
			}
		}
		$level = $this->plugin->getServer()->getLevelByName("vipworld");
		if($level !== null){
		    $premium = $level->getSpawnLocation()->distance($player);
		    if($player->getLevel()->getFolderName() == "vipworld"){
			    if($premium >= 15000){
		    	    if(!$player->hasPermission("core.border.bypass")){
					    $player->sendPopup("\n\n\n" . LangManager::translate("core-world-border", $player));
				        $event->setCancelled();
					}
				}
			}
		}
	}
	
	/**
     * @param PlayerQuitEvent $event
     */
    public function onQuit(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if(isset($this->plugin->suvwild[$player->getLowerCaseName()])){
            unset($this->plugin->suvwild[$player->getLowerCaseName()]);
		}
		if(isset($this->plugin->pvpmine[$player->getLowerCaseName()])){
            unset($this->plugin->pvpmine[$player->getLowerCaseName()]);
		}
    	if(isset($this->plugin->warzones[$player->getLowerCaseName()])){
            unset($this->plugin->warzones[$player->getLowerCaseName()]);
		}
    }
	
	/**
     * @param EntityDamageEvent $event
     */
	public function onHurt(EntityDamageEvent $event) : void{
		$entity = $event->getEntity();
		$cause = $event->getCause();
        if($entity instanceof Player){
		    if(isset($this->plugin->warzones[$entity->getLowerCaseName()])){
				if($entity->getLevel()->getName() == "prison"){
		            $event->setCancelled();
				}
			}
			if(isset($this->plugin->pvpmine[$entity->getLowerCaseName()])){
				if($entity->getLevel()->getFolderName() == "prison"){
		            $event->setCancelled();
				}
			}
			if(isset($this->plugin->suvwild[$entity->getLowerCaseName()])){
				if($entity->getLevel()->getFolderName() == "wild" || $entity->getLevel()->getFolderName() == "vipworld") {
                    $event->setCancelled();
				}
			}
		}
		if($event instanceof EntityDamageByEntityEvent){
            $damager = $event->getDamager();
            if($damager instanceof Player){
                if(isset($this->plugin->warzones[$damager->getLowerCaseName()])){
                	$damager->sendPopup(LangManager::translate("core-fair-attack", $damager));
                    $event->setCancelled();
				}
				if(isset($this->plugin->pvpmine[$damager->getLowerCaseName()])){
                    $damager->sendPopup(LangManager::translate("core-fair-attack", $damager));
                    $event->setCancelled();
				}
			}
		}
	}
	
	/**
     * @param EntityEffectAddEvent $event
     */
	public function onEffect(EntityEffectAddEvent $event) : void{
    	$entity = $event->getEntity();
		$effect = $event->getEffect();
		if($entity->getLevel()->getFolderName() == "prison"){
            if($entity instanceof Player){
				if($effect->getId() == Effect::INVISIBILITY || $effect->getId() == Effect::LEVITATION){
                    $event->setCancelled();
				}
			}
		}
	}
	
	/**
     * @param EntityTeleportEvent $event
     */
	public function onEntityTeleport(EntityTeleportEvent $event): void{
        $entity = $event->getEntity();
        if($entity instanceof Player){
			if(isset($this->plugin->suvwild[$entity->getLowerCaseName()])){
                unset($this->plugin->suvwild[$entity->getLowerCaseName()]);
			}
			if(isset($this->plugin->pvpmine[$entity->getLowerCaseName()])){
                unset($this->plugin->pvpmine[$entity->getLowerCaseName()]);
			}
			if(isset($this->plugin->warzones[$entity->getLowerCaseName()])) {
                unset($this->plugin->warzones[$entity->getLowerCaseName()]);
			}
		}
	}
	
	/**
     * @param EntityLevelChangeEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
	public function onLevel(EntityLevelChangeEvent $event): void{
		$entity = $event->getEntity();
		if(!($entity instanceof Player)){
			return;
		}
		$world = $event->getTarget();
		$level = $event->getOrigin();
		if($world->getFolderName() === "prison"){
			if($entity->hasPermission("core.flying.enable")){
				$entity->setAllowFlight(true);
		    	$entity->setFlying(true);
			}else{
				$entity->removeEffect(Effect::INVISIBILITY);
				$entity->removeEffect(Effect::LEVITATION);
				if($entity->getGamemode() % 2 === 0){
					$entity->setAllowFlight(false);
					$entity->setFlying(false);
				}
			}
		}
		if($world->getFolderName() == "vipworld"){
			if(!$entity->hasPermission("core.command.vip")){
				LangManager::send("core-vipworld-noperm", $entity);
				$event->setCancelled(true);
			}
		}
	}
}