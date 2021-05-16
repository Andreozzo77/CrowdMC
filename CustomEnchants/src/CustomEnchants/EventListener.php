<?php

namespace CustomEnchants;

use CustomEnchants\CustomEnchants\CustomEnchants;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;
use CustomEnchants\Entities\MagicFireball;
use CustomEnchants\Entities\PiggyFireball;
use CustomEnchants\Entities\PiggyWitherSkull;
use CustomEnchants\Entities\PigProjectile;
use CustomEnchants\Tasks\CobwebTask;
use CustomEnchants\Tasks\GoeyTask;
use CustomEnchants\Tasks\GrapplingTask;
use CustomEnchants\Tasks\GuardianTask;
use CustomEnchants\Tasks\HallucinationTask;
use CustomEnchants\Tasks\ImplantsTask;
use CustomEnchants\Tasks\MoltenTask;
use CustomEnchants\Tasks\PlaceTask;
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
use pocketmine\event\Event;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerItemConsumeEvent;
use pocketmine\event\player\PlayerKickEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\item\Durable;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Armor;
use pocketmine\item\Axe;
use pocketmine\item\Item;
use pocketmine\item\Sword;
use pocketmine\item\Tool;
use pocketmine\level\Explosion;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\utils\Random;
use pocketmine\utils\TextFormat;

/**
 * Class EventListener
 * @package CustomEnchants
 */
class EventListener implements Listener
{
	private $plugin;

    /**
     * EventListener constructor.
     * @param Main $plugin
     */
    public function __construct(Main $plugin)
    {
        $this->plugin = $plugin;
    }
	
	/**
     * @param EntityRegainHealthEvent $event
     */
	public function onRegen(EntityRegainHealthEvent $event) 
	{
		$entity = $event->getEntity();
		$reason = $event->getRegainReason();
		if($entity instanceof Player){
	    	if(isset($this->plugin->bleeding[$entity->getName()])){
                if($reason === EntityRegainHealthEvent::CAUSE_REGEN || $reason === EntityRegainHealthEvent::CAUSE_EATING || $reason === EntityRegainHealthEvent::CAUSE_MAGIC || $reason === EntityRegainHealthEvent::CAUSE_CUSTOM || $reason === EntityRegainHealthEvent::CAUSE_SATURATION){
			     	$event->setCancelled();
				}
			}
		}
	}
	
	/**
     * @param EntityEffectAddEvent $event
     */
    public function onEffect(EntityEffectAddEvent $event)
    {
		$entity = $event->getEntity();
		$effect = $event->getEffect();
		if($entity instanceof Player){
		    if(isset($this->plugin->bleeding[$entity->getName()])){
				if($effect->getId() == Effect::REGENERATION){
			        $event->setCancelled();
				}
			}
        }
	}
	
	/**
     * @param PlayerDeathEvent $event
     *
     * @priority HIGHEST
     * @ignoreCancelled true
     */
    public function onDeath(PlayerDeathEvent $event)
    {
        $player = $event->getEntity();
		if(isset($this->plugin->bleeding[$player->getName()])){
            unset($this->plugin->bleeding[$player->getName()]);
        }
		if(isset($this->plugin->freeze[$player->getName()])){
            unset($this->plugin->freeze[$player->getName()]);
        }
	}
	
	/**
     * @param PlayerMoveEvent $event
     */
    public function onMove(PlayerMoveEvent $event)
    {
        $player = $event->getPlayer();
		if($player instanceof Player){
	    	if(isset($this->plugin->freeze[$player->getName()])){
				$event->setCancelled();
			}
		}
	}
}
	