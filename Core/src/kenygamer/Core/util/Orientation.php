<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\math\Vector3;

use kenygamer\Core\block\RailBlock;

class Orientation{

	// The type of the rail
	const STRAIGHT = 0;
	const ASCENDING = 1;
	const CURVED = 2;

	/** @var int */
	private $meta;
	/** @var int */
	private $state;
	/** @var int[] */
	private $connectingDirections;
	/** @var int|null */
	private $ascendingDirection;

	private function __construct(int $meta, int $state, int $from, int $to, ?int $ascendingDirection){
		$this->meta = $meta;
		$this->state = $state;
		$this->connectingDirections[$from] = $from;
		$this->connectingDirections[$to] = $to;
		$this->ascendingDirection = $ascendingDirection;
	}

	/**
	 * Get all of the possible orientation that
	 * been made with rail.
	 *
	 * @return Orientation[]
	 */
	public static function getMetadata(): array{
		$railMetadata = [];

		$railMetadata[] = new Orientation(0, self::STRAIGHT, Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH, null);
		$railMetadata[] = new Orientation(1, self::STRAIGHT, Vector3::SIDE_EAST, Vector3::SIDE_WEST, null);
		$railMetadata[] = new Orientation(2, self::ASCENDING, Vector3::SIDE_EAST, Vector3::SIDE_WEST, Vector3::SIDE_EAST);
		$railMetadata[] = new Orientation(3, self::ASCENDING, Vector3::SIDE_EAST, Vector3::SIDE_WEST, Vector3::SIDE_WEST);
		$railMetadata[] = new Orientation(4, self::ASCENDING, Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH, Vector3::SIDE_NORTH);
		$railMetadata[] = new Orientation(5, self::ASCENDING, Vector3::SIDE_NORTH, Vector3::SIDE_SOUTH, Vector3::SIDE_SOUTH);
		$railMetadata[] = new Orientation(6, self::CURVED, Vector3::SIDE_SOUTH, Vector3::SIDE_EAST, null);
		$railMetadata[] = new Orientation(7, self::CURVED, Vector3::SIDE_SOUTH, Vector3::SIDE_WEST, null);
		$railMetadata[] = new Orientation(8, self::CURVED, Vector3::SIDE_NORTH, Vector3::SIDE_WEST, null);
		$railMetadata[] = new Orientation(9, self::CURVED, Vector3::SIDE_NORTH, Vector3::SIDE_EAST, null);

		return $railMetadata;
	}

	/**
	 * Get orientation metadata by their damage or
	 * meta.
	 *
	 * @param int $meta
	 * @return Orientation
	 */
	public static function byMetadata(int $meta): Orientation{
		if($meta < 0 || $meta >= count(Rail::$railMetadata)){
			$meta = 0;
		}

		return Rail::$railMetadata[$meta];
	}

	/**
	 * Gets the metadata for a straight railways.
	 *
	 * @param int $face
	 * @return Orientation
	 */
	public static function getNormalRail(int $face): Orientation{
		switch($face){
			case Vector3::SIDE_NORTH:
			case Vector3::SIDE_SOUTH:
				return RailBlock::$railMetadata[RailBlock::STRAIGHT_NORTH_SOUTH];
			case Vector3::SIDE_EAST:
			case Vector3::SIDE_WEST:
				return RailBlock::$railMetadata[RailBlock::STRAIGHT_EAST_WEST];
		}

		return RailBlock::$railMetadata[RailBlock::STRAIGHT_NORTH_SOUTH];
	}

	/**
	 * Gets the metadata for the ascending rail.
	 *
	 * @param int $face
	 * @return Orientation
	 */
	public static function getAscendingData(int $face): Orientation{
		switch($face){
			case Vector3::SIDE_NORTH:
				return RailBlock::$railMetadata[Rail::ASCENDING_NORTH];
			case Vector3::SIDE_SOUTH:
				return RailBlock::$railMetadata[Rail::ASCENDING_SOUTH];
			case Vector3::SIDE_EAST:
				return RailBlock::$railMetadata[Rail::ASCENDING_EAST];
			case Vector3::SIDE_WEST:
				return RailBlock::$railMetadata[Rail::ASCENDING_WEST];
		}

		return RailBlock::$railMetadata[Rail::ASCENDING_EAST];
	}

	/**
	 * Get if the rail could be curved to the specific
	 * direction based on the given parameters.
	 *
	 * @param int $face1
	 * @param int $face2
	 * @return Orientation
	 */
	public static function getCurvedState(int $face1, int $face2): Orientation{
		$origin = [RailBlock::CURVED_SOUTH_EAST, RailBlock::CURVED_SOUTH_WEST, RailBlock::CURVED_NORTH_WEST, RailBlock::CURVED_NORTH_EAST];
		foreach($origin as $side){
			$o = RailBlock::$railMetadata[$side];

			if(isset($o->connectingDirections[$face1]) && isset($o->connectingDirections[$face2])){
				return $o;
			}
		}

		return RailBlock::$railMetadata[RailBlock::CURVED_SOUTH_EAST];
	}

	/**
	 * Get if the rail that could possibly changes it
	 * orientation to either straight or curved.
	 *
	 * @param int $face1
	 * @param int $face2
	 * @return Orientation
	 */
	public static function getConnectedState(int $face1, int $face2): Orientation{
		$origin = Orientation::getHorizontalRails();
		foreach($origin as $side){
			$o = RailBlock::$railMetadata[$side];

			if(isset($o->connectingDirections[$face1]) && isset($o->connectingDirections[$face2])){
				return $o;
			}
		}

		return RailBlock::$railMetadata[Rail::STRAIGHT_NORTH_SOUTH];
	}

	/**
	 * Gets all the horizontal rails as array
	 *
	 * @return array
	 */
	public static function getHorizontalRails(): array{
		return [RailBlock::STRAIGHT_NORTH_SOUTH, RailBlock::STRAIGHT_EAST_WEST, RailBlock::CURVED_SOUTH_EAST, RailBlock::CURVED_SOUTH_WEST, RailBlock::CURVED_NORTH_WEST, RailBlock::CURVED_NORTH_EAST];
	}

	/**
	 * Returns the metadata of this orientation
	 *
	 * @return int
	 */
	public function getDamage(): int{
		return $this->meta;
	}

	/**
	 * Gets if the of the rail has its own possible
	 * connections to the other rail.
	 *
	 * @param int ...$faces
	 * @return bool
	 */
	public function hasConnectingDirections(int... $faces): bool{
		// That's deep
		foreach($faces as $direction){
			if(!isset($this->connectingDirections[$direction])){
				return false;
			}
		}

		return true;
	}

	/**
	 * Get the possible connections that this
	 * rail could connects with.
	 *
	 * @return int[]
	 */
	public function connectingDirections(): array{
		return $this->connectingDirections;
	}

	/**
	 * Get the ascending direction for this
	 * rail metadata.
	 *
	 * @return int|null
	 */
	public function ascendingDirection(){
		return $this->ascendingDirection;
	}

	/**
	 * Gets if this rail could be straight
	 *
	 * @return bool
	 */
	public function isStraight(): bool{
		return $this->state == self::STRAIGHT;
	}

	/**
	 * Checks if this rail is ascending
	 *
	 * @return bool
	 */
	public function isAscending(): bool{
		return $this->state == self::ASCENDING;
	}

	/**
	 * Checks if this rail is curved
	 *
	 * @return bool
	 */
	public function isCurved(): bool{
		return $this->state == self::CURVED;
	}
}