<?php

declare(strict_types=1);

namespace kenygamer\Core\vehicle;

use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\FloatTag;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinImage;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;

use kenygamer\Core\Main;

final class VehicleFactory{
	/** @var Main */
	private $plugin;
	/** @var array<string, SkinData> */
	private $designs = [];
	/** @var array<string, mixed[]>*/
	private $vehicles = [];

	public function __construct(){
		Entity::registerEntity(Vehicle::class, true, ["Vehicle"]);
	}
	
	/**
	 * @param Vector3 $pos
	 * @param array $data
	 * @return CompoundTag
	 */
	public static function createNBT(Vector3 $pos, array $data) : CompoundTag{
		$seatPositions = $data["seatPositions"];
		$seats = [];
		foreach($seatPositions["passengers"] as $passengerSeat){
			$seat = new ListTag("", [], NBT::TAG_Float);
			$seat->push(new FloatTag("x", $passengerSeat[0]));
			$seat->push(new FloatTag("y", $passengerSeat[1]));
			$seat->push(new FloatTag("z", $passengerSeat[2]));
			$seats[] = $seat;
		}
		
		$baseNbt = Entity::createBaseNBT($pos);
		$nbt = new CompoundTag("vehicleData", []);
		foreach($baseNbt->getValue() as $tag){
			$nbt->setTag($tag);
		}
		$nbt->setInt("type", $data["type"]);
		$nbt->setString("name", $data["name"]);
		$nbt->setFloat("gravity", $data["gravity"]);
		$nbt->setFloat("scale", $data["scale"]);
		$nbt->setFloat("baseOffset", $data["baseOffset"]);
		$nbt->setFloat("forwardSpeed", $data["forwardSpeed"]);
		$nbt->setFloat("backwardSpeed", $data["backwardSpeed"]);
		$nbt->setFloat("leftSpeed", $data["leftSpeed"]);
		$nbt->setFloat("rightSpeed", $data["rightSpeed"]);
		
		$bb = new ListTag("bbox", []);
		$bb->push(new FloatTag("x", $data["bbox"][0]));
		$bb->push(new FloatTag("y", $data["bbox"][1]));
		$bb->push(new FloatTag("z", $data["bbox"][2]));
		$bb->push(new FloatTag("x2", $data["bbox"][3]));
		$bb->push(new FloatTag("y2", $data["bbox"][4]));
		$bb->push(new FloatTag("z2", $data["bbox"][5]));
		$nbt->setTag($bb);
	
		list($x, $y, $z) = $seatPositions["driver"];
		$driverSeat = new ListTag("driverSeat", []);
		$driverSeat->push(new FloatTag("x", $x));
		$driverSeat->push(new FloatTag("y", $y));
		$driverSeat->push(new FloatTag("z", $z));
		$nbt->setTag($driverSeat);
		
		$nbt->setTag(new ListTag("passengerSeats", $seats));
		return $nbt;
	}

	/**
	 * Spawns a vehicle, with specified data.
	 *
	 * @param string $name
	 * @param Level $level
	 * @param Vector3 $pos
	 * @param string|null $owner The owner UUID
	 * @return Vehicle|null
	 */
	public function spawnVehicle(string $name, Level $level, Vector3 $pos, ?string $owner = null) : ?Vehicle{
		foreach($this->vehicles as $data){
			
			if($data["name"] === $name){
				$nbt = self::createNBT($pos, $data);
				$nbt->setString(Vehicle::OWNER_TAG, $owner);
				$entity = Entity::createEntity("Vehicle", $level, $nbt);
				$entity->spawnToAll();
				
				return $entity;
				
			}
		}
		
		return null;
	}

	/**
	 * Register all vehicles from *_vehicle.json into memory.
	 * @param bool $force
	 */
	public function registerVehicles($force = false) : void{
		$plugin = Main::getInstance();
		foreach($plugin->vehicleData->getAll() as $data){
			$name = $data["name"];
			/** @var array $model */
			$model = null;
			foreach($plugin->models as $name_ => $value){
				if($name_ === $name . "_vehicle"){
					$model = $value;
				}
			}
			/** @var string $design */
			$design = null;
			foreach($plugin->designs as $name_ => $value){
				if($name_ === $name . "_vehicle"){
					$design = $value;
				}
			}
			if($model === null){
				$plugin->getLogger()->error("Vehicle " . $name . ": geometry model not found.");
				break;
			}
			if($design === null){
				$plugin->getLogger()->error("Vehicle " . $name . ": skin design not found.");
				break;
			}
			$this->vehicles[$name] = $data;
		}
	}
	
	/**
	 * @param string $name
	 * @return array
	 */
	public function getVehicleData(string $name) : ?array{
		return $this->vehicles[$name] ?? null;
	}
	
}