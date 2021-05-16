<?php

declare(strict_types=1);

namespace kenygamer\Core\land;

use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\utils\Config;
use kenygamer\Core\Main;
use kenygamer\Core\util\SQLiteConfig;

class LandManager{
	/** @var Land[] */
	private $lands;
	/** @var Config|null */
	private $data;
	/** @var Config|null */
	public $homeland;
	
	public function __construct(){
		$plugin = Main::getInstance();
		$this->lands = [];
		$this->data = new SQLiteConfig($plugin->getDataFolder() . "server.db", "lands");
		$this->homeland = new SQLiteConfig($plugin->getDataFolder() . "server.db", "homeland");
		foreach($this->data->getAll() as $key => $pp){
			$this->lands[$pp["id"]] = new Land($pp["id"], $pp["price"], $pp["owner"], $pp["helpers"], $pp["pos1"], $pp["pos2"], $pp["world"], $pp["sign"], $pp["denied"], $pp["lastPayment"]);
		}
		$plugin->getScheduler()->scheduleRepeatingTask(new LandTask(), 20);
	}
	
	/**
	 * @return array
	 */
	public function getAll() : array{
		return $this->lands;
	}
	
	/**
	 * Save lands.
	 */
	public function saveAll() : void{
		$lands = [];
		foreach($this->lands as $landKey => $land){
			if($land->sign !== null){
				$signCoords = [
				    $land->sign->getX(),
				    $land->sign->getY(),
				    $land->sign->getZ()
				];
			}
			$lands[$land->id] = [
			    "id" => $land->id,
			    "price" => $land->price,
			    "owner" => $land->owner,
			    "helpers" => $land->helpers,
			    "pos1" => [$land->pos1->getX(), $land->pos1->getY(), $land->pos1->getZ()],
                "pos2" => [$land->pos2->getX(), $land->pos2->getY(), $land->pos2->getZ()],
			    "world" => $land->world,
			    "sign" => $signCoords ?? null,
			    "denied" => $land->denied,
			    "lastPayment" => $land->lastPayment
			];
		}
		$this->data->setAll($lands);
		$this->data->save();
	}
	
	/**
	 * @param string|int $id
	 *
	 * @return Land|null
	 */
	public function getLand($id) : ?Land{
		$id = strval($id);
		foreach($this->lands as $land){
			if($land->id === $id){
				return $land;
			}
		}
		return null;
	}
	
	/**
	 * Get the land by position.
	 *
	 * @param Position $pos
	 *
	 * @return Land|null
	 */
	public function getLand2(Position $pos) : ?Land{
		foreach($this->lands as $land){
			if($land->contains($pos)){
				return $land;
			}
		}
		return null;
	}
	
	/**
	 * Get land by sign.
	 *
	 * @param Position $pos
	 *
	 * @return Land|null
	 */
	public function getLandBySign(Position $pos) : ?Land{
		foreach($this->lands as $land){
			if($land->sign !== null && $land->world === $pos->getLevel()->getFolderName() && $land->sign->equals($pos)){
				return $land;
			}
		}
		return null;
	}
	
	/**
	 * Dispose a land.
	 *
	 * @param Land|string|int $ref
	 *
	 * @return bool
	 */
	public function disposeLand($ref) : bool{
		if(is_object($ref)){
			if(!$ref->isOwned()) return false;
			$backup = clone $ref;
			$ref->owner = "";
			$ref->lastPayment = -1;
		}else{
			$land = $this->getLand($ref);
			if(is_null($land) || !$land->isOwned()){
				return false;
			}
			$backup = clone $land;
			$land->owner = "";
			$land->lastPayment = -1;
		}
		Main::getInstance()->getServer()->broadcastMessage("Land #" . $backup->id . " disposed by " . $backup->owner);
		return true;
	}
  
    /**
     * Delete a land.
     *
     * @param string|int $id
     *
     * @return bool
     */
    public function deleteLand($id) : bool{
        $land = $this->getLand($id);
        if(!($land instanceof Land)){
            return false;
        }
        unset($this->lands[$land->id]);
        return true;
    }
    
    /**
     * Create a land.
     *
     * @param float[] $pos1 x, y, z
     * @param float[] $pos2 x, y, z
     * @param string $world
     * @param float[] $pos3 x, y, z
     * @param int $price
     *
     * @return string|bool
     */
    public function createLand(array $pos1, array $pos2, string $world, array $pos3, int $price){
    	$id = 1;
    	foreach($this->lands as $land){
    		$ids[] = $land->id;
    		$id = $land->id > $id ? $land->id : $id;
    	}
    	while(in_array($id, $ids ?? [])){
    		if($id === PHP_INT_MAX){
    			return false;
    		}
    		$id++;
    	}
    	if(count($pos1) !== 3 || count($pos2) !== 3){
    		throw new \InvalidArgumentException("Argument 1 must be an array of 3 elements");
    	}
    	$this->lands[strval($id)] = new Land(strval($id), $price, "", [], [(int) floor($pos1[0]), (int) floor($pos1[1]), (int) floor($pos1[2])], [(int) floor($pos2[0]), (int) floor($pos2[1]), (int) floor($pos2[2])], $world, $pos3, [], -1);
    	return $id;
    }
	
}