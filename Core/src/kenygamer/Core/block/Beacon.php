<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use kenygamer\Core\listener\MiscListener2;
use kenygamer\Core\inventory\BeaconInventory;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\tile\Tile;
use kenygamer\Core\tile\BeaconTile;
use pocketmine\block\Air;
use pocketmine\block\block;
use pocketmine\block\Transparent;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\StringTag;
use pocketmine\Player;

class Beacon extends Transparent{

	/**
	 * @var int
	 */
	protected $id = self::BEACON;

	/**
	 * Beacon constructor.
	 * @param int $meta
	 */
	public function __construct($meta = 0){
		$this->meta = $meta;
	}

	/**
	 * @return bool
	 */
	public function canBeActivated() : bool{
		return true;
	}

	/**
	 * @return string
	 */
	public function getName() : string{
		return "Beacon";
	}

	/**
	 * @return int
	 */
	public function getLightLevel() : int{
		return 15;
	}
	
	/**
	 * @return float
	 */
	public function getBlastResistance() : float{
		return 15;
	}

	/**
	 * @return float
	 */
	public function getHardness() : float{
		return 3;
	}

	/**
	 * @param Item $item
	 * @param Block $blockReplace
	 * @param Block $blockClicked
	 * @param int $face
	 * @param Vector3 $clickVector
	 * @param Player|null $player
	 * @return bool
	 */
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$this->getLevel()->setBlock($this, $this, true, true);
		/** @var CompoundTag $nbt */
		$nbt = new CompoundTag("", [
			new StringTag("id", "Beacon"),
			new ByteTag("isMovable", 0),
			new IntTag("primary", 0),
			new IntTag("secondary", 0),
			new IntTag("x", $blockReplace->x),
			new IntTag("y", $blockReplace->y),
			new IntTag("z", $blockReplace->z),
		]);
		Tile::createTile("BeaconTile", $this->getLevel(), $nbt);

		return true;
	}

	/**
	 * @param Item $item
	 * @param Player|null $player
	 * @return bool
	 */
	public function onActivate(Item $item, Player $player = null) : bool{
		if(!($player instanceof Player)){
			return false;
		}
		/** @var Tile $t */
		$t = $this->getLevel()->getTile($this);
		/** @var BeaconInventory $beacon */
		$beacon = null;
		if($t instanceof BeaconTile){
				/** @var BeaconTile $beacon */
			$beacon = $t;
		}else{
			/** @var CompoundTag $nbt */
			$nbt = new CompoundTag("", [
				new StringTag("id", "Beacon"),
				new ByteTag("isMovable", 0),
				new IntTag("primary", 0),
				new IntTag("secondary", 0),
				new IntTag("x", $this->x),
				new IntTag("y", $this->y),
				new IntTag("z", $this->z),
			]);
			$beacon = Tile::createTile("BeaconTile", $this->getLevel(), $nbt);
			echo "made new tile\n";
		}
		$inv = $beacon->getInventory();
		if($inv instanceof BeaconInventory){
			$player->addWindow($beacon->getInventory(), WindowTypes::BEACON);
			MiscListener2::$usingBeacon[$player->getName()] = $this->asPosition();
		}
		return true;
	}

	/**
	 * @param Item $item
	 * @param Player|null $player
	 * @return bool
	 */
	public function onBreak(Item $item, Player $player = null) : bool{
		$this->getLevel()->setBlock($this, new Air(), true, true);
		return true;
	}
	
}