<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\block\Block;

class RailUtils{

	public static function isRailBlock($block): bool{
		if(is_null($block)){
			throw new \InvalidArgumentException("Rail block predicate can not accept null block");

			return false;
		}
		$id = $block;
		if($block instanceof Block){
			$id = $block->getId();
		}
		switch($id){
			case Block::RAIL:
			case Block::POWERED_RAIL:
			case Block::ACTIVATOR_RAIL:
			case Block::DETECTOR_RAIL:
				return true;
			default:
				return false;
		}
	}

}