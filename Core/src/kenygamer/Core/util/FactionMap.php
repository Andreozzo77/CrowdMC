<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\Player;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use LegacyCore\Tasks\ScoreHudTask;

class FactionMap{
	//CONST >= 3 && CONST % 2 !== 0
	private const COLUMNS = 9, ROWS = 9;
	private const FILL_CHAR = "\xe2\x96\x88";
	
	//Ordered by priority.
	private const OBJECTIVE_SELF = "3"; //You
	private const OBJECTIVE_ALLY = "2"; //Ally
	private const OBJECTIVE_NEUTRAL = "1"; //Neutral
	private const OBJECTIVE_ENEMY = "0"; //Enemy
	
	/** @var Player */
	private $player;
	/** @var Player[] */
	private $targets = [];
	/** @var array Consisting of array of columns with array of strings (rows), possible values: " " / OBJECTIVE_* */
	private $map = [];
	
	//-Z = North
	//+Z = South
	//-X = West
	//+X = East
	
	//Y = Column
	//X = Row
	
	public function __construct(Player $player){
		$this->player = $player;
	}
	
	public function updateTargets() : void{
		$this->targets = [];
		foreach($this->player->getServer()->getOnlinePlayers() as $target){
			if($target->getId() !== $this->player->getId() && $this->getDistance($target) <= $this->getMaxDistance()){
				$this->targets[] = $target;
			}
		}
	}
	
	public function getPosition(Player $who = null) : Vector2{
		if($who === null){
			$who = $this->player;
		}
		return new Vector2($who->getX() >> 4, $who->getZ() >> 4); //Operated as ->x (x), ->y (z)
	}
	
	public function getDistance(Player $target) : float{
		return $this->getPosition($target)->distance($this->getPosition());
	}
	
	public function getMaxDistance() : int{
		return (int) floor(sqrt(self::COLUMNS * self::ROWS) / 2);
	}
	
	/**
	 * @return Vector2
	 */
	public function getCenter() : Vector2{
		return new Vector2((self::COLUMNS - 1) / 2, (self::ROWS - 1) / 2);
	}
	
	/**
	 * Rotates objective 90-degrees clockwise
	 */
	public function rotateYX(int &$Y, int &$X){
		$matrix = $this->makeEmptyMap();
		$matrix[$Y][$X] = "FindMe";
		
		$ret = [];
		//self::COLUMNS, self::ROWS: only tested with 10
		for($i = 0; $i < self::COLUMNS; $i++){
			for($j = 0; $j < self::ROWS; $j++){
				$ret[$i][$j] = $matrix[(self::ROWS) - $j - 1][$i];
			}
		}
		
		for($y = 0; $y < self::COLUMNS; $y++){
			for($x = 0; $x < self::ROWS; $x++){
				if($ret[$y][$x] === "FindMe"){
					$Y = $y;
					$X = $x;
					break 2;
				}
			}
		}
	}
	
	public function makeEmptyMap() : array{
		$map = [];
		for($y = 0; $y < self::COLUMNS; $y++){
			$map[$y] = [];
			for($x = 0; $x < self::ROWS; $x++){
				$map[$y][] = " ";
			}
		}
		return $map;
	}
	
	public function setObjective(string $objective, Vector3 $target){
		$diffZ = $this->getPosition($target)->y - $this->getPosition()->y;
		$diffX = $this->getPosition($target)->x - $this->getPosition()->x;
		//var_dump(compact("diffZ", "diffX"));
			
		$locY = $this->getMaxDistance() - (intval(floor($diffZ)));
		$locX = $this->getMaxDistance() - (intval(floor($diffX)));
		//var_dump(compact("locY", "locX"));
			
		$yaw = atan2($diffZ, $diffX) / M_PI * 180 - 90;
		if($yaw < 0){
			$yaw += 360.0;
		}
		if($objective !== self::OBJECTIVE_SELF){
			$direction = Main::getInstance()->getCompassDirection($yaw);
		
		    //Clockwise
		    $directions = [
		       "South" => ["South", "Southwest"],
		       "West" => ["West", "Northwest"],
		       "East" => ["East", "Southeast"],
		       "North" => ["North", "Northeast"],
		    ];
		    $rotateTimes = 0;
		    foreach($directions as $rounded => $list){
		    	if(in_array($direction, $list)){
		    		$rotateTimes = array_search($rounded, array_keys($directions));
		    		break;
		    	}
		    }
		    for($i = 0; $i < $rotateTimes; $i++){
		    	$this->rotateYX($locY, $locX);
		    }
		}
		
		$offsetObjective = $this->map[$locY][$locX];
		if($offsetObjective === " " || ($offsetObjective !== self::OBJECTIVE_SELF && $objective < $offsetObjective)){
			$this->map[$locY][$locX] = $objective;
		}
	}
	
