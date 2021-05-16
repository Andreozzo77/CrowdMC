<?php

declare(strict_types=1);

namespace kenygamer\Core\network;

use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\network\mcpe\NetworkSession;
use pocketmine\network\mcpe\protocol\DataPacket;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;

class InventoryContentPacket extends DataPacket{
	public const NETWORK_ID = ProtocolInfo::INVENTORY_CONTENT_PACKET;

	/** @var int */
	public $windowId;
	/** @var ItemStackWrapper[] */
	public $items = [];

	protected function decodePayload(){
		$this->windowId = $this->getUnsignedVarInt();
		$count = $this->getUnsignedVarInt();
		for($i = 0; $i < $count; ++$i){
			try{
				$item = ItemStackWrapper::read($this);
			}catch(\InvalidArgumentException $e){
				$item = ItemStackWrapper::legacy(ItemFactory::get(Item::AIR));
			}
			$this->items[] = $items;
		}
	}

	protected function encodePayload(){
		$this->putUnsignedVarInt($this->windowId);
		$this->putUnsignedVarInt(count($this->items));
		foreach($this->items as $item){
			try{
				$item->write($this);
			}catch(\InvalidArgumentException $e){

				$wrapper = ItemStackWrapper::legacy(ItemFactory::get(Item::AIR));
				$wrapper->write($this);
			}
		}
	}

	public function handle(NetworkSession $session) : bool{
		return true;
		//return $session->handleInventoryContent($this);
	}
}