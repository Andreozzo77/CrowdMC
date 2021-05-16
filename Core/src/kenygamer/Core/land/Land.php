<?php

declare(strict_types=1);

namespace kenygamer\Core\land;

use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\Server;

class Land{
	/** @var string */
	public $id;
	/** @var int */
	public $price;
	/** @var string */
	public $owner;
	/** @var string[] */
	public $helpers, $invitee;
	/** @var Vector3 */
	public $pos1;
	/** @var Vector3 */
	public $pos2;
	/** @var string */
	public $world;
	/** @var Vector3|null */
	public $sign;
	/** @var string[] */
	public $denied;
	/** @var int */
	public $lastPayment;
	
	public function __construct(string $id, int $price, string $owner, array $helpers, array $pos1, array $pos2, string $world, array $pos3, array $denied, int $lastPayment){
		$this->id = $id;
		$this->price = $price;
		$this->owner = $owner;
		$this->helpers = $helpers;
		$this->invitee = &$this->helpers;
		$this->pos1 = new Vector3(...$pos1);
		$this->pos2 = new Vector3(...$pos2);
		$this->world = $world;
		$this->sign = is_array($pos3) ? new Vector3(...$pos3) : null;
		$this->denied = $denied;
		$this->lastPayment = $lastPayment;
	}
	
	/**
	 * @return bool
	 */
	public function isOwned() : bool{
		return trim($this->owner) !== "";
	}
	
	/**
	 * Get center of land.
	 *
	 * @return Vector3
	 */
	public function getCenter() : Vector3{
		return new Vector3(
		    ($this->pos1->getX() + $this->pos2->getX()) / 2,
		    ($this->pos1->getY() + $this->pos2->getY()) / 2,
		    ($this->pos1->getZ() + $this->pos2->getZ()) / 2
		);
	}
	
	/**
	 * Get land size in m^2.
	 *
	 * @return int
	 */
	public function getSize() : int{
		return
		    (((max($this->pos1->getX(), $this->pos2->getX()) + 1) - (min($this->pos1->getX(), $this->pos2->getX()) - 1)) - 1) *
		    (((max($this->pos1->getZ(), $this->pos2->getZ()) + 1) - (min($this->pos1->getZ(), $this->pos2->getZ()) - 1)) - 1);
	}
	
	/**
	 * @param Position $pos
	 * @param bool $_2d
	 *
	 * @return bool
	 */
	public function contains(Position $pos, bool $_2d = false) : bool{
		return $pos->getLevel()->getFolderName() === $this->world &&
		    min($this->pos1->getX(), $this->pos2->getX()) <= $pos->getX() &&
		    max($this->pos2->getX(), $this->pos1->getX()) >= $pos->getX() &&
		    (!$_2d ?
		        (min($this->pos1->getY(), $this->pos2->getY()) <= $pos->getY() and
		        max($this->pos2->getY(), $this->pos1->getY()) >= $pos->getY())
		    : true) &&
		    min($this->pos1->getZ(), $this->pos2->getZ()) <= $pos->getZ() &&
		    max($this->pos2->getZ(), $this->pos1->getZ()) >= $pos->getZ();
	}
	
	/**
	 * @param string $player
	 *
	 * @return bool
	 */
	public function isHelper(string $player) : bool{
		$player = mb_strtolower($player);
		return (($this->isOwned() && $this->owner === $player) || array_search($player, $this->helpers) !== false);
	}
	
	/**
	 * Adds the player to this land.
	 *
	 * @param string $player
	 *
	 * @return bool
	 */
	public function addHelper(string $player) : bool{
		if($this->isHelper($player)){
			return false;
		}
		$this->helpers[] = mb_strtolower($player);
		return true;
	}
	
	/**
	 * Removes the player from this land.
	 *
	 * @param string $helper
	 *
	 * @return bool
	 */
	public function removeHelper(string $helper) : bool{
		$rm = array_search(mb_strtolower($helper), $this->helpers);
		unset($this->helpers[$rm]);
		return is_int($rm);
	}
	
	/**
	 * @param string $player
	 *
	 * @return bool
	 */
	public function toggleDeny(string $player) : bool{
		$player = mb_strtolower($player);
		if(in_array($player, $this->denied)){
			unset($this->denied[array_search($player, $this->denied)]);
			return false;
		}else{
			$this->denied[] = $player;
			return true;
		}
	}
	
}