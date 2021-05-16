<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\utils\Color;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\Server; 

use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\Main;
use kenygamer\Core\Main2;

class RainbowArmorTask extends Task{
	/** @var Item[] */
	private $armor;
	/** @var Color */
	private $color;
	/** @var int */
	private $step = 0;
	
	private const STEPS = 16;
	
	private const COLOR_RED = 0xff0000;
	private const COLOR_GREEN = 0x00ff00;
	private const COLOR_BLUE = 0x0000ff;
	
	/** @var int[] */
	private const RAINBOW_COLORS = [
	   Main2::WINGS_COLOR_PRESET[TextFormat::RED],
	   Main2::WINGS_COLOR_PRESET[TextFormat::GOLD],
	   Main2::WINGS_COLOR_PRESET[TextFormat::YELLOW],
	   Main2::WINGS_COLOR_PRESET[TextFormat::GREEN],
	   Main2::WINGS_COLOR_PRESET[TextFormat::AQUA],
	   Main2::WINGS_COLOR_PRESET[TextFormat::LIGHT_PURPLE]
	];
	
	public function __construct(){
		$this->armor = [
		   ItemUtils::get("leather_cap"),
		   ItemUtils::get("leather_tunic"),
		   ItemUtils::get("leather_pants"),
		   ItemUtils::get("leather_boots")
		];
		$this->color = new Color(0, 0, 0);
	}
	
	/**
	 * @return int
	 */
	private function getCurrentColor() : int{
		$color = $this->step >> 4;
		if(!isset(self::RAINBOW_COLORS[$color])){
			$this->step = 0;
			$color = 0;
		}
		return self::RAINBOW_COLORS[$color];
	}
	
	/**
	 * @return int
	 */
	private function getNextColor() : int{
		$color = $this->getCurrentColor(false);
		$index = array_search($color, self::RAINBOW_COLORS);
		if(isset(self::RAINBOW_COLORS[$index + self::STEPS])){
			return self::RAINBOW_COLORS[$index + self::STEPS];
		}
		return self::RAINBOW_COLORS[0];
	}
	
	private function interpolate(int $pBegin, int $pEnd, int $pStep, int $pMax) : float{
		if($pBegin < $pEnd){
			return (($pEnd - $pBegin) * ($pStep / $pMax)) + $pBegin;
		}else{
			return (($pBegin - $pEnd) * (1 - ($pStep / $pMax))) + $pEnd;
		}
    }
    
	private function updateColor() : void{
		 /*$beginColor = $this->getCurrentColor();
		 $endColor = $this->getNextColor();
		 
		 # & prevent overflow
		 
		 $R0 = ($beginColor & self::COLOR_RED) >> 16;
		 $G0 = ($beginColor & self::COLOR_GREEN) >> 8;
		 $B0 = ($beginColor & self::COLOR_BLUE) >> 0;
		 
		 $R1 = ($endColor & self::COLOR_RED) >> 16;
		 $G1 = ($endColor & self::COLOR_GREEN) >> 8;
		 $B1 = ($endColor & self::COLOR_BLUE) >> 0;
		 
		 $this->color->setR(intval($this->interpolate($R0, $R1, $this->step, 16)));
		 $this->color->setG(intval($this->interpolate($G0, $G1, $this->step, 16)));
		 $this->color->setB(intval($this->interpolate($B0, $B1, $this->step, 16)));*/
		 
		 $this->step += self::STEPS;
	}
	
	public function onRun(int $currentTick) : void{
		$players = Server::getInstance()->getOnlinePlayers();
		foreach($players as $player){
			$armor = @$player->getArmorInventory();
			if($armor !== null && Main::getInstance()->rankCompare($player, "Nightmare") >= 0){
				$pieces = [$armor->getHelmet(), $armor->getChestplate(), $armor->getLeggings(), $armor->getBoots()];
				foreach($pieces as $slot => $piece){
					if($piece->getId() === $this->armor[$slot]->getId()){
						$piece->setCustomColor(Color::fromRGB($this->getCurrentColor()));
						$armor->setItem($slot, $piece);
						$armor->sendContents($player);
					}
				}
			}
		}
		$this->updateColor();
	}
	
}