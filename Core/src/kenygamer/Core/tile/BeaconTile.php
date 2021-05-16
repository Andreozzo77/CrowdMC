<?php

declare(strict_types=1);

namespace kenygamer\Core\tile;

use kenygamer\Core\inventory\BeaconInventory;
use pocketmine\block\Block;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\inventory\InventoryHolder;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Spawnable;

class BeaconTile extends Spawnable implements InventoryHolder {
	/** @var string */
	public const
		TAG_PRIMARY = "primary",
		TAG_SECONDARY = "secondary";
	
	/** @var BeaconInventory */
	private $inventory;
	/** @var CompoundTag */
	private $nbt;
	
	/**
	 * @param Level $level
	 * @param CompoundTag $nbt
	 */
	public function __construct(Level $level, CompoundTag $nbt){
		if(!$nbt->hasTag(self::TAG_PRIMARY, IntTag::class)){
			$nbt->setInt(self::TAG_PRIMARY, 0);
		}
		if(!$nbt->hasTag(self::TAG_SECONDARY, IntTag::class)){
			$nbt->setInt(self::TAG_SECONDARY, 0);
		}
		$this->inventory = new BeaconInventory($this);
		parent::__construct($level, $nbt);
		$this->scheduleUpdate();
	}
	
	/**
	 * @return CompoundTag
	 */
	public function saveNBT() : CompoundTag{
		return parent::saveNBT();
	}
	
	/**
	 * @param CompoundTag $nbt
	 */
	public function addAdditionalSpawnData(CompoundTag $nbt) : void{
		$nbt->setInt(self::TAG_PRIMARY, $this->getNBT()->getInt(self::TAG_PRIMARY));
		$nbt->setInt(self::TAG_SECONDARY, $this->getNBT()->getInt(self::TAG_SECONDARY));
	}
	
	/**
	 * @return CompoundTag
	 */
	public function getNBT() : CompoundTag{
		return $this->nbt;
	}
	
	/**
	 * @param CompoundTag $nbt
	 * @param Player $player
	 * @return bool
	 */
	public function updateCompoundTag(CompoundTag $nbt, Player $player) : bool{
		$this->setPrimaryEffect($nbt->getInt(self::TAG_PRIMARY));
		$this->setSecondaryEffect($nbt->getInt(self::TAG_SECONDARY));

		return true;
	}
	
	/**
	 * @param int $effectId
	 */
	public function setPrimaryEffect(int $effectId){
		$this->getNBT()->setInt(self::TAG_PRIMARY, $effectId);
	}
	
	/**
	 * @param int $effectID
	 */
	public function setSecondaryEffect(int $effectId){
		$this->getNBT()->setInt(self::TAG_SECONDARY, $effectId);
	}
	
	/**
	 * @param Item $item
	 * @return bool
	 */
	public function isPaymentItem(Item $item) : bool{
		return in_array($item->getId(), [Item::DIAMOND, Item::IRON_INGOT, Item::GOLD_INGOT, Item::EMERALD]);
	}
	
	/**
	 * @return bool
	 */
	public function isSecondaryAvailable() : bool{
		return $this->getLayers() >= 4 && !$this->solidAbove();
	}
	
	/**
	 * @return int
	 */
	public function getLayers() : int{
		$layers = 0;
		if($this->checkShape($this->getSide(0), 1)){
			$layers++;
		}
		if($this->checkShape($this->getSide(0, 2), 2)){
			$layers++;
		}
		if($this->checkShape($this->getSide(0, 3), 3)){
			$layers++;
		}
		if($this->checkShape($this->getSide(0, 4), 4)){
			$layers++;
		}
		return $layers;
	}

	public function checkShape(Vector3 $pos, $layer = 1) : bool{
		for($x = $pos->x - $layer; $x <= $pos->x + $layer; $x++)
			for($z = $pos->z - $layer; $z <= $pos->z + $layer; $z++)
				if(!in_array($this->getLevel()->getBlockIdAt($x, $pos->y, $z), [Block::DIAMOND_BLOCK, Block::IRON_BLOCK, Block::EMERALD_BLOCK, Block::GOLD_BLOCK])) return false;

		return true;
	}

