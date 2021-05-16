<?php

declare(strict_types=1);

namespace kenygamer\Core\vehicle;

use pocketmine\network\mcpe\protocol\MovePlayerPacket;
use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\math\Vector3;
use pocketmine\math\VoxelRayTrace;
use pocketmine\utils\UUID;
use kenygamer\Core\Main;

final class Vehicle extends VehicleBase{
	/** @var Player|null */
	private $driver = null;
	/** @var Player[] */
	private $passengers = [];

	/**
	 * Vehicle constructor.
	 *
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);

		$this->setCanSaveWithChunk(true);
		$this->saveNBT();
	}
	
	/**
	 * @return bool Has update
	 */
	public function entityBaseTick(int $tickDiff = 1) : bool{
		$plugin = Main::getInstance();
		$found = false;
		foreach(VoxelRayTrace::inDirection($this->add(0, 0, 0), $this->getDirectionVector(), 2) as $dist => $pos){
			$block = $this->level->getBlock($pos);
			if($block->getId() !== Block::AIR && $block->isTransparent()){
				$found = true;
			}
		}
		if($found){
			foreach($plugin->inVehicle as $player => $vehicle){
				if($vehicle->getId() === $this->getId()){
					$this->setMotion($this->getMotion()->add(0, 2.75));
					break;
				}
			}
		}
		return parent::entityBaseTick($tickDiff);
	}
	
	

	/**
	 * Handle player input.
	 * @param float $x
	 * @param float $y
	 */
	public function updateMotion(float $x, float $y): void{
		//(1 if only one button, 0.7 if two)
		//+y = forward (+1/+0.7)
		//-y = backward (-1/-0.7)
		//+x = left (+1/+0.7)
		//-x = right (-1/-0.7)
		if($x !== 0){
			if($x > 0){
				$this->yaw -= $x * $this->speed["leftSpeed"];
			}
			if($x < 0){
				$this->yaw -= $x * $this->speed["rightSpeed"];
			}
			$this->motion = $this->getDirectionVector();
		}
		if($y > 0){
			//forward
			$this->motion = $this->getDirectionVector()->multiply($y * $this->speed["forwardSpeed"]);	
			$this->yaw = $this->driver->getYaw();// - turn based on players rotation
		}elseif($y < 0){
			//reverse
			$this->motion = $this->getDirectionVector()->multiply($y * $this->speed["backwardSpeed"]);
		}
		//$this->forceMovementUpdate = true;
		//$this->updateMovement();
		echo "UPDAT THE MOTION \n";
	}

	/**
	 * @param bool $teleport
	 */
    protected function broadcastMovement(bool $teleport = false) : void{
		$pos = $this->getPosition();
        $pk = new MovePlayerPacket();
        $pk->entityRuntimeId = $this->getId();
        $pk->position = $this->getOffsetPosition($pos);
        $pk->pitch = $this->getPitch();
        $pk->headYaw = $this->getYaw();
        $pk->yaw = $this->getYaw();
        $pk->mode = MovePlayerPacket::MODE_NORMAL;
        $this->getLevel()->broadcastPacketToViewers($pos, $pk);
    }

	/**
	 * @return bool
	 */
	public function isVehicleEmpty() : bool{
		return $this->getDriver() === null && count($this->getPassengers()) === 0;
	}

	/**
	 * @return Player|null
	 */
	public function getDriver() : ?Player{
		return $this->driver;
	}

	/**
	 * @param Player $player
	 * @param bool $override
	 * @return bool
	 */
	public function setDriver(Player $player, bool $override = false) : bool{
		if($this->owner !== $player->getRawUniqueId()){
			return false;
		}
		if($this->getDriver() !== null) {
			if($override){
				$this->removeDriver();
			}else{
				return false;
			}
		}
		$player->setGenericFlag(self::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(self::DATA_FLAG_SITTING, true);
		$player->setGenericFlag(self::DATA_FLAG_WASD_CONTROLLED, true);
		$player->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, $this->seats["driver"]);
		$this->setGenericFlag(self::DATA_FLAG_SADDLED, true);
		$this->driver = $player;
		$plugin = Main::getInstance();
		$plugin->inVehicle[$player->getRawUniqueId()] = $this;
		$this->broadcastLink($this->driver);
		return true;
	}

