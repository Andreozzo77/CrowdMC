<?php

declare(strict_types=1);

namespace kenygamer\Core\auction;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class AuctionTask extends Task{
	
	/**
	 * Collect taxes and return inactive auctions.
	 *
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$plugin = Main::getInstance();
		foreach($plugin->auctions as $ID => $auction){
			if($auction->hasExpired() && $auction !== Auction::TYPE_BID){
				$this->expireAuction($auction);
				continue;
			}
			if($plugin->rankCompare($auction->getSeller(), "Nightmare") < 0){
				$tax = ($auction->getPrice() * ($plugin->getConfig()->getNested("auction.daily-tax") / 24) / 100) / 60 / 60;
				if(Main::getInstance()->myMoney($auction->getSeller()) < $tax){
					$player = Server::getInstance()->getPlayerExact($auction->getSeller());
					if($player instanceof Player){
						$player->sendMessage("ah-takedown-nomoney", $plugin->getAuctionID($auction));
					}
					$auction->returnBids(false);
					$this->expireAuction($auction);
					continue;
				}
				Main::getInstance()->reduceMoney($auction->getSeller(), $tax);
			}
			if(time() >= $auction->getMaxBidTime() && $auction->getType() === Auction::TYPE_BID){
				$bids = $auction->getSortedBids();
				if(count($bids) > 0){
					$highestBid = $bids[0];
					list($bidder, $amount) = $highestBid;
					
					$auction->seller = $bidder;
					
					$auction->returnBids(true);
					$plugin->questManager->getQuest("salesmen")->progress($auction->getSeller(), 1);
					LangManager::broadcast("ah-sold-bid", $ID, $auction->getSeller(), $bidder, $amount, count($bids) - 1);
				}else{
					$auction->returnBids(false);
					
					$player = Server::getInstance()->getPlayerExact($auction->getSeller());
					if($player instanceof Player){
						$player->sendMessage("ah-takedown-nobid", Main::getInstance()->getAuctionID($auction));
					}
				}
				$this->expireAuction($auction);
			}
		}
		foreach($plugin->expiredAuctions as $ID => $auction){
			if(time() >= ($auction->getExpireTime() + ($plugin->getConfig()->getNested("auction.collect-days") * 60 * 60))){
				unset($plugin->expiredAuctions[$ID]);
			}else{
				$seller = Server::getInstance()->getPlayerExact($auction->getSeller());
				$item = $auction->getItem();
				if($seller instanceof Player){
					if($seller->getInventory()->canAddItem($item)){
						unset($plugin->expiredAuctions[$ID]);
						$seller->getInventory()->addItem($item);
						$seller->sendMessage("ah-takedown", $plugin->getAuctionID($auction));
					}else{
						$seller->sendMessage("inventory-nospace");
					}
				}
			}
		}
	}
	
	/**
	 * Expire auction (internals).
	 *
	 * @param Auction $auction
	 */
	private function expireAuction(Auction $auction) : void{
		$plugin = Main::getInstance();
		$ID = $plugin->getAuctionID($auction);
		if($ID){
			unset($plugin->auctions[$ID]);
			if(!isset($plugin->expiredAuctions[$ID])){
				$plugin->expiredAuctions[$ID] = $auction;
				
				$plugin->auctionStats->set("auctions-expired", $plugin->auctionStats->get("auctions-expired", 0) + 1);
				LangManager::broadcast("ah-expired", $ID, $auction->getSeller());
			}
		}
	}
	
}