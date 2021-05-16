<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\item\Item;

final class FilledMap extends Item{
	public const TAG_MAP_IS_SCALING = "map_is_scaling"; //TAG_Byte
	public const TAG_MAP_SCALE = "map_scale"; //TAG_Byte
	public const TAG_MAP_UUID = "map_uuid"; //TAG_Long
	public const TAG_MAP_DISPLAY_PLAYERS = "map_display_players"; //TAG_Byte
	public const TAG_MAP_NAME_INDEX = "map_name_index"; //TAG_Int
	public const TAG_MAP_IS_INIT = "map_is_init"; //TAG_Byte

	/**
	 * @param int $meta
	 */
	public function __construct(int $meta = 0){
		parent::__construct(self::FILLED_MAP, $meta, "Filled Map");
	}

	/**
	 * @param bool $displayPlayers
	 */
	public function setDisplayPlayers(bool $displayPlayers) : void{
		$this->getNamedTag()->setByte(self::TAG_MAP_DISPLAY_PLAYERS, (int) $displayPlayers);
	}

	/**
	 * @param bool $isScaling
	 */
	public function setIsScaling(bool $isScaling) : void{
		$this->getNamedTag()->setByte(self::TAG_MAP_IS_SCALING, (int) $isScaling);
	}

	/**
	 * @return int
	 */
	public function setMapId(int $id) : void{
		$this->getNamedTag()->setString(self::TAG_MAP_UUID, (string) $id);
	}

	/**
	 * @return int
	 */
	public function getMapId() : int{
		return (int) $this->getNamedTag()->getString(self::TAG_MAP_UUID);
	}

	/**
	 * @return bool
	  */
	public function getDisplayPlayers() : bool{
		return (bool) $this->getNamedTag()->getByte(self::TAG_MAP_DISPLAY_PLAYERS);
	}

	/**
	 * @return bool
	 */
	public function getIsScaling() : bool{
		return (bool) $this->getNamedTag()->getByte(self::TAG_MAP_IS_SCALING);
	}
	
}