<?php

namespace xenialdan\BossBar;

use pocketmine\entity\Attribute;
use pocketmine\entity\AttributeMap;
use pocketmine\entity\DataPropertyManager;
use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\BossEventPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorDataPacket;
use pocketmine\network\mcpe\protocol\UpdateAttributesPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\MainLogger;

class BossBar{
    protected const NETWORK_ID = Entity::SLIME;

    /** @var Player[] */
    private $players = [];
    /** @var string */
    private $title = "";
    /** @var string */
    private $subTitle = "";
    /** @var int|null */
    public $entityId = null;
    /** @var AttributeMap */
    private $attributeMap;
    /** @var DataPropertyManager */
    protected $propertyManager;

    public function __construct(){
        $this->entityId = Entity::$entityCount++;
        $this->attributeMap = new AttributeMap();
        $this->attributeMap->addAttribute(Attribute::getAttribute(Attribute::HEALTH)->setMaxValue(100.0)->setMinValue(0.0)->setDefaultValue(100.0));
        $this->propertyManager = new DataPropertyManager();
        $this->propertyManager->setLong(Entity::DATA_FLAGS, 0
            ^ 1 << Entity::DATA_FLAG_SILENT
            ^ 1 << Entity::DATA_FLAG_INVISIBLE
            ^ 1 << Entity::DATA_FLAG_NO_AI
            ^ 1 << Entity::DATA_FLAG_FIRE_IMMUNE);
        $this->propertyManager->setShort(Entity::DATA_MAX_AIR, 400);
        $this->propertyManager->setString(Entity::DATA_NAMETAG, $this->getFullTitle());
        $this->propertyManager->setLong(Entity::DATA_LEAD_HOLDER_EID, -1);
        $this->propertyManager->setFloat(Entity::DATA_SCALE, 0);
        $this->propertyManager->setFloat(Entity::DATA_BOUNDING_BOX_WIDTH, 0.0);
        $this->propertyManager->setFloat(Entity::DATA_BOUNDING_BOX_HEIGHT, 0.0);
    }

    /**
     * @return Player[]
     */
    public function getPlayers() : array{
        return $this->players;
    }

    /**
     * @param Player[] $players
     * @return BossBar
     */
    public function addPlayers(array $players) : BossBar{
        foreach($players as $player){
            $this->addPlayer($player);
        };
        return $this;
    }

    /**
     * @param Player $player
     * @return BossBar
     */
    public function addPlayer(Player $player) : BossBar{
        if(isset($this->players[$player->getId()])){
			return $this;
		}
        if(!($this->getEntity() instanceof Player)){
			$this->sendSpawnPacket([$player]);
		}
        $this->sendBossPacket([$player]);
        $this->players[$player->getId()] = $player;
        return $this;
    }

    /**
     * Removes a single player from this bar.
     * Use @see BossBar::hideFrom() when just removing temporarily to save some performance / bandwidth
     * @param Player $player
     * @return BossBar
     */
    public function removePlayer(Player $player): BossBar{
        if(!isset($this->players[$player->getId()])){
            MainLogger::getLogger()->debug("Removed player that was not added to the boss bar (" . $this . ")");
            return $this;
        }
        $this->sendRemoveBossPacket([$player]);
        unset($this->players[$player->getId()]);
        return $this;
    }

    /**
     * @param Player[] $players
     * @return BossBar
     */
    public function removePlayers(array $players) : BossBar{
        foreach($players as $player){
            $this->removePlayer($player);
        };
        return $this;
    }

    /**
     * Removes all players from this bar
     * @return BossBar
     */
    public function removeAllPlayers(): BossBar{
        foreach($this->getPlayers() as $player){
			$this->removePlayer($player);
		}
        return $this;
    }

    /**
     * The text above the bar
     * @return string
     */
    public function getTitle(): string{
        return $this->title;
    }

    /**
     * Text above the bar. Can be empty. Should be single-line
     * @param string $title
     * @return BossBar
     */
    public function setTitle(string $title = "") : BossBar{
        $this->title = $title;
        $this->sendEntityDataPacket($this->getPlayers());
        $this->sendBossTextPacket($this->getPlayers());
        return $this;
    }

    /**
     * @return string
     */
    public function getSubTitle() : string{
        return $this->subTitle;
    }

    /**
     * Optional text below the bar. Can be empty
     * @param string $subTitle
     * @return BossBar
     */
    public function setSubTitle(string $subTitle = "") : BossBar{
        $this->subTitle = $subTitle;
        $this->sendEntityDataPacket($this->getPlayers());
        $this->sendBossTextPacket($this->getPlayers());
        return $this;
    }

    /**
     * The full title as a combination of the title and its subtitle. Automatically fixes encoding issues caused by newline characters
     * @return string
     */
    public function getFullTitle() : string{
        $text = $this->title;
        if (!empty($this->subTitle)) {
            $text .= PHP_EOL . PHP_EOL . $this->subTitle;
        }
        return mb_convert_encoding($text, 'UTF-8');
    }

