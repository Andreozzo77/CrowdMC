<?php

/**
 * PureEntitiesX: Mob AI Plugin for PMMP
 * Copyright (C)  2018 RevivalPMMP
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace revivalpmmp\pureentities\tile;

use pocketmine\level\Level;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\tile\Spawnable;
use pocketmine\utils\TextFormat;

use revivalpmmp\pureentities\data\Data;
use revivalpmmp\pureentities\data\NBTConst;
use revivalpmmp\pureentities\PluginConfiguration;
use revivalpmmp\pureentities\PureEntities;
use revivalpmmp\pureentities\entity\BaseEntity;

class MobSpawner extends Spawnable{

    public $entityId = -1;
    public $isValid = 0;
    protected $spawnRange = 8;
    protected $maxNearbyEntities = 1;
    protected $requiredPlayerRange = 16;

    public $delay = 0;

    protected $minSpawnDelay = self::BASE_DELAY;
    protected $maxSpawnDelay = self::BASE_DELAY * 2;
    protected $spawnCount = 0;
    
    public $boosterTimes = [];
    
    public const BOOSTER_X2_RATE = 0x1; //1
	public const BOOSTER_X3_RATE = 0x2; //2
	public const BOOSTER_X4_RATE = 0x4; //4
	
	public const BOOSTER_X2_COUNT = 0x8; //8
	public const BOOSTER_X3_COUNT = 1 << 4; //16
	public const BOOSTER_X4_COUNT = 1 << 5; //32
	
	public const BASE_DELAY = 400;
	public const BASE_COUNT = 1;
	
    private $stackableEntities = false;

    public function __construct(Level $level, CompoundTag $nbt){

        parent::__construct($level, $nbt);

        $this->scheduleUpdate();
    }
    
    /**
     * Ticks boosters.
     */
    public function tickBoosters() : void{
    	foreach($this->boosterTimes as $booster => $time){
    		$finalTime = --$this->boosterTimes[$booster];
    		if($finalTime === 0){
    			switch($booster){
    				 case self::BOOSTER_X2_RATE:
    				 case self::BOOSTER_X3_RATE:
    				 case self::BOOSTER_X4_RATE:
    				     $this->setMinSpawnDelay(self::BASE_DELAY);
    				     $this->setMaxSpawnDelay(self::BASE_DELAY * 2);
    				     break;
    				 case self::BOOSTER_X2_COUNT:
    				 case self::BOOSTER_X3_COUNT:
    				 case self::BOOSTER_X4_COUNT:
    				     $this->setMaxNearbyEntities(self::BASE_COUNT);
    				     break;
				}
				unset($this->boosterTimes[$booster]);
			}
		}
    }
    
    /**
     * Applies boosters to this spawner.
     *
     * @param array $arr booster => time
     */
    public function applyBoosters(array $arr) : void{
    	$delay = $this->minSpawnDelay;
    	$count = $this->maxNearbyEntities;
		
		/** @var int */
		$boosters = array_sum(array_keys($arr));
		
		if($boosters & self::BOOSTER_X4_RATE){
			$this->boosterTimes[self::BOOSTER_X4_RATE] = $arr[self::BOOSTER_X4_RATE];
			$delay /= 4;
		}
		if($boosters & self::BOOSTER_X3_RATE){
			$this->boosterTimes[self::BOOSTER_X3_RATE] = $arr[self::BOOSTER_X3_RATE];
			$delay /= 3;
		}
		if($boosters & self::BOOSTER_X2_RATE){
			$this->boosterTimes[self::BOOSTER_X2_RATE] = $arr[self::BOOSTER_X2_RATE];
			$delay /= 2;
		}
		$this->setMinSpawnDelay($delay);
		$this->setMaxSpawnDelay($delay * 2);
		
		if($boosters & self::BOOSTER_X4_COUNT){
			$this->boosterTimes[self::BOOSTER_X4_COUNT] = $arr[self::BOOSTER_X4_COUNT];
			$count *= 4;
		}
		if($boosters & self::BOOSTER_X3_COUNT){
			$this->boosterTimes[self::BOOSTER_X3_COUNT] = $arr[self::BOOSTER_X3_COUNT];
			$count *= 3;
		}
		if($boosters & self::BOOSTER_X2_COUNT){
			$this->boosterTimes[self::BOOSTER_X2_COUNT] = $arr[self::BOOSTER_X2_COUNT];
			$count *= 2;
		}
		$this->setMaxNearbyEntities($count);
	}

    public function onUpdate() : bool{
        try{
        if($this->isClosed()){
            return false;
        }
        if($this->entityId === -1){
            PureEntities::logOutput("onUpdate Called with EntityID of -1");
            return false;
        }
        if($this->delay % 100 === 0 && count($this->boosterTimes)){
        }
        if($this->delay++ >= \kenygamer\Core\Main::mt_rand($this->minSpawnDelay, $this->maxSpawnDelay)){
            $this->delay = 0;

            $count = 0;
            $isValid = false;
            foreach($this->level->getEntities() as $entity){
                if($entity->distance($this) <= $this->requiredPlayerRange){
                    if($entity instanceof Player){
                        $isValid = true;
                    }
                    
                    if($this->stackableEntities){
                        $tag = $entity->namedtag->getTag("EntityCount");
                        if($tag instanceof IntTag){
                            $count += $tag->getValue();
                            break;
                        }
                    }elseif($entity instanceof BaseEntity){
                    	$count++;
                    }
                    break;
                }
            }

            if($isValid && $count <= $this->maxNearbyEntities){
                $y = $this->y;
                $x = $this->x + \kenygamer\Core\Main::mt_rand(-$this->spawnRange, $this->spawnRange);
                $z = $this->z + \kenygamer\Core\Main::mt_rand(-$this->spawnRange, $this->spawnRange);
                $pos = PureEntities::getSuitableHeightPosition($x, $y, $z, $this->level);
                if(!isset(Data::HEIGHTS[$this->entityId])){
                    return false;
                }
                $pos->y += Data::HEIGHTS[$this->entityId];
                $entity = PureEntities::create($this->entityId, $pos);
                if($entity != null){
                	
                	//Cooldown
                	$totalEntities = 0;
                	foreach($entity->getLevel()->getEntities() as $e){
                		if(spl_object_hash($e) !== spl_object_hash($entity) && $e instanceof $entity && $e->distance($entity) <= $this->requiredPlayerRange){
                			$totalEntities++;
                		}
                	}
                	if($totalEntities >= $this->maxNearbyEntities){
                		$entity->close();
                	    return false;
                	    
                	}
                	if($this->stackableEntities && !$isBear){
                		
                		//ye, basically one spawner will command the other spawners
                		$count = 1;
                		foreach($this->getLevel()->getTiles() as $tile){
                			if($tile instanceof self && spl_object_hash($tile) !== spl_object_hash($this) && $tile->distance($this) <= 32 && $tile->entityId === $this->entityId){
                				$count++;
                				$tile->delay = 0;
                			}
                		}
                		
                		$nbt = $entity->namedtag;
                		$nbt->setInt("EntityCount", $count = \kenygamer\Core\Main::mt_rand(1, $count));
                				
                		$entity->namedtag = $nbt;
                		
                		$entity->setMaxHealth($entity->getMaxHealth() * $count);
                		$entity->setHealth($entity->getMaxHealth());
                		$entity->setNameTag(TextFormat::colorize("&5x" . $count . " &6" . ((new \ReflectionClass($entity))->getShortName())));
                	}
                	$entity->setNameTagVisible(true);
                	$entity->setNameTagAlwaysVisible(true);
                	
                    $entity->spawnToAll();
                    
                }
            }
        }
        $this->scheduleUpdate();
        return true;
        }catch(\ErrorException $e){
            return false;
        }
    }
    
    //Custom code
    public function setStackableEntities(bool $stackableEntities) : void{
        $this->stackableEntities = $stackableEntities;
    }
    
    public function setSpawnEntityType(int $entityId){
        $this->entityId = $entityId;
        if(true){
            $this->writeSaveData($tag = new CompoundTag());
        }
        $this->onChanged();
        $this->scheduleUpdate();
    }

    public function setMinSpawnDelay(int $minDelay){
        if($minDelay > $this->maxSpawnDelay){
            return;
        }

        $this->minSpawnDelay = $minDelay;
    }

    public function setMaxSpawnDelay(int $maxDelay){
        if($this->minSpawnDelay > $maxDelay or $maxDelay === 0){
            return;
        }

        $this->maxSpawnDelay = $maxDelay;
    }

    public function setSpawnDelay(int $minDelay, int $maxDelay){
        if($minDelay > $maxDelay){
            return;
        }

        $this->minSpawnDelay = $minDelay;
        $this->maxSpawnDelay = $maxDelay;
    }

    public function setRequiredPlayerRange(int $range){
        $this->requiredPlayerRange = $range;
    }

    public function setMaxNearbyEntities(int $count){
        $this->maxNearbyEntities = $count;
    }

    public function addAdditionalSpawnData(CompoundTag $nbt) : void{
        $nbt->setByte(NBTConst::NBT_KEY_SPAWNER_IS_MOVABLE, 1);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_DELAY, 0);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, $this->maxNearbyEntities);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, $this->maxSpawnDelay);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, $this->minSpawnDelay);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, $this->requiredPlayerRange);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT, $this->spawnCount);
        $nbt->setShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE, $this->spawnRange);
        $nbt->setInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, $this->entityId);
        $boosters = [];
        foreach($this->boosterTimes as $booster => $time){
        	$boosters[] = new StringTag((string) $booster, strval($booster) . ":" . strval($time));
		}
		$nbt->setTag(new ListTag("Boosters", $boosters, NBT::TAG_String));
		$nbt->setInt("IsValid", $this->isValid);
        //$spawnData = new CompoundTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_DATA, [new StringTag("id", $this->entityId)]);
        //$nbt->setTag($spawnData);
        $this->scheduleUpdate();
    }

    public function readSaveData(CompoundTag $nbt) : void{
        if(true){ //PluginConfiguration::getInstance()->getEnableNBT()

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID)){
                $this->setSpawnEntityType($nbt->getInt(NBTConst::NBT_KEY_SPAWNER_ENTITY_ID, -1, true));
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE)){
                $this->spawnRange = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_RANGE, 8, true);
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY)){
                $this->minSpawnDelay = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_MIN_SPAWN_DELAY, 200, true);
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY)){
                $this->maxSpawnDelay = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_MAX_SPAWN_DELAY, 800, true);
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_DELAY)){
                $this->delay = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_DELAY, 0, true);
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES)){
                $this->maxNearbyEntities = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_MAX_NEARBY_ENTITIES, 6, true);
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE)){
                $this->requiredPlayerRange = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_REQUIRED_PLAYER_RANGE, 16);
            }

            if($nbt->hasTag(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT)){
                $this->spawnCount = $nbt->getShort(NBTConst::NBT_KEY_SPAWNER_SPAWN_COUNT, 0, true);
            }
            
            if($nbt->hasTag("Boosters")){
            	foreach($nbt->getTagValue("Boosters", ListTag::class, [], true) as $booster){
            		list($booster, $timeLeft) = explode(":", $booster->getValue());
            		$this->boosterTimes[$booster] = $timeLeft;
            	}
            }
            $this->isValid = $nbt->getInt("IsValid", 0);
        }
    }

    public function writeSaveData(CompoundTag $nbt) : void{
        if(true){
            $this->addAdditionalSpawnData($nbt);
        }
    }

    public function getSpawnCount() : int{
        return $this->spawnCount;
    }
}