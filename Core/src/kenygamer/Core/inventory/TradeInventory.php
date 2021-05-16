<?php

declare(strict_types=1);

namespace kenygamer\Core\inventory;

use pocketmine\inventory\BaseInventory;
use pocketmine\inventory\ContainerInventory;
use pocketmine\math\Vector3;
use pocketmine\nbt\NetworkLittleEndianNBTStream;
use pocketmine\network\mcpe\protocol\types\WindowTypes;
use pocketmine\network\mcpe\protocol\UpdateTradePacket;
use pocketmine\Player;

use kenygamer\Core\listener\MiscListener2;

class TradeInventory extends ContainerInventory{
	/** @var int */
	private $traderEid;
	
	public function __construct(int $traderEid){
		$this->traderEid = $traderEid;
		parent::__construct(new Vector3());
	}

	public function getName(): string{
		return "Trade Inventory";
	}

	public function getDefaultSize(): int{
		return 3;
	}

	public function getNetworkType(): int{
		return WindowTypes::TRADING;
	}

	public function onOpen(Player $who): void{
		BaseInventory::onOpen($who);

		$pk = new UpdateTradePacket();
		$pk->displayName = "Trade";
		$pk->windowId = $who->getWindowId($this);
		$pk->isWilling = false;
		$pk->isV2Trading = false;
		$pk->tradeTier = 1;
		$pk->playerEid = $who->getId();
		$pk->traderEid = $this->traderEid;
		$pk->offers = (new NetworkLittleEndianNBTStream())->write(MiscListener2::$tradeOffers);
		$who->dataPacket($pk);
		MiscListener2::$trading[$who->getName()] = $pk->windowId;
	}

	public function onClose(Player $who): void{
		BaseInventory::onClose($who);
		unset(MiscListener2::$trading[$who->getName()]);
	}
	
}