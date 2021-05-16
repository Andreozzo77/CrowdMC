<?php

declare(strict_types=1);

namespace kenygamer\Core\block;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\math\Vector3;
use pocketmine\block\Block;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\StringTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\utils\TextFormat;
use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener2;
use revivalpmmp\pureentities\block\MonsterSpawnerPEX;
use revivalpmmp\pureentities\tile\MobSpawner;

class MonsterSpawner extends MonsterSpawnerPEX{
	/** @var int */
	public $entityId = -1;
	/** @var int */
	public $isValid = 0;
	
	public function __construct(int $meta = 0){
		$this->meta = $meta;
	}
	
	public function canBeActivated() : bool{
		return false;
	}
	
	public function isAffectedBySilkTouch() : bool{
		return true;
	}
	
	public function place(Item $item, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector, Player $player = null) : bool{
		$eid = $item->getNamedTag()->getInt("EntityId", $this->entityId, true);
		$test = $this->getSilkTouchDrops($item)[0];
		$test->setDamage($eid);
		$return = parent::place($test, $blockReplace, $blockClicked, $face, $clickVector, $player);
		
		$tile = $this->level->getTile($this);
		if($tile instanceof MobSpawner){
			$boosters = [];
			foreach($item->getNamedTag()->getTagValue("Boosters", ListTag::class, [], true) as $booster){
				list($booster, $timeLeft) = explode(":", $booster->getValue());
				$boosters[$booster] = $timeLeft;
			}
			$tile->boosterTimes = $boosters;
			$tile->isValid = $item->getNamedTag()->getInt("IsValid", 0);
		}
		
		$this->entityId = $eid; //????
		
		return $return;
	}
	
	public function getSilkTouchDrops(Item $item) : array{
		if(isset($this->level)){
			$loc = $this->asPosition()->__toString();
			if(isset(MiscListener2::$spawnerEntities[$loc])){
				$this->entityId = MiscListener2::$spawnerEntities[$loc];
				unset(MiscListener2::$spawnerEntities[$loc]);
			}
		}
		
		$spawner = ItemFactory::get(Item::MONSTER_SPAWNER, 0, 1);
		$nbt = $spawner->getNamedTag();
		$nbt->setInt("EntityId", $this->entityId);
		$nbt->setInt("IsValid", intval($this->entityId !== -1));
		
		$boosters = [];
		if(isset($loc)){
			foreach(MiscListener2::$spawnerBoosters[$loc] ?? [] as $booster => $time){
				$boosters[] = new StringTag((string) $booster, strval($booster) . ":" . strval($time));
			}
			$nbt->setTag(new ListTag("Boosters", $boosters, NBT::TAG_String));
		}
		$spawner->setNamedTag($nbt);
		if($this->entityId !== -1){
			$spawner->setCustomName(TextFormat::colorize("&r&a" . Main::getInstance()->getSpawnerName($this->entityId) . " Spawner"));
			if(count($boosters) > 0){
				$spawner->setLore([
				   TextFormat::colorize("&r&7" . count($boosters) . " paused boosters")
				]);
			}
		}
		return [$spawner];
	}
	
}