<?php

declare(strict_types=1);

namespace kenygamer\Core\item;

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\item\Item;
use pocketmine\math\Vector3;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\ByteTag;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\ListTag;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\LevelSoundEventPacket;
use pocketmine\utils\Random;

use kenygamer\Core\entity\FireworksEntity;
use kenygamer\Core\item\Elytra;

class FireworksItem extends Item{
    public const TAG_FIREWORKS = "Fireworks";
	public const TAG_EXPLOSIONS = "Explosions";
	public const TAG_FLIGHT = "Flight";
	
	public $spread = 5.0;

	public function __construct($meta = 0){
		parent::__construct(self::FIREWORKS, $meta, "Fireworks");
	}
	
	public function onClickAir(Player $player, Vector3 $directionVector): bool{
		if($player->getArmorInventory()->getChestplate() instanceof Elytra && !$player->isOnGround()){
			if($player->getGamemode() !== Player::CREATIVE && $player->getGamemode() !== Player::SPECTATOR){
				$this->pop();
			}
			$damage = 0;
			$flight = 1;
			if($this->getNamedTag()->hasTag(self::TAG_FIREWORKS, CompoundTag::class)){
				$fwNBT = $this->getNamedTag()->getCompoundTag(self::TAG_FIREWORKS);
				$flight = $fwNBT->getByte(self::TAG_FLIGHT);
				$explosions = $fwNBT->getListTag(self::TAG_EXPLOSIONS);
				if(count($explosions) > 0){
					$damage = 7;
				}
			}
			$dir = $player->getDirectionVector();
			$player->setMotion($dir->multiply($flight * 1.25));
			$player->getLevel()->broadcastLevelSoundEvent($player->asVector3(), LevelSoundEventPacket::SOUND_LAUNCH);
			if($damage > 0){
				$ev = new EntityDamageEvent($player, EntityDamageEvent::CAUSE_CUSTOM, 7);
				$player->attack($ev);
			}
		}
		return true;
	}

    public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		$random = new Random();
		$yaw = $random->nextBoundedInt(360);
		$pitch = -1 * (float) (90 + ($random->nextFloat() * $this->spread - $this->spread / 2));
		$nbt = Entity::createBaseNBT($blockReplace->add(0.5, 0, 0.5), null, $yaw, $pitch);
		/** @var CompoundTag $tags */
		$tags = $this->getNamedTagEntry("Fireworks");
		if ($tags !== null){
			$nbt->setTag($tags);
		}

        $rocket = new FireworksEntity($player->getLevel(), $nbt, $player, $this, $random);
        $player->getLevel()->addEntity($rocket);

		if ($rocket instanceof Entity){
			if ($player->isSurvival()){
				--$this->count;
			}
			$rocket->spawnToAll();
			return true;
		}

		return false;
	}

	public static function ToNbt(FireworksData $data) : CompoundTag{
		$value = [];
		$root = new CompoundTag();
		foreach ($data->explosions as $explosion){
			$tag = new CompoundTag();
			$tag->setByteArray("FireworkColor", strval($explosion->fireworkColor[0])); //TODO figure out calculation
			$tag->setByteArray("FireworkFade", strval($explosion->fireworkFade[0])); //TODO figure out calculation
			$tag->setByte("FireworkFlicker", ($explosion->fireworkFlicker ? 1 : 0));
			$tag->setByte("FireworkTrail", ($explosion->fireworkTrail ? 1 : 0));
			$tag->setByte("FireworkType", $explosion->fireworkType);
			$value[] = $tag;
		}

		$explosions = new ListTag("Explosions", $value, NBT::TAG_Compound);
		$root->setTag(new CompoundTag("Fireworks", [
		   $explosions, new ByteTag("Flight", $data->flight)
		]));
		return $root;
	}

}

class FireworksData{
	/** @var int */
	public $flight = 1;
	/** @var FireworksExplosion[] */
	public $explosions = [];
}

class FireworksExplosion{
	/** @var int[] count keys = 3 */ //TODO figure out calculation
	public $fireworkColor = [];
	/** @var int[] count keys = 3 */ //TODO figure out calculation
	public $fireworkFade = [];
	/** @var bool */
	public $fireworkFlicker = false;
	/** @var bool */
	public $fireworkTrail = false;
	/** @var int */
	public $fireworkType = -1;
}