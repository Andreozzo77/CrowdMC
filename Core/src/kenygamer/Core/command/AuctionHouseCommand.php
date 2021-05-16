<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\block\Block;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\Item;
use pocketmine\item\Durable;
use pocketmine\item\ItemFactory;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\auction\Auction;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use CustomEnchants\CustomEnchants\CustomEnchants;

use function array_merge;
use function max;
use function array_keys;
use function is_null;
use function is_string;
use function is_float;
use function is_numeric;
use function ceil;
use function count;
use function array_splice;
use function number_format;
use function strval;
use function str_replace;
use function strpos;
use function strlen;

class AuctionHouseCommand extends BaseCommand{
	/** @var int[] */
	private $buy = [];
	/** @var int[] */
	private $cooldown = [];
	
	public function __construct(){
		parent::__construct(
			"auctionhouse",
			"AuctionHouse Command",
			"/ah [sell/list/info/buy]",
			["ah"],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	/**
	 * @param Item $item
	 * @return string[]
	 */
	private function getItemDescription(Item $item) : array{
		$desc[] = ItemUtils::getDescription($item);
		if($item instanceof Durable){
			$use = round($item->getDamage() / $item->getMaxDurability() * 100);
			if($use >= 100){
				$desc[] = TextFormat::RESET . TextFormat::RED . " ({$use}%%)" . TextFormat::RESET;
			}elseif($use >= 75){
				$desc[] = TextFormat::RESET . TextFormat::YELLOW . " ({$use}%%)" . TextFormat::RESET;
			}elseif($use >= 25 && $use < 75){
				$desc[] = TextFormat::RESET . TextFormat::YELLOW . " ({$use}%%)" . TextFormat::RESET;
			}elseif($use > 0 && $use < 25){
				$desc[] = TextFormat::RESET . TextFormat::GREEN . " ({$use}%%)" . TextFormat::RESET;
			}else{
				$desc[] = TextFormat::RESET . TextFormat::GREEN . " ({$use}%%)" . TextFormat::RESET;
			}
		}else{
			$desc[] = "";
		}
		$ench = "";
		$enchants = $item->getEnchantments();
		$count = count($enchants);
		foreach($enchants as $i => $enchantment){
			$ench .= ($i !== $count - 1 ? ", " : "") . $enchantment->getType()->getName() . " " . $this->getPlugin()->getPlugin("CustomEnchants")->getRomanNumber($enchantment->getLevel());
		}
		$desc[] = "Enchants (" . $count . "): " . $ench;
		return $desc;
	}
	
	/**
	 * @param Player $player
	 * @param string $title
	 * @param string $msg
	 * @param array $button
	 * @param array $button2
	 */
	private function nextWindow(Player $player, string $title, string $msg, array $button, array $button2 = []) : void{
		if(!$player->isOnline()){
		    //Close session
			return;
		}
		$form = new ModalForm(function(Player $player, $data) use($button, $button2){
			if(is_bool($data) && $data){
				$player->chat($button[1]);
			}else{
				if(empty($button2)){
					$this->homeWindow($player);
				}else{
					$player->chat($button2[1]);
				}
			}
		});
		$form->setTitle($title);
		$form->setContent($msg);
		$form->setButton1($button[0]);
		$form->setButton2(empty($button2) ? LangManager::translate("ah-mainmenu", $player) : $button2[0]);
		$player->sendForm($form);
	}
	
	/**
	 * @param Player $player
	 */
	private function homeWindow(Player $player) : void{
		unset($this->buy[$player->getName()]);
		if(!$player->isOnline()){
		    //Close session
			return;
		}
		$form = new SimpleForm(function(Player $player, $opt){
			if(is_string($opt)){
				$player->chat("/ah " . $opt);
			}else{
				//Close session
			}
		});
		$form->setTitle(LangManager::translate("ah-title", $player));
		$form->setContent(LangManager::translate("ah-content", $player, $this->getPlugin()->auctionStats->get("auctions-expired", 0), number_format($this->getPlugin()->auctionStats->get("auctions-retired", 0))));
		$form->addButton(LangManager::translate("ah-1", $player), -1, "", "sell");
		$form->addButton(LangManager::translate("ah-2", $player), -1, "", "list");
		$form->addButton(LangManager::translate("ah-3", $player), -1, "", "info");
		$form->addButton(LangManager::translate("ah-4", $player), -1, "", "manage");
		$form->sendToPlayer($player);
	}
	
	protected function onExecute($sender, array $args) : bool{
		//Open session
		$action = $args[0] ?? null;
		switch($action){
			case "sell":
			    $item = $sender->getInventory()->getItemInHand();
			    $desc = $this->getItemDescription($item);
			    if($item->isNull()){
			    	$this->nextWindow($sender, "AH Sell", LangManager::translate("hold-item", $sender), [LangManager::translate("try-again", $sender), "/ah sell"]);
			    	break;
			    }
				$cost = $this->getPlugin()->getConfig()->getNested("auction.cost");
			    $form = new CustomForm(function(Player $player, $data) use($item, $cost){
			    	if(is_null($data) || !isset($data[1])){
			    		$this->homeWindow($player);
			    		return;
			    	}
			    	$price = (int) $data[1];
			    	$notes = $data[2] ?? "";
			    	$isBid = $data["type"] === 1;
			    	if($price <= 0){
			    		$this->nextWindow($player, "AH Sell", LangManager::translate("ah-sell-price", $player), [LangManager::translate("try-again", $player), "/ah sell"]);
			    		return;
			    	}
			    	if(isset($this->cooldown[$player->getName()]) && time() < $this->cooldown[$player->getName()]){
			    		$this->nextWindow($player, "AH Sell", LangManager::translate("in-cooldown", $player), [LangManager::translate("try-again", $player), "/ah sell"]);
			    	}elseif(strlen($notes) > 100){
			    		$this->nextWindow($player, "AH Sell", LangManager::translate("ah-sell-desc", $player), [LangManager::translate("try-again", $player), "/ah sell"]);
			    	}elseif($this->getPlugin()->rankCompare($player, "Nightmare") >= 0 && !$player->reduceMoney($cost)){
			    		$this->nextWindow($player, "AH Sell", LangManager::translate("ah-sell-nomoney", $player, $cost), [LangManager::translate("try-again", $player), "/ah sell"]);
			    	}elseif($isBid && ($maxBidTime = $this->getPlugin()->parseTime($data["maxBidTime"])) < 1){
			    		$this->nextWindow($player, "AH Sell", LangManager::translate("ah-sell-bidtime", $player), [LangManager::translate("try-again", $player), "/ah sell"]);
			    	}else{
			    		$i = $player->getInventory()->getItemInHand();
			    		if(!$i->equalsExact($item)){
			    			$this->nextWindow($player, "AH Sell", LangManager::translate("hold-item", $player), [LangManager::translate("try-again", $player), "/ah sell"]);
			    			return;
			    		}
			    		$this->cooldown[$player->getName()] = time() + ($this->getPlugin()->getConfig()->getNested("auction.cooldown") * 60);
			    		$player->getInventory()->setItemInHand(ItemFactory::get(Item::AIR));
						
						$lastID = !empty($this->getPlugin()->auctions) ? max(array_keys($this->getPlugin()->auctions)) : 0;
						$ID = strval(empty($this->getPlugin()->auctions) ? 1 : ++$lastID);
						$this->getPlugin()->auctions[$ID] = new Auction($player->getName(), $i, $price, time(), $notes, $isBid ? Auction::TYPE_BID : Auction::TYPE_BUY, $isBid ? (time() + $maxBidTime) : -1, []);
						$this->getPlugin()->auctions[$ID]->id = $ID;
		
			    		LangManager::broadcast("ah-sell-broadcast", $player->getName(), $ID);
			    		$this->nextWindow($player, "AH Sell", LangManager::translate("ah-sell", $player, $ID), [LangManager::translate("continue", $player), "/ah list " . $player->getName()]);
			    	}
			    });
			    $form->setTitle("AH Sell");
			    $form->addLabel(LangManager::translate("item", $sender) . ": " .  TextFormat::clean(explode("\n", $desc[0])[0]));
			    $form->addInput(LangManager::translate("price", $sender) . ": ", "$");
			    $form->addInput(LangManager::translate("details", $sender) . ": ");
			    $form->addDropdown(LangManager::translate("type", $sender), ["Buy", "Bid"], 0, "type");
			    $form->addInput(LangManager::translate("ah-sell-bidtime2", $sender), "Hours:Minutes:Seconds", null, "maxBidTime");
			    if($this->getPlugin()->rankCompare($sender, "Nightmare") >= 0){
			    	$form->addLabel(LangManager::translate("ah-sell-notax", $sender));
			    }else{
			    	$form->addLabel(LangManager::translate("ah-sell-info", $sender, $cost, $this->getPlugin()->getConfig()->getNested("auction.daily-tax")));
			    }
			    $form->addLabel(LangManager::translate("ah-sell-info-2", $sender));
				$sender->sendForm($form);
				break;
			case "list":
			    if(isset($args[1])){
			    	if($args[1] === "search"){
			    		$form = new CustomForm(function(Player $player, $data){
			    			if(is_null($data) || !isset($data[1])){
			    				$this->homeWindow($player);
			    				return;
			    			}
			    			$username = $data[1];
			    			$player->chat("/ah list \"" . $username . "\" 1");
			    		});
			    		$form->setTitle("AH List");
			    		$form->addLabel(LangManager::translate("ah-list-desc", $sender));
			    		$form->addInput(LangManager::translate("ah-list-seller", $sender));
						$sender->sendForm($form);
			    		break;
			    	}elseif(is_numeric($args[1])){
			    		$page = (int) $args[1];
			    	}else{
			    		$username = $args[1];
			    		$page = isset($args[2]) ? (int) $args[2] : 1;
			    	}
			    }else{
			    	$page = 1;
			    }
			    $found = [];
			    $aucs = array_combine(array_keys($this->getPlugin()->auctions), array_reverse(array_values($this->getPlugin()->auctions)));
			    foreach($aucs as $ID => $auction){
			    	if(isset($username)){
			    		if(strcasecmp($auction->getSeller(), $username) === 0){
			    			$found[$ID] = $auction;
			    		}
			    	}else{
			    		$found[$ID] = $auction;
			    	}
			    }
			    if(!$found){
			    	$this->nextWindow($sender, "AH List", LangManager::translate((isset($username) ? "ah-list-seller-none" : "ah-list-page-none"), $sender), [LangManager::translate("continue", $sender), "/ah list"]);
			    }else{
					$limit = $this->getPlugin()->getConfig()->getNested("auction.per-page-limit");
			    	$pages = ceil(count($found) / $limit);
			    	$end_offset = ($page - 1) * $limit;
			    	$auctions = array_slice($found, $end_offset, $limit);
			    	$form = new SimpleForm(function(Player $player, $input) use($args, $page){
			    		if(is_null($input)){
			    			$this->homeWindow($player);
			    			return;
			    		}
			    		//args[1] page number, args[2] null -> List all auctions on the specified page
			    		//args[1] username, args[2] page number or null -> List auctions by the given player on the specified page
			    		//args[1] search, args[2] null -> List auctions by the given player
						
			    		switch($input){
			    			case "next_page":
			    			    $player->chat("/ah list " . (isset($args[1]) && is_numeric($args[1]) ? ($args[1] + 1) : (isset($args[2]) && is_numeric($args[2]) ? ($args[1] . " " . ($args[2] + 1)) : 2)));
			    			    break;
			    			case "previous_page":
			    			    $player->chat("/ah list " . (isset($args[1]) && is_numeric($args[1]) ? ($args[1] - 1) : (isset($args[2]) && is_numeric($args[2]) ? ($args[1] . " " . ($args[2] - 1)) : 1)));
			    			    break;
			    			case "search":
			    			    $player->chat("/ah list search");
			    			    break;
			    			default:
			    			    if(!is_numeric($input) || !isset($this->getPlugin()->auctions[$input])){
			    			    	$this->nextWindow($player, "AH List", LangManager::translate("ah-notfound", $player), [LangManager::translate("goback", $player), "/ah " . implode("", $args)]);
			    			    }else{
			    			    	$player->chat("/ah info " . $input . " " . (isset($username) ? ($username . " " . $page) : $page));
			    			    }
			    		}
			    	});
			    	$form->setTitle(TextFormat::BLUE . TextFormat::BOLD . "AH List");
			    	if(isset($username) && $username === $sender->getName()){
			    		$content = LangManager::translate("ah-list-me", $sender, count($found), $page, $pages);
			    	}else{
			    	    $content = LangManager::translate("ah-list-all", $sender, count($found), $page, $pages);
			    	}
			    	$content .= "\n" . LangManager::translate("ah-list-sorting", $sender);
			    	$form->setContent($content);
			    	if(!isset($username)){
			    		$form->addButton(LangManager::translate("ah-list-search", $sender), -1, "", "search");
			    	}
			    	foreach($auctions as $key => $auction){
			    		$item = $auction->getItem();
			    		$desc = $this->getItemDescription($item);
			    		$ID = (string) $this->getPlugin()->getAuctionID($auction);
			    		$path = $this->getPlugin()->getTexturePath($item);
			    		if($path !== ""){
			    			$form->addButton(TextFormat::BLUE . TextFormat::clean(explode("\n", $desc[0])[0]) . " (#" . $ID . ")", 0, $path, $ID);
			    		}else{
			    			$form->addButton(TextFormat::BLUE . TextFormat::clean(explode("\n", $desc[0])[0]) . " (#" . $ID . ")", -1, "", $ID);
			    		}
			    	}
			    	if($page - 1 !== 0){
			    		$form->addButton(LangManager::translate("previous-page", $sender), -1, "", "previous_page");
			    	}
			    	if($page < $pages){
			    		$form->addButton(LangManager::translate("next-page", $sender), -1, "", "next_page");
			    	}
					$sender->sendForm($form);
			    }
                break;
			case "info":
			    if(!isset($args[1])){
			    	$form = new CustomForm(function(Player $player, $data){
			    		if(is_null($data) || !isset($data[1])){
			    			$this->homeWindow($player);
			    			return;
			    		}
			    		$auctionID = str_replace("#", "", $data[1]);
			    		$player->chat("/ah info " . $auctionID);
			    	});
			    	$form->setTitle(TextFormat::BLUE . TextFormat::BOLD . "AH Info");
			    	$form->addLabel(LangManager::translate("ah-info-desc", $sender));
			    	$form->addInput(LangManager::translate("ah-auctionid", $sender), "#");
					$sender->sendForm($form);
			    	break;
			    }else{
			    	$auctionID = str_replace("#", "", $args[1]);
			        if(!is_numeric($auctionID) || !isset($this->getPlugin()->auctions[$auctionID])){
			        	$this->nextWindow($sender, "AH Info", LangManager::translate("ah-notfound", $sender), [LangManager::translate("continue", $sender), "/ah info"]);
			        }else{
			        	$auction = $this->getPlugin()->getAuction($auctionID);
			        	$price = $auction->getPrice();
			        	$desc = $this->getItemDescription($auction->getItem());
			        	$lore = empty($auction->getItem()->getLore()) ? "N/A" : TextFormat::clean(implode("/ ", $auction->getItem()->getLore()));
			        	
			        	$enchants = strpos($desc[2], "Enchants (0):") !== false ? "N/A" : $desc[2];
			        	$enchants = str_replace(["Enchants (", "):"], "", $enchants);
			        	$desc[0] = TextFormat::clean(explode("\n", $desc[0])[0]);
			        	
			            $page = isset($args[2]) ? (is_numeric($args[2]) ? $args[2] : ($args[3] ?? 1)) : 1;
			            $seller = isset($args[3]) ? ($args[2] ?? 1) : null;
			            $data = ($seller !== null ? ($seller . " " . $page) : $page);
			        	
			        	$isBid = $auction->getType() === Auction::TYPE_BID;
			        	$this->nextWindow(
			        	    $sender,
			        	    "AH Info #" . $auctionID,
			        	    LangManager::translate("ah-info", $sender, $auction->getSeller(), $desc[0], $desc[1], $enchants, LangManager::translate($isBid ? "ah-info-minbid" : "price", $sender), $price, $auction->getSellerNotes(), $lore),
			        	    [
			        	        LangManager::translate(mb_strtolower($sender->getName()) === mb_strtolower($auction->getSeller()) ? "ah-info-retireauction" : ($isBid ? "ah-info-postbid" : "ah-info-buyauction"), $sender), "/ah manage " . $auctionID
			        	    ],
							isset($args[2]) ? [LangManager::translate("ah-info-backpage", $sender, $page), "/ah list " . $data] : []
			       		);
			    	}
			    }
			    break;
			case "manage":
			    if(!isset($args[1])){
			    	$form = new CustomForm(function(Player $player, $data){
			    		if(is_null($data) || !isset($data[1])){
			    			$this->homeWindow($player);
			    			return;
			    		}
			    		$auctionID = $data[1];
			    		$player->chat("/ah manage " . $auctionID);
			    	});
			    	$form->setTitle("AH Manage");
			    	$form->addLabel(LangManager::translate("ah-manage", $sender));
			    	$form->addInput(LangManager::translate("ah-auctionid", $sender), "#");
					$sender->sendForm($form);
			    }else{
			    	$auctionID = str_replace("#", "", $args[1]);
			    	if(!is_numeric($auctionID) || !isset($this->getPlugin()->auctions[$auctionID])){
			    		$this->nextWindow($sender, "AH Manage", LangManager::translate("ah-notfound", $sender), [LangManager::translate("continue", $sender), "/ah manage"]);
			    	}else{
			    		$auction = $this->getPlugin()->getAuction($auctionID);
			    		$price = $auction->getPrice();
			    		$item = $auction->getItem();
			    		if(strcasecmp($auction->getSeller(), $sender->getName()) === 0){
			    			if(!$sender->getInventory()->canAddItem($item)){
			    				$this->nextWindow($sender, "AH Retire #" . $auctionID, LangManager::translate("inventory-nospace", $sender), [LangManager::translate("continue", $sender), "/ah manage " . $auctionID]);
			    			}else{
			    				$auction->returnBids(false);
			    				unset($this->getPlugin()->auctions[$auctionID]);
			    				$sender->getInventory()->addItem($item);
			    				$this->getPlugin()->auctionStats->set("auctions-retired", $this->getPlugin()->auctionStats->get("auctions-retired", 0) + 1);
			    				LangManager::broadcast("ah-retire", $auctionID);
			    				$this->nextWindow($sender, "AH Retire #" . $auctionID, LangManager::translate("ah-retired", $sender), [LangManager::translate("continue", $sender), "/ah list"]);
			    			}
			    			break;
			    		}else{
			    			if($auction->getType() === Auction::TYPE_BUY){
			    				if(!isset($this->buy[$sender->getName()])){
			    					$this->buy[$sender->getName()] = [$auctionID, time()];
			    					$this->nextWindow($sender, "AH Buy #" . $auctionID, LangManager::translate("ah-buy-buy", $sender, $auction->getSeller(), $auction->getPrice()), [LangManager::translate("ah-buy-confirm", $sender), "/ah manage " . $auctionID]);
			    					break;
			    				}elseif(time() - $this->buy[$sender->getName()][1] <= $this->getPlugin()->getConfig()->getNested("auction.buy-timer")){
			    			        if(!$sender->getInventory()->canAddItem($item)){
			    			        	foreach($sender->getInventory()->getContents() as $i){
			    			        		if($i->equals($item)){
			    			        			$this->nextWindow($sender, "AH Buy #" . $auctionID, LangManager::translate("ah-buy-nospace-2", $sender, $item->getMaxStackSize() - $item->getCount()), [LangManager::translate("continue", $sender), "/ah manage " . $auctionID]);
			    			        			break 2;
			    			        		}
			    			        	}
			    			        	$this->nextWindow($sender, "AH Buy #" . $auctionID, LangManager::translate("ah-buy-nospace-1", $sender), [LangManager::translate("continue", $sender), "/ah manage" . $auctionID]);
			    			        	break;
			    			        }
			    			        if(!$sender->reduceMoney($price)){
			    			        	$this->nextWindow($sender, "AH Buy #" . $auctionID, LangManager::translate("money-needed-more", $sender, $sender->getMoney() - $price), [LangManager::translate("continue", $sender), "/ah manage " . $auctionID]);
			    			        	break;
			    			        }
			    			        Main::getInstance()->addMoney($auction->getSeller(), $price);
			    			        unset($this->getPlugin()->auctions[$auctionID]);
			    			        $sender->getInventory()->addItem($item);
			    			        LangManager::broadcast("ah-buy-broadcast", $auctionID, $auction->getSeller(), $sender->getName());
			    			        $this->getPlugin()->auctionStats->set("auctions-sold", $this->getPlugin()->auctionStats->get("auctions-sold", 0) + 1);
			    			        $this->nextWindow($sender, "AH Buy #" . $auctionID, LangManager::translate("ah-buy", $auctionID), [LangManager::translate("continue", $sender), "/ah manage"]);
			    			        $this->getPlugin()->questManager->getQuest("salesmen")->progress($auction->getSeller(), 1);
			    			        $seller = $this->getPlugin()->getServer()->getPlayerExact($auction->getSeller());
			    			        if($seller instanceof Player){
										$sender->sendMessage("ah-buy-sold", $auctionID, $sender->getName());
			    			        }
			    			    }else{
			    			    	$this->nextWindow($sender, "AH Buy #" . $auctionID, LangManager::translate("timedout", $sender), [LangManager::translate("continue", $sender), "/ah manage " . $auctionID]);
			    			    }
			    			    unset($this->buy[$sender->getName()]);
			    			}elseif($auction->getType() === Auction::TYPE_BID){
			    				$timeLeft = $this->getPlugin()->formatTime($this->getPlugin()->getTimeLeft($auction->getMaxBidTime()));
			    				$form = new CustomForm(function(Player $player, ?array $data) use($auctionID, $timeLeft, $auction){
			    					if(isset($data["bid"])){
			    						$bidAmount = intval($data["bid"]);
			    						if($bidAmount < 1){
			    							$this->nextWindow($player, "AH Bid #" . $auctionID, LangManager::translate("ah-bid-invalid", $player), [LangManager::translate("continue", $player), "/ah manage " . $auctionID]);
			    						}else{
			    							$result = $auction->postBid($player->getName(), $bidAmount);
			    							switch($result){
			    								case -3:
			    								    $this->nextWindow($player, "AH Bid #" . $auctionID, LangManager::translate("ah-bid-expired", $player, $auctionID), [LangManager::translate("continue", $player), "/ah list"]);
			    								    break;
			    								case -2:
			    								    $this->nextWindow($player, "AH Bid #" . $auctionID, LangManager::translate("ah-bid-top", $player), [LangManager::translate("continue", $player), "/ah manage " . $auctionID]);
			    								    break;
			    								case -1:
			    								    $this->nextWindow($player, "AH Bid #" . $auctionID, LangManager::translate("ah-bid-low", $player), [LangManager::translate("continue", $player), "/ah manage " . $auctionID]);
			    								    break;
			    								case 0:
			    								    $this->nextWindow($player, "AH Bid #" . $auctionID, LangManager::translate("money-needed-more", $player, bcsub((string) $bidAmount, (string) $player->getMoney())), [LangManager::translate("continue", $player), "/ah manage " . $auctionID]);
			    								    break;
			    								case 1:
			    								    LangManager::broadcast("ah-bid-broadcast", $auctionID, $auction->getSeller(), $bidAmount, $player->getName(), $timeLeft);
			    								    $this->nextWindow($player, "AH Bid #", LangManager::translate("ah-bid-placed", $player, $bidAmount, $auctionID), [LangManager::translate("continue", $player), "/ah manage " . $auctionID]);
			    								    break;
			    							}
			    						}
			    					}else{
			    						$player->chat("/ah");
			    					}
			    				});
			    				$form->setTitle(TextFormat::colorize("&9&lAH Bid #" . $auctionID));
			    				$form->addLabel(LangManager::translate("ah-bid-left", $sender) . $timeLeft);
			    				if(!empty($auction->getBids())){
			    					$form->addLabel(LangManager::translate("ah-bid-history", $sender));
			    					$details = "";
			    					$i = 0;
			    					foreach($auction->getSortedBids() as $bid){
			    						if(++$i > 5){
			    							break;
			    						}
			    						list($pl, $amount) = $bid;
			    						$form->addLabel(LangManager::translate("ah-bid-bid", $pl, $amount));
			    					}
			    				}else{
			    					$form->addLabel(LangManager::translate("ah-bid-none", $sender));
			    				}
			    				$form->addLabel(LangManager::translate("ah-bid-info", $sender));
			    				$form->addInput(LangManager::translate("ah-bid-amount", $sender), "", null, "bid");
								$sender->sendForm($form);
			    			}
			    		}
					}
			    	}
			    break;
			default:
			    $this->homeWindow($sender);
		}
		return true;
	}
	
}