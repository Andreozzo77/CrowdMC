<?php

namespace kenygamer\Core\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\projectile\Projectile;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\Player;
use pocketmine\utils\Random;
use kenygamer\Core\item\FireworksItem;

class FireworksEntity extends Projectile{
	const NETWORK_ID = self::FIREWORKS_ROCKET;

	public $width = 0.25;
	public $height = 0.25;

	public $gravity = 0.0;
	public $drag = 0.01;

	private $lifeTime = 0;
	public $random;
    /** @var null|FireworksItem */
	public $fireworks;

	public function __construct(Level $level, CompoundTag $nbt, Entity $shootingEntity = null, ?FireworksItem $item = null, ?Random $random = null){
		$this->random = $random;
		$this->fireworks = $item;
		parent::__construct($level, $nbt, $shootingEntity);
	}

    protected function initEntity() : void{
		parent::initEntity();
		$random = $this->random ?? new Random();

		$this->setGenericFlag(self::DATA_FLAG_HAS_COLLISION, true);
		$this->setGenericFlag(self::DATA_FLAG_AFFECTED_BY_GRAVITY, true);

		$flyTime = 3;

		try{
			if($this->namedtag->getCompoundTag("Fireworks") !== null){
				if($this->namedtag->getCompoundTag("Fireworks")->getByte("Flight", 3)){
					$flyTime = $this->namedtag->getCompoundTag("Fireworks")->getByte("Flight", 3);
				}
			}
		}catch(\Exception $exception){
			$this->server->getLogger()->debug($exception);
		}

		$this->lifeTime = 20 * $flyTime + $random->nextBoundedInt(5) + $random->nextBoundedInt(7);
	}

    public function spawnTo(Player $player) : void{
		$this->setMotion($this->getDirectionVector());
		$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_LAUNCH);
		parent::spawnTo($player);
	}

    public function despawnFromAll() : void{
        $this->broadcastEntityEvent(ActorEventPacket::FIREWORK_PARTICLES, 0);

		parent::despawnFromAll();

		$this->level->broadcastLevelSoundEvent($this, LevelSoundEventPacket::SOUND_BLAST);
	}

	public function entityBaseTick(int $tickDiff = 1) : bool{
		if($this->lifeTime-- < 0){
			$this->flagForDespawn();
			return true;
		}else{
			return parent::entityBaseTick($tickDiff);
		}
	}
	
}