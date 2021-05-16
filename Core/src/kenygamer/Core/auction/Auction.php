<?php

declare(strict_types=1);

namespace kenygamer\Core\auction;

use pocketmine\item\Item;
use pocketmine\Player;

use kenygamer\Core\Main;

class Auction{
	/**
	 * @see AuctionHouse::getAuctionID()
	 *
	 * @var string
	 */
	public $id;
	/** @var string */
	public $seller;
	/** @var Item */
	private $item;
	/** @var int */
	private $price;
	/** @var int */
	private $time;
	/** @var string */
	private $notes;
	/** @var int */
	private $type;
	/** @var int */
	private $bidTime;
	/** @var int[] */
	private $bids;
	
	public const TYPE_BUY = 1;
	public const TYPE_BID = 2;
	
	/**
	 * Auction constructor.
	 *
	 * @param string $seller
	 * @param Item $item
	 * @param int $price
	 * @param int $time
	 * @param string $notes
	 * @param int $type
	 * @param int $bidTime
	 * @param array $bids
	 */
	public function __construct(string $seller, Item $item, int $price, int $time, string $notes, int $type, int $bidTime, array $bids){
		$this->seller = $seller;
		$this->item = $item;
		$this->price = $price;
		$this->time = $time;
		$this->notes = $notes;
		$this->type = $type;
		$this->bidTime = $bidTime;
		$this->bids = $bids;
	}
	
	/**
	 * @return string
	 */
	public function getSeller() : string{
		return $this->seller;
	}
	
	/**
	 * @return Item
	 */
	public function getItem() : Item{
		return clone $this->item;
	}
	
	/**
	 * @return int
	 */
	public function getPrice() : int{
		return $this->price;
	}
	
	/**
	 * @return int Unix timestamp.
	 */
	public function getPublishTime() : int{
		return $this->time;
	}
	
	/**
	 * @return int Unix timestamp.
	 */
	public function getExpireTime() : int{
		return $this->time + (Main::getInstance()->getConfig()->getNested("auction.takedown-days") * 24 * 60 * 60);
	}
	
	/**
	 * @return bool
	 */
	public function hasExpired() : bool{
		return time() >= $this->getExpireTime();
	}
	
	/**
	 * @return string
	 */
	public function getSellerNotes() : string{
		return $this->notes;
	}
	
	/**
	 * @return int
	 */
	public function getType() : int{
		return $this->type;
	}
	
	/**
	 * @return int Unix timestamp
	 */
	public function getMaxBidTime() : int{
		return $this->bidTime;
	}
	
	/**
	 * @return array
	 */
	public function getBids() : array{
		return $this->bids;
	}
	
	/**
	 * Returns bids sorted from highest to lowest.
	 *
	 * @return array
	 */
	public function getSortedBids() : array{
		if($this->type !== self::TYPE_BID || empty($this->bids)){
			return [];
		}
		$bids = $this->bids;
		usort($bids, function(array $A, array $B) : bool{
			return $A < $B;
		});
		return $bids;
	}
	
	/**
	 * Returns the last bidder.
	 *
	 * @return null|string
	 */
	public function getLastBidder() : ?string{
		if(!empty($this->bids) && $this->type === self::TYPE_BID){
			return end($this->bids)[0];
		}
		return null;
	}
	
	/**
	 * Adds a bid to the auction.
	 *
	 * @param string $player
	 * @param int $amount
	 *
	 * @return int -4= auction not biddable, -3= auction expired, -2= already last to bid, -1= bid is too low, 0= no money, 1= OK
	 */
	public function postBid(string $player, int $amount) : int{
		if($this->type !== self::TYPE_BID){
			return -4;
		}
		if(time() >= $this->getMaxBidTime()){
			return -3;
		}
		if($this->getLastBidder() === $player){
			return -2;
		}
		if((empty($this->bids) ? ($amount < $this->price) : false) || $amount <= end($this->bids)[1]){
			return -1;
		}
		$ret = Main::getInstance()->reduceMoney($player, $amount);
		if($ret){
			$this->bids[] = [$player, $amount];
		}
		return $ret ? 1 : 0;
	}
	
	/**
	 * Returns money to bidders.
	 *
	 * @param bool $highestBidder
	 */
	public function returnBids(bool $highestBidder) : void{
		if($this->type !== self::TYPE_BID){
			return;
		}
		$sortedBids = $this->getSortedBids();
		foreach($this->bids as $bid){
			list($bidder, $amount) = $bid;
			
			if($highestBidder && $bidder === $sortedBids[0]){
				continue;
			}
			Main::getInstance()->addMoney($bidder, $amount);
		}
	}
	
}