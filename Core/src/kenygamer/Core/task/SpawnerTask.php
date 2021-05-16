<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;

use revivalpmmp\pureentities\tile\MobSpawner;
use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener2;

class SpawnerTask extends Task{
	/** @var Main */
	private $plugin;
	/** @var FloatingTextParticle[] */
	private $texts = [];
	
	private static $instance = null;
	
	public function __construct(){
		self::$instance = $this;
	}
	
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		foreach($plugin->getServer()->getLevels() as $level){
			foreach($level->getTiles() as $tile){
				if($tile instanceof MobSpawner){
					if(!$tile->isValid){
						//$level->setBlockIdAt($tile->x, $tile->y, $tile->z, 0);
						continue;
					}
					$loc = $tile->asPosition()->__toString();
					$boosters = 0;
					foreach(array_keys($tile->boosterTimes) as $booster){
						$boosters |= $booster;
					}
					$boosters = $plugin->parseBoosters($boosters);
					if(!empty($boosters)){
						$hasText = true;
						if(!isset($this->texts[$loc])){
							$this->texts[$loc] = new FloatingTextParticle($tile->asVector3(), "", TextFormat::DARK_PURPLE . strval($plugin->getSpawnerName($tile->entityId) . " Spawner"));
						}
						$timeLeft = min(array_values($tile->boosterTimes)); //time left of the booster nearest to expire
						$timeLeft = $plugin->formatTime($plugin->getTimeLeft(time() + $timeLeft));
						$text = "";
						foreach($boosters as $booster){
							$text .= "&6" . $booster . "\n";
						}
						$this->texts[$loc]->setText(TextFormat::colorize("&6" . $text . "\n" . $timeLeft));
						$tile->getLevel()->addParticle($this->texts[$loc]);
					}elseif(isset($this->texts[$loc])){
						self::removeText($loc, $tile->getLevel());
					}
					$tile->tickBoosters();
					$tile->setStackableEntities(true);
					
					MiscListener2::$spawnerEntities[$loc] = $tile->entityId; //MobSpawner::$entityId property must be public
					MiscListener2::$spawnerBoosters[$loc] = $tile->boosterTimes;
				}
			}
		}
	}
	
	/**
	 * @param string $loc
	 * @param Level $level
	 * @return bool
	 */
	public static function removeText(string $loc, Level $level) : bool{
		if(!isset(self::$instance->texts[$loc])){
			return false;
		}
		self::$instance->texts[$loc]->setInvisible(true);
		$level->addParticle(self::$instance->texts[$loc]);
		unset(self::$instance->texts[$loc]);
		return true;
	}
	
}