	public function solidAbove() : bool{
		if($this->y === $this->getLevel()->getHighestBlockAt($this->x, $this->z)) return false;
		for($i = $this->y; $i < Level::Y_MAX; $i++){
			if(($block = $this->getLevel()->getBlock(new Vector3($this->x, $i, $this->z)))->isSolid() && !$block->getId() === Block::BEACON) return true;
		}

		return false;
	}

	public function isActive() : bool{
		return !empty($this->getEffects()) && $this->checkShape($this->getSide(0), 1);
	}
	
	/**
	 * @return int[]
	 */
	public function getEffects() : array{
		static $effects = [
			Effect::HASTE, Effect::SPEED, Effect::JUMP, Effect::DAMAGE_RESISTANCE, Effect::STRENGTH, Effect::REGENERATION
		];
		return [$effects[array_rand($effects)], $effects[array_rand($effects)]];
		//return [$this->getPrimaryEffect(), $this->getSecondaryEffect()];
	}
	
	/**
	 * @return int
	 */
	public function getPrimaryEffect() : int{
		return $this->getNBT()->getInt(self::TAG_PRIMARY);
	}
	
	/**
	 * @return int
	 */
	public function getSecondaryEffect() : int{
		return $this->getNBT()->getInt(self::TAG_SECONDARY);
	}
	
	public function getTierEffects(){
	}

	public function getEffectTier(int $tier){
	}
	
	/**
	 * @return bool
	 */
	public function onUpdate() : bool{
		if((Server::getInstance()->getTick() % (20 * 4)) == 0){
			if($this->getLevel() instanceof Level){
				if(!Server::getInstance()->isLevelLoaded($this->getLevel()->getName()) || !$this->getLevel()->isChunkLoaded($this->x >> 4, $this->z >> 4)) return false;
				if(!empty($this->getEffects())){
					$this->applyEffects($this);
				}
			}
		}

		return true;
	}

	public function applyEffects(Vector3 $pos) : void{
		$layers = $this->getLayers();
		/** @var Player $player */
		foreach($this->getLevel()->getCollidingEntities(new AxisAlignedBB($pos->x - (10 + 10 * $layers), 0, $pos->z - (10 + 10 * $layers), $pos->x + (10 + 10 * $layers), Level::Y_MAX, $pos->z + (10 + 10 * $layers))) as $player)
			foreach($this->getEffects() as $effectId){
				if($this->isEffectAvailable($effectId) && $player instanceof Player){
					$player->removeEffect($effectId);
					$eff = new EffectInstance(Effect::getEffect($effectId));
					$effect = $eff->setDuration(20 * 9 + $layers * 2 * 20);
					if($this->getSecondaryEffect() !== 0 && $this->getSecondaryEffect() !== Effect::REGENERATION)
						$effect->setAmplifier(1);
					$player->addEffect($effect);
				}
			}
	}
	
	/**
	 * @return bool
	 */
	public function isEffectAvailable(int $effectId) : bool{
		switch($effectId){
			case Effect::SPEED:
			case Effect::HASTE:
				return $this->getLayers() >= 1 && !$this->solidAbove();
				break;
			case Effect::DAMAGE_RESISTANCE:
			case Effect::JUMP:
				return $this->getLayers() >= 2 && !$this->solidAbove();
				break;
			case Effect::STRENGTH:
				return $this->getLayers() >= 3 && !$this->solidAbove();
				break;
			case Effect::REGENERATION:
				//this case is for secondary effect only
				return $this->getLayers() >= 4 && !$this->solidAbove();
				break;
			default:
				return false;
		}
	}

	/**
	 * Get the object related inventory.
	 * @return BeaconInventory
	 */
	public function getInventory() : BeaconInventory{
		return $this->inventory;
	}
	
	/**
	 * @param CompoundTag $nbt
	 */
	protected function readSaveData(CompoundTag $nbt): void{
		$this->nbt = $nbt;
	}
	
	/**
	 * @param CompoundTag $nbt
	 */
	protected function writeSaveData(CompoundTag $nbt) : void{
		$nbt->setInt(self::TAG_PRIMARY, $this->getNBT()->getInt(self::TAG_PRIMARY));
		$nbt->setInt(self::TAG_SECONDARY, $this->getNBT()->getInt(self::TAG_SECONDARY));
	}
	
}