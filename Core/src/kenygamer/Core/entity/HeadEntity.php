<?php

declare(strict_types=1);

namespace kenygamer\Core\entity;

use pocketmine\entity\Human;
use pocketmine\entity\Skin;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

use kenygamer\Core\Main;

class HeadEntity extends Human{
    public const HEAD_GEOMETRY = '{"geometry.player_head":{"texturewidth":64,"textureheight":64,"bones":[{"name":"head","pivot":[0,24,0],"cubes":[{"origin":[-4,0,-4],"size":[8,8,8],"uv":[0,0]}]}]}}';

    public $width = 0.5;
    public $height = 0.6;

    protected function initEntity() : void{
	    $this->setMaxHealth(1);
        $this->setSkin($this->getSkin());
	    parent::initEntity();
    }
    
    public function hasMovementUpdate() : bool{
        return false;
    }

    public function attack(EntityDamageEvent $source) : void{
    	$source->setCancelled();
		if($source instanceof EntityDamageByEntityEvent && ($player = $source->getDamager()) instanceof Player){
			$item = Main::getInstance()->getPlayerHead($this->skin, $this->skin->getSkinId());
			if($player->getInventory()->canAddItem($item)){
				$player->getInventory()->addItem($item);
				$this->flagForDespawn();
			}
		}
    }

	public function setSkin(Skin $skin) : void{
		parent::setSkin(new Skin($skin->getSkinId(), $skin->getSkinData(), "", "geometry.player_head", self::HEAD_GEOMETRY));
	}
    
}