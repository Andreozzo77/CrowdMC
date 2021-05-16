<?php

declare(strict_types=1);

namespace kenygamer\Core\map;

use pocketmine\math\Vector3;
use pocketmine\utils\Color;

final class MapData implements \JsonSerializable{
	/** @var int */
	protected $id;
	/** @var Color[][] */
	protected $colors = [];
	/** @var bool */
	protected $displayPlayers = false;
	/** @var Vector3 */
	protected $center;

	/**
	 * @param int $id
	 * @param Color[] $colors
	 * @param bool $displayPlayers
	 * @param Vector3 $center
	 */
	public function __construct(int $id, array $colors, bool $displayPlayers, Vector3 $center){
		$this->id = $id;
		$this->colors = $colors;
		$this->displayPlayers = $displayPlayers;
		$this->center = $center;
	}

	/**
	 * @return int
	 */
	public function getMapId() : int{
		return $this->id;
	}

	/**
	 * @param Color[][] $colors
	 */
	public function setColors(array $colors) : void{
		$this->colors = $colors;
	}

	/**
	 * @return Color[][]
	 */
	public function getColors() : array{
		return $this->colors;
	}

	public function getDisplayPlayers() : bool{
		return $this->displayPlayers;
	}

	public function getCenter() : Vector3{
		return $this->center;
	}

	/**
	 * @return array
	 */
	public function jsonSerialize(){
		return [
			"id" => $this->id,
			"displayPlayers" => $this->displayPlayers,
			"center" => implode(":", [$this->center->getX(), $this->center->getY(), $this->center->getZ()])
		];
	}
	
}