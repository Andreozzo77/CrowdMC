<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

class MathUtils{
	
	public static function clamp($value, $min, $max){
		return $value < $min ? $min : ($value > $max ? $max : $value);
	}

	public static function getDirection(float $d0, $d1){
		if($d0 < 0){
			$d0 = -$d0;
		}

		if($d1 < 0){
			$d1 = -$d1;
		}

		return $d0 > $d1 ? $d0 : $d1;
	}

	public static function wrapDegrees(float $yaw){
		$yaw %= 360.0;
		if($yaw >= 180.0){
			$yaw -= 360.0;
		}
		if($yaw < -180.0){
			$yaw += 360.0;
		}

		return $yaw;
	}
	
}