	/**
	 * Fills the map and sets objectives.
	 */
	public function fillMap(){
		$factions = Main::getInstance()->getPlugin("FactionsPro");
		
		$this->map = $this->makeEmptyMap();
		
		$this->setObjective(self::OBJECTIVE_SELF, $this->player);
		
		$this->updateTargets(); //Only those in range
		foreach($this->targets as $target){
			$selfFac = $factions->getPlayerFaction($this->player->getName()) ?? "";
			$targetFac = $factions->getPlayerFaction($target->getName()) ?? "";
			
			if($targetFac === $selfFac || $factions->areAllies($targetFac, $selfFac)){
				$this->setObjective(self::OBJECTIVE_ALLY, $target);
			}elseif($targetFac === ""){
			    $this->setObjective(self::OBJECTIVE_NEUTRAL, $target);
			}elseif($targetFac !== ""){
				$this->setObjective(self::OBJECTIVE_ENEMY, $target);
			}
		}
	}
	
	/**
	 * Sends the map to the player. Main method. Call if the player is in faction.
	 */
	public function sendMap(){
		$this->fillMap();
		
		$helper = ScoreHudTask::getInstance();
		$helper->rmScoreboard($this->player, "objektName");
		$helper->createScoreboard($this->player, LangManager::translate("core-scoreboard-title", $this->player), "objektName");
		
		$stringyMap = "";
		for($y = 0; $y < self::COLUMNS; $y++){
			for($x = 0; $x < self::ROWS; $x++){
				$stringyMap .= $this->map[$y][$x];
			}
			if($y !== self::COLUMNS - 1){
				$stringyMap .= "\n";
			}
		}
		
		$stringyMap = str_replace([
		   " ",
		   self::OBJECTIVE_ENEMY,
		   self::OBJECTIVE_NEUTRAL,
		   self::OBJECTIVE_ALLY,
		   self::OBJECTIVE_SELF
		], [
		   TextFormat::GRAY . self::FILL_CHAR,
		   TextFormat::RED . self::FILL_CHAR,
		   TextFormat::GOLD . self::FILL_CHAR,
		   TextFormat::YELLOW . self::FILL_CHAR,
		   TextFormat::GREEN . self::FILL_CHAR
		], $stringyMap);
		
		$columnArray = explode("\n", $stringyMap);
		for($i = 0; $i < self::COLUMNS; $i++){
			//HACK: Insert invisible characters at the end of each scoreboard entry so Minecraft doesn't mess it up.
			$__NOT__DUPLICATE__ = str_repeat(TextFormat::ESCAPE, $i + 1);
			
			//Scoreboard entries: 1-15
			$helper->setScoreboardEntry($this->player, $i + 1, $columnArray[$i] . $__NOT__DUPLICATE__, "objektName");
		}
		
		$i++; //Grab from loop and compensate + 1
		$helper->setScoreboardEntry($this->player, ++$i, TextFormat::GREEN . self::FILL_CHAR . TextFormat::GRAY . "= You", "objektName");
		$helper->setScoreboardEntry($this->player, ++$i, TextFormat::GOLD . self::FILL_CHAR . TextFormat::GRAY . "= Ally", "objektName");
		$helper->setScoreboardEntry($this->player, ++$i, TextFormat::YELLOW . self::FILL_CHAR . TextFormat::GRAY . "= Neutral", "objektName");
		$helper->setScoreboardEntry($this->player, ++$i, TextFormat::RED . self::FILL_CHAR . TextFormat::GRAY . "= Enemy", "objektName");
		//$helper->setScoreboardEntry($this->player, ++$i, TextFormat::AQUA . TextFormat::BOLD . Main::getInstance()->getCompassDirection($this->player->getYaw()), "objektName");
	}
	
}