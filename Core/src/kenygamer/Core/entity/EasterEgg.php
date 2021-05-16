<?php

declare(strict_types=1);

namespace kenygamer\Core\entity;

use pocketmine\entity\Entity;
use pocketmine\entity\Skin;
use pocketmine\network\mcpe\protocol\types\SkinData;
use pocketmine\network\mcpe\protocol\types\SkinAdapterSingleton;
use pocketmine\network\mcpe\protocol\PlayerListPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\types\PlayerListEntry;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\utils\UUID;
use pocketmine\item\Item;

use kenygamer\Core\Main;
use kenygamer\Core\listener\MiscListener;
use kenygamer\Core\util\ItemUtils;

class EasterEgg extends Entity{
	public const NETWORK_ID = self::PLAYER;
	
	/** @var SkinData */
	private $skin;
	/** @var int */
	public $eggId = -1;
	
	protected $uuid = null;
	public $width = 1.0;
	public $height = 1.0;
	public $gravity = 1.0;

    /**
     * @param Level $level
     * @param CompoundTag $nbt
     */
	public function __construct(Level $level, CompoundTag $nbt){
		parent::__construct($level, $nbt);
		$this->uuid = UUID::fromRandom();
		
		$types = [];
		foreach(Main::getInstance()->models as $name => $model){
			if(stripos($name, "EasterEgg") !== false){
				$design = Main::getInstance()->designs[$name] ?? "";
				if($design !== ""){
					$types[] = [$model, $design];
				}
			}
		}
		if(!empty($types)){
			$type = $types[array_rand($types)];
			$oldSkin = new Skin($this->uuid->toString(), $type[1], "", $type[0]["minecraft:geometry"][0]["description"]["identifier"], json_encode($type[0]));
			$this->skin = SkinAdapterSingleton::get()->toSkinData($oldSkin);
		}
		$this->setScale(1.3);
	}
	
	public function initEntity() : void{
		if(!$this->namedtag->hasTag("EggId", IntTag::class)){
			$this->eggId = count(Main::getInstance()->easterEggs->getAll()) + 1; //prevent numeric indexing
		}else{
			$this->eggId = $this->namedtag->getInt("EggId");
		}
		parent::initEntity();
	}
	
	public function canCollideWith(Entity $entity) : bool{
		return false;
	}
	
	public function saveNBT(): void{
		parent::saveNBT();
		$this->namedtag->setInt("EggId", $this->eggId);
	}
	
	public function attack(EntityDamageEvent $source): void{
		$source->setCancelled();
		if($source instanceof EntityDamageByEntityEvent && ($player = $source->getDamager()) instanceof Player){
			$cfg = Main::getInstance()->easterEggs;
			if($player->getGamemode() === Player::CREATIVE && $player->isOp()){
				if(!$this->closed){
					$this->flagForDespawn();
				}
				$cfg->remove(strval($this->eggId));
				return;
			}
			if($cfg->get(strval($this->eggId)) !== false){
				if(!$cfg->getNested($this->eggId . "." . $player->getName())){
					$eggs = 0;
					foreach($cfg->getAll() as $egg => $collected){
						if(in_array($player->getName(), array_keys($collected))){
							$eggs++;
						}
					}
					if(++$eggs === count($cfg->getAll())){
						if(ItemUtils::addItems($player->getInventory(), ItemUtils::get("hestia_gem"))){
							LangManager::broadcast("easteregg-broadcast-basket", $player->getName());
							$cfg->setNested($this->eggId . "." . $player->getName(), time());
							$this->despawnFrom($player);
						}else{
							LangManager::send("inventory-nospace", $player);
						}
					}else{
						LangManager::broadcast("easteregg-broadcast-found", $player->getName());
						LangManager::send("easteregg-found", $player, $eggs, count($cfg->getAll()));
						Main::getInstance()->addTokens($player, 1);
						$cfg->setNested($this->eggId . "." . $player->getName(), time());
						$this->despawnFrom($player);
					}
				}
			}else{
				if(!$this->closed){
					$this->flagForDespawn();
				}
			}
		}
	}

	/**
	 * @param Player $player
	 */
	protected function sendSpawnPacket(Player $player) : void{
		if(Main::getInstance()->easterEggs->getNested($this->eggId . "." . $player->getName()) || $this->skin === null){ //|| MiscListener::getInstance()->isMCPE1460[$player->getUniqueId()->toString()]
			return;
		}
		$pk = new PlayerListPacket();
		$pk->type = PlayerListPacket::TYPE_ADD;
		$pk->entries[] = PlayerListEntry::createAdditionEntry($this->uuid, $this->id, "EasterEgg-" . $this->id, $this->skin);
		$player->sendDataPacket($pk);

		$pk = new AddPlayerPacket();
		$pk->uuid = $this->uuid;
		$pk->item = Item::get(Item::AIR);
		$pk->motion = $this->getMotion();
		$pk->position = $this->asVector3();
		$pk->entityRuntimeId = $this->getId();
		$pk->metadata = $this->propertyManager->getAll();
		$pk->username = "EasterEgg-" . $this->id;
		$player->sendDataPacket($pk);

		$pk = new PlayerListPacket();
		$pk->type = $pk::TYPE_REMOVE;
		$pk->entries = [PlayerListEntry::createRemovalEntry($this->uuid)];
		$player->sendDataPacket($pk);
	}

}