    /**
     * @param float $percentage 0-1
     * @return BossBar
     */
    public function setPercentage(float $percentage) : BossBar{
        $percentage = (float) max(0.01, $percentage);
        $this->attributeMap->getAttribute(Attribute::HEALTH)->setValue($percentage* $this->attributeMap->getAttribute(Attribute::HEALTH)->getMaxValue(), true, true);
        $this->sendAttributesPacket($this->getPlayers());
        $this->sendBossHealthPacket($this->getPlayers());
        return $this;
    }

    /**
     * @return float
     */
    public function getPercentage() : float{
        return $this->attributeMap->getAttribute(Attribute::HEALTH)->getValue()/100;
    }

    /**
     * TODO: Only registered players validation
     * Hides the bar from the specified players without removing it.
     * Useful when saving some bandwidth or when you'd like to keep the entity
     * @param Player[] $players
     */
    public function hideFrom(array $players) : void{
        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_HIDE;
        Server::getInstance()->broadcastPacket($players, $pk);
    }

    /**
     * Hides the bar from all registered players
     */
    public function hideFromAll() : void{
        $this->hideFrom($this->getPlayers());
    }

    /**
     * TODO: Only registered players validation
     * Displays the bar to the specified players
     * @param Player[] $players
     */
    public function showTo(array $players) : void{
        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_SHOW;
        Server::getInstance()->broadcastPacket($players, $this->addDefaults($pk));
    }

    /**
     * Displays the bar to all registered players
     */
    public function showToAll() : void{
        $this->showTo($this->getPlayers());
    }

    /**
     * @return null|Entity
     */
    public function getEntity() : ?Entity{
        return Server::getInstance()->findEntity($this->entityId);
    }

    /**
     * @param Player[] $players
     */
    protected function sendSpawnPacket(array $players) : void{
        $pk = new AddActorPacket();
        $pk->entityRuntimeId = $this->entityId;
        $pk->type = AddActorPacket::LEGACY_ID_MAP_BC[$this->getEntity() instanceof Entity ? $this->getEntity()::NETWORK_ID : static::NETWORK_ID];
        $pk->attributes = $this->attributeMap->getAll();
        $pk->metadata = $this->propertyManager->getAll();
        foreach($players as $player){
            $pkc = clone $pk;
            $pkc->position = $player->asVector3()->subtract(0, 28);
            $player->dataPacket($pkc);
        }
    }

    /**
     * @param Player[] $players
     */
    protected function sendBossPacket(array $players) : void{
        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_SHOW;
        $pk->title = $this->getFullTitle();
        $pk->healthPercent = $this->getPercentage();
        Server::getInstance()->broadcastPacket($players, $this->addDefaults($pk));
    }

    /**
     * @param Player[] $players
     */
    protected function sendRemoveBossPacket(array $players) : void{
        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_HIDE;
        Server::getInstance()->broadcastPacket($players, $pk);
    }

    /**
     * @param Player[] $players
     */
    protected function sendBossTextPacket(array $players) : void{
        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_TITLE;
        $pk->title = $this->getFullTitle();
        Server::getInstance()->broadcastPacket($players, $pk);
    }

    /**
     * @param Player[] $players
     */
    protected function sendAttributesPacket(array $players) : void{
        $pk = new UpdateAttributesPacket();
        $pk->entityRuntimeId = $this->entityId;
        $pk->entries = $this->attributeMap->needSend();
        Server::getInstance()->broadcastPacket($players, $pk);
    }

    /**
     * @param Player[] $players
     */
    protected function sendEntityDataPacket(array $players) : void{
        $this->propertyManager->setString(Entity::DATA_NAMETAG, $this->getFullTitle());
        $pk = new SetActorDataPacket();
        $pk->metadata = $this->propertyManager->getDirty();
        $pk->entityRuntimeId = $this->entityId;
        Server::getInstance()->broadcastPacket($players, $pk);
        $this->propertyManager->clearDirtyProperties();
    }

    /**
     * @param Player[] $players
     */
    protected function sendBossHealthPacket(array $players): void
    {
        $pk = new BossEventPacket();
        $pk->bossEid = $this->entityId;
        $pk->eventType = BossEventPacket::TYPE_HEALTH_PERCENT;
        $pk->healthPercent = $this->getPercentage();
        Server::getInstance()->broadcastPacket($players, $pk);
    }

    private function addDefaults(BossEventPacket $pk):BossEventPacket{
        $pk->title = $this->getFullTitle();
        $pk->healthPercent = $this->getPercentage();
        $pk->unknownShort = 1;
        $pk->color = 0;//Does not function anyways
        $pk->overlay = 0;//Neither. Typical for Mojang: Copy-pasted from Java edition
        return $pk;
	}
	
    /**
     * @return string
     */
    public function __toString() : string{
        return __CLASS__ . " ID: $this->entityId, Players: " . count($this->players) . ", Title: \"$this->title\", Subtitle: \"$this->subTitle\", Percentage: \"".$this->getPercentage()."\"";
    }

}