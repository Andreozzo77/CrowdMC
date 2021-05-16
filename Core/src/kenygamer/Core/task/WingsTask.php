<?php

namespace kenygamer\Core\task;

use pocketmine\entity\Effect;
use pocketmine\level\particle\DustParticle;
use pocketmine\level\Location;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\Task;

use kenygamer\Core\Main2;

class WingsTask extends Task{
	
	public const WINGS_SHAPE = [ //13 layers
	    "   X        X   ", //ALL layers must have same length
	    "  XX        XX  ",
	    " XXXX      XXXX ",
	    " XXXX      XXXX ",
	    "  XXXX    XXXX  ",
	    "   XXXX  XXXX   ",
	    "    XXXXXXXX    ",
	    "     XXXXXX     ",
	    "      XXXX      ",
	    "     XX  XX     ",
	    "    XXX  XXX    ",
	    "    XX    XX    ",
	    "    X      X    "
	];
	
    /** @var self|null */
    private static $instance;
    
    public function __construct(){
    	self::$instance = $this;
    }
    
    /**
     * @param int $decimal
     * @return int[] 
     */
    public static function getRGBFromDecimal(int $decimal) : array{
    	$r = floor($decimal / (256 * 256));
    	$g = floor($decimal / 256) % 256;
    	$b = $decimal % 256;
    	return [$r, $g, $b];
    }
    
    /**
     * Returns the task instance.
     *
     * @return self|null
     */
    public static function getInstance() : ?self{
    	return self::$instance;
    }
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player->hasPermission("core.command.wings") && $player->getGamemode() !== Player::SPECTATOR && !$player->hasEffect(Effect::INVISIBILITY)){ //&& !$player->isOnGround()
				$this->drawParticles($player->asLocation(), $player->getName());
			}
		}
	}
	
	/**
	 * Draws the wings in the player's back.
	 *
	 * @param Location $location
	 * @param string $player
	 */
	private function drawParticles(Location $location, string $player) : void{
		$toggle = Main2::$wings->getNested($player . ".toggle", 0);
		if($toggle === Main2::WINGS_TOGGLE_DISABLE){
			return;
		}
		
		/** @var Player */
		$Player = Server::getInstance()->getPlayerExact($player);
		
		$space = 0.16 + ((Main2::$wings->getNested($player . ".size", "1.0") - 1) / 10);
		$defX = $location->x - ($space * strlen(self::WINGS_SHAPE[0]) / 2) + $space;
		$x = $defX;
		$z = $location->z;
		$y = $location->getY() + 2.8;
		$fire = -(($location->getYaw() + 180) / 60);
		$fire += ($location->getYaw() < -180 ? 3.25 : 2.985);
		
		$layers = self::WINGS_SHAPE;
		$shape = Main2::getWingsShape($player);
		$preset = Main2::WINGS_COLOR_PRESET[Main2::getWingsColorFormatFromIndex(Main2::$wings->getNested($player . ".preset", 13))];
		
		for($i = 0; $i < count($layers); $i++){
			
			$count = substr_count($layers[$i], "X");
			$k = 0;
			
			for($j = 0; $j < strlen($layers[$i]); $j++){
				if($layers[$i][$j] === "X"){
					/** @var Location */
					$target = clone $location;
					$target->x = $x;
					$target->y = $y;
					$target->z = $z;
					
					/** @var Vector3 */
					$v = $target->subtract($location);
					/** @var Vector3 */
					$v2 = $this->getBackVector($location);
					
					$v = $this->rotateAroundAxisY($v, $fire);
					$v2->y = 0;
					$v2 = $v2->multiply(-0.5);
					
					$location->x += $v->x; $location->y += $v->y; $location->z += $v->z;
					$location->x += $v2->x; $location->y += $v2->y; $location->z += $v2->z;
					if($location->level !== null){
						switch($toggle){
							case Main2::WINGS_TOGGLE_ADVANCED:
							   list($r, $g, $b) = self::getRGBFromDecimal(Main2::WINGS_COLOR_PRESET[Main2::getWingsColorFormatFromIndex($shape[$i][$k++])]);
							   break;
							case Main2::WINGS_TOGGLE_PRESET:
							   list($r, $g, $b) = self::getRGBFromDecimal($preset);
							   break;
							default:
							   return;
						}
						$players = [];
						foreach(Server::getInstance()->getOnlinePlayers() as $p){
							if($p->canSee($Player)){
								$players[] = $p;
							}
						}
						$location->level->addParticle(new DustParticle($location, $r, $g, $b, 1), $players);
					}
					$location->x -= $v->x; $location->y -= $v->y; $location->z -= $v->z;
					$location->x -= $v2->x; $location->y -= $v2->y; $location->z -= $v2->z;
				}
				$x += $space;
			}
			$y -= $space;
			$z -= 0.05;
			$x = $defX;
		}
	}

    /**
     * @param Vector3 $v
     * @param float $fire
     *
     * @return Vector3
     */
    private function rotateAroundAxisY(Vector3 $v, float $fire) : Vector3{
        $cos = cos($fire);
        $sin = sin($fire);
        $x = $v->x * $cos + $v->z * $sin;
        $z = $v->x * -$sin + $v->z * $cos;
        $v->x = $x;
        $v->z = $z;
        return $v;
    }

    /**
     * @param Location $loc
     *
     * @return Vector3
     */
    public function getBackVector(Location $loc) : Vector3{
        $newZ = (float) ($loc->z + (1 * sin(deg2rad($loc->yaw + 90 * 1))));
        $newX = (float) ($loc->x + (1 * cos(deg2rad($loc->yaw + 90 * 1))));
        return new Vector3($newX - $loc->x, 0, $newZ - $loc->z);
    }

}