	public function removeDriver() : bool{
		$plugin = Main::getInstance();
		$driver = $this->getDriver();
		if($driver === null){
			return false;
		}
		$driver->setGenericFlag(self::DATA_FLAG_RIDING, false);
		$driver->setGenericFlag(self::DATA_FLAG_SITTING, false);
		$driver->setGenericFlag(self::DATA_FLAG_WASD_CONTROLLED, false);
		$this->setGenericFlag(self::DATA_FLAG_SADDLED, false);
		$this->broadcastLink($driver, EntityLink::TYPE_REMOVE);
		unset($plugin->inVehicle[$driver->getRawUniqueId()]);
		$this->driver = null;
		return true;
	}

	/**
	 * @return Player[]
	 */
	public function getPassengers() : array{
		return $this->passengers;
	}

	/**
	 * @param Player $player
	 * @param int|null $seat
	 * @param bool $force
	 * @return bool
	 */
	public function addPassenger(Player $player, ?int $seat = null, bool $force = false): bool{
		if(count($this->getPassengers()) === count($this->seats["passengers"]) || isset($this->getPassengers()[$seat])){
			if($force && $seat === null){
				return false;
			}
			if(!$force){
				return false;
			}
			$this->removePassengerBySeat($seat);
		}
		if($seat === null){
			$seat = $this->getNextPassengerSeat();
			if($seat === null){
				return false;
			}
		}
		$this->passengers[$seat] = $player;
		$plugin = Main::getInstance();
		$plugin->inVehicle[$player->getRawUniqueId()] = $this;
		$player->setGenericFlag(self::DATA_FLAG_RIDING, true);
		$player->setGenericFlag(self::DATA_FLAG_SITTING, true);
		$player->getDataPropertyManager()->setVector3(self::DATA_RIDER_SEAT_POSITION, $this->seats["passengers"][$seat]);
		$this->broadcastLink($player, EntityLink::TYPE_PASSENGER);
		$player->sendTip("Sneak/Jump to leave the vehicle.");
		return true;
	}

	/**
	 * @param int $seat
	 * @return bool
	 */
	public function removePassengerBySeat(int $seat) : bool{
		if(isset($this->passengers[$seat])){
			$plugin = Main::getInstance();
			$player = $this->passengers[$seat];
			unset($this->passengers[$seat]);
			unset($plugin->inVehicle[$player->getRawUniqueId()]);
			$player->setGenericFlag(self::DATA_FLAG_RIDING, false);
			$player->setGenericFlag(self::DATA_FLAG_SITTING, false);
			$this->broadcastLink($player, EntityLink::TYPE_REMOVE);
			return true;
		}
		return false;
	}

	/**
	 * @param Player|UUID $id
	 * @return bool
	 */
	public function removePassenger($id) : bool{
		if($id instanceof Player){
			$id = $id->getUniqueId();
		}
		foreach(array_keys($this->passengers) as $i){
			if($this->passengers[$i]->getUniqueId() === $id){
				return $this->removePassengerBySeat($i);
			}
		}
		return false;
	}

	/**
	 * @param Player $player
	 * @return bool
	 */
	public function removePlayer(Player $player) : bool{
		if($this->driver !== null){
			if($this->driver->getUniqueId() === $player->getUniqueId()){
				return $this->removeDriver();
			}
		}
		return $this->removePassenger($player);
	}

	/**
	 * @return int|null
	 * @throws \BadMethodCallException
	 */
	public function getNextPassengerSeat() : ?int{
		$passengers = $this->getPassengers();
		$max = count($this->seats["passengers"]);
		$current = count($passengers);
		if($max === $current){
			return null;
		}
		for($i = 0; $i < $max; $i++){
			if(!isset($passengers[$i])){
				return $i;
			}
		}
		throw new \BadMethodCallException("No seat found");
	}

}