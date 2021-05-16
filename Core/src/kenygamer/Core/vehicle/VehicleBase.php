<?php

declare(strict_types=1);

namespace kenygamer\Core\vehicle;

use pocketmine\Player;
use pocketmine\entity\Skin;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\utils\UUID;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\entity\Entity;
use pocketmine\entity\Rideable;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\network\mcpe\protocol\types\LegacySkinAdapter;

use kenygamer\Core\Main;

class VehicleBase extends Entity implements Rideable{
	public const OWNER_TAG = "Owner";
	
	public const NETWORK_ID = self::PLAYER;

	/** @var UUID|null */
	protected $uuid = null;
	/** @var float */
	public $gravity = 1.0;
	/** @var float */
	public $width = 1.0;
	/** @var float */
	public $height = 1.0;
	/** @var float */
	public $baseOffset = 1.0;
	/** @var string|null */
	protected $name = null;
	/** @var int */
	protected $type = 9; //Unknown
	/** @var float */
	protected $scale = 1.0;
	/** @var float[] */
	protected $bbox = [0, 0, 0, 0, 0, 0];
	/** @var string|null */
	public $owner = null;
	
	/**
	 * @var array<string, null|Vector3|array<Vector3>>
	 */
	protected $seats = [];
	/**
	 * @var array<string, float|null>
	 */
	protected $speed = [];
	/** @var Skin */
	protected $skin = null;

	/**
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		
		$plugin = Main::getInstance();
			
		parent::__construct($level, $nbt);
		$this->loadFromNBT($nbt);
		
		
		
		$this->saveIntoNBT(); //Save anything that reverted to default
	}

	/**
	 */
	public function loadFromNBT(CompoundTag $data) : void{
		$plugin = Main::getInstance();

		$this->uuid = UUID::fromString($data->getString("uuid", UUID::fromRandom()->toString()));
		$this->owner = $data->getString(self::OWNER_TAG, null);
		$this->type = $data->getInt("type", 9);
		$this->name = $data->getString("name");
		
		$geometry = $plugin->models[$this->name . "_vehicle"];
		$this->skin = new Skin($this->uuid->toString(), $plugin->designs[$this->name . "_vehicle"], "", $geometry["minecraft:geometry"][0]["description"]["identifier"], json_encode($geometry)); 
	
		$this->skin->validate();
		
		$this->gravity = $data->getFloat("gravity", 1.0);
		$this->scale = $data->getFloat("scale", 1.0);
		$this->baseOffset = $data->getFloat("baseOffset", 1.0);
		$this->speed["forwardSpeed"] = $data->getFloat("forwardSpeed", 1.0);
		$this->speed["backwardSpeed"] = $data->getFloat("backwardSpeed", 1.0);
		$this->speed["leftSpeed"] = $data->getFloat("leftSpeed", 1.0);
		$this->speed["rightSpeed"] = $data->getFloat("rightSpeed", 1.0);

		$this->bbox = $data->getListTag("bbox")->getAllValues();

		$this->width = max(max($this->bbox[0], $this->bbox[3]) - min($this->bbox[0], $this->bbox[3]), max($this->bbox[2], $this->bbox[5]) - min($this->bbox[2], $this->bbox[5]));
		$this->height = max($this->bbox[1], $this->bbox[4]) - min($this->bbox[1], $this->bbox[4]);

		$seat = $data->getListTag("driverSeat")->getAllValues();
		$this->seats["driver"] = new Vector3($seat[0], $seat[1], $seat[2]);
		foreach($data->getListTag("passengerSeats")->getAllValues() as $tag){
			$seat = $tag->getAllValues();
			$this->seats["passengers"][] = new Vector3($seat[0], $seat[1], $seat[2]);
		}
		$this->setScale($this->scale);
	}

	public function saveIntoNBT() : void{
		$seatPositions = [
			"passengers" => []
		];
		
		$driverSeat = $this->seats["driver"];
		$seatPositions["driver"] = [$driverSeat->getX(), $driverSeat->getY(), $driverSeat->getZ()];
		
		var_dump($this->seats);
		
		foreach($this->seats["passengers"] as $name => $seat){
			$seatPositions["passengers"][] = [$seat->getX(), $seat->getY(), $seat->getZ()];
		}
		$this->namedtag->setTag(VehicleFactory::createNBT($this->asVector3(), [
			"seatPositions" => $seatPositions,
			"type" => $this->type,
			"owner" => $this->owner,
			"uuid" => $this->uuid->toString(),
			"name" => $this->name,
			"gravity" => $this->gravity,
			"scale" => $this->scale,
			"baseOffset" => $this->baseOffset,
			"forwardSpeed" => $this->speed["forwardSpeed"],
			"backwardSpeed" => $this->speed["backwardSpeed"],
			"leftSpeed" => $this->speed["leftSpeed"],
			"rightSpeed" => $this->speed["rightSpeed"],
			"bbox" => $this->bbox
		]), true);
		$this->saveNBT();
	}

	/**
	 * @param Pluarr $player
	 */
	protected function sendSpawnPacket(Player $player) : void{
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$skinAdapter = new LegacySkinAdapter();
		$skinData = $skinAdapter->toSkinData($this->skin);
		$pk->entries[] = PlayerListEntry::createAdditionEntry($this->uuid, $this->id, $this->name . "-" . $this->id, $skinData);
		$player->sendDataPacket($pk);

		//Below adds the actual entity and puts the pieces together.
		$pk = new AddPlayerPacket();
		$pk->uuid = $this->uuid;
		$pk->item = ItemFactory::get(Item::AIR);
		$pk->motion = $this->getMotion();
		$pk->position = $this->asVector3();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $this->propertyManager->getAll();
		$pk->username = $this->name. "-" . $this->id;
		$player->sendDataPacket($pk);

		//Dont want to keep a fake person there..._
		$pk = new PlayerListPacket();
		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$player->sendDataPacket($pk);
	}

	//Without this the player will not do the things it should be (driving, sitting etc)
	protected function broadcastLink(Player $player, int $type = EntityLink::TYPE_RIDER): void{
		foreach($this->getViewers() as $viewer) {
			if (!isset($viewer->getViewers()[$player->getLoaderId()])) {
				$player->spawnTo($viewer);
			}
			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $player->getId(), $type, true, true);
			$viewer->sendDataPacket($pk);
		}
	}
	
}