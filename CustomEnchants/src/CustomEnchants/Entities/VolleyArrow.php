<?php

namespace CustomEnchants\Entities;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Arrow;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;

/**
 * Class VolleyArrow
 * @package CustomEnchants\Entities
 */
class VolleyArrow extends Arrow
{
    private $volley;
    public $placeholder;
    private $ownerOriginalLocation;

    /**
     * VolleyArrow constructor.
     * @param Level $level
     * @param CompoundTag $nbt
     * @param Entity|null $shootingEntity
     * @param bool $critical
     * @param bool $volley
     * @param bool $placeholder
     */
    public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, bool $critical = false, bool $placeholder = false, bool $volley = false)
    {
        $this->volley = $volley;
        $this->placeholder = $placeholder;
        if($shootingEntity !== null){
        	$this->ownerOriginalLocation = $shootingEntity->getLocation();
        }
        parent::__construct($level, $nbt, $shootingEntity, $critical);
    }

    /**
     * @param int $tickDiff
     * @return bool
     */
    public function entityBaseTick(int $tickDiff = 1): bool
    {
        if ($this->closed) {
            return false;
        }
        $hasUpdate = parent::entityBaseTick($tickDiff);
        if (!$this->isFlaggedForDespawn()) {
            if (!$this->isCollided) {
                if ($this->getOwningEntity() instanceof Player && $this->getOwningEntity()->isOnline() && $this->placeholder) {
                    $this->getOwningEntity()->sendPosition($this->add($this->getDirectionVector()->multiply(-2)), $this->yaw, $this->pitch);
                }
            } else {
                if ($this->isVolley()) {
                    $this->flagForDespawn();
                    $hasUpdate = true;
                }
                if ($this->placeholder) {
                    $this->placeholder = false;
                    if($this->getOwningEntity() instanceof Player && $this->getOwningEntity()->isOnline()){
                        $this->getOwningEntity()->teleport($this->ownerOriginalLocation);
                    }
                }
            }
        }
        return $hasUpdate;
    }

    /**
     * @return bool
     */
    public function isVolley()
    {
        return $this->volley;
    }
}