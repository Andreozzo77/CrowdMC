<?php

namespace LegacyCore\Events;

use LegacyCore\Core;

use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\item\Item;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use pocketmine\scheduler\AsyncTask;

use kenygamer\Core\Main;
use kenygamer\Core\util\ItemUtils;
use kenygamer\Core\util\CustomItems;
use LegacyCore\Commands\RankUp;

class PrisonMiner implements Listener{
	/** @var array */
	public static $queue = [];
	/** @var bool */
	public static $processingQueue = false;

    public function __construct(Core $plugin){
		$plugin->getScheduler()->scheduleRepeatingTask(new QueueTask(), 20);
	}
	
	public static function queue0(Player $player) : void{
		$main = Main::getInstance();
		//Mining Mask
	    $mining_mask = ItemUtils::get("mining_mask");
	    $miningMask = 0;
	    if($player->getArmorInventory() !== null && ($helmet = $player->getArmorInventory()->getHelmet())->equals($mining_mask, true, false) && $helmet->getNamedTag()->hasTag(CustomItems::TIER_TAG)){
	    	$miningMask = $helmet->getNamedTag()->getInt(CustomItems::TIER_TAG);
	    }
		self::$queue[$player->getName()][0] = [$main->permissionManager->getPlayerGroup($player)->getName(), $main->xpboost->get($player->getName()), $main->permissionManager->getPlayerPrefix($player), $miningMask];
	}
	
	/**
	 * @see LegacyCore\Events\Area Block exemption
	 *
     * @param BlockBreakEvent $event
     *
     * @priority HIGH
     * @ignoreCancelled true
     */
	public function onMine(BlockBreakEvent $event) : void{
	    $p = $event->getPlayer();
	    $block = $event->getBlock();
	    if($p->getGamemode() === Player::SURVIVAL && $p->getLevel()->getFolderName() === "prison"){
	    	$event->setDrops([]);
	    	if(!isset($this->queue[$p->getName()][0])){
	    		self::queue0($p);
	    	}
	    	self::$queue[$p->getName()][1][] = $block->getId();
	    }
	}

}


class QueueTask extends Task{
	
	public function onRun(int $currentTick) : void{
		if(!empty(PrisonMiner::$queue) && !PrisonMiner::$processingQueue){
			PrisonMiner::$processingQueue = true;
			Server::getInstance()->getAsyncPool()->submitTask(new ProcessQueueTask(PrisonMiner::$queue));
		}
	}
}

class ProcessQueueTask extends AsyncTask{
	private const BLOCK_EARNING = [
	    //Normal
	    Block::COAL_ORE => 10,
	    Block::IRON_ORE => 20,
	    Block::LAPIS_ORE => 30,
	    //PVP (+ Block::LAPIS_ORE)
	    Block::DIAMOND_ORE => 40,
	    Block::EMERALD_ORE => 50,
	    Block::NETHER_QUARTZ_ORE => 60,
	    //VIP+
	    Block::GOLD_BLOCK => 70,
	    Block::DIAMOND_BLOCK => 80,
	    Block::EMERALD_BLOCK => 90,
	    //Personal Mines (+ Block::EMERALD_BLOCK)
	    Block::PRISMARINE => 100,
	    Block::SEA_LANTERN => 110
	];
	
	/** @var string serialized array */
	private $queue = "";
	
	public function __construct(array $queue){
		$this->queue = serialize($queue);
	}
	
	public function onRun() : void{
		$updates = [];
		$ranks = RankUp::getRanks();
		$queue = unserialize($this->queue);
		foreach($queue as $player => $data){
			$nastyData = $data[0];
			/** @var int[] $breaks */
			$breaks = $data[1];
			/** @var string $rank */
			$rank = $nastyData[0];
			/** @var array|false $xpBoost */
			$xpBoost = $nastyData[1];
			/** @var string $prisonRank */
			$prisonRank = $nastyData[2];
			/** @var int $miningMask */
			$miningMask = $nastyData[3];
			
			$prisonRanks = RankUp::getRanks();
			
			$money = 0;
			$exp = 1; //This is required for boosters to work
			foreach($breaks as $break){
				$key = array_search($rank, $ranks);
				if($key !== false){
					$money += 1 * $key;
				}
				$key = array_search($prisonRank, $prisonRanks);
				if($key !== false){
					$money += 0.1 * $key;
				}
			}
			
			if($miningMask > 0){
				$money += $money * ($miningMask * 10) / 100;
			}
			switch($rank){
				case "Nigthmare":
				   $money += $money * 3 / 100;
				   break;
				case "Universe": //Universe+
				   $money += $money * 6 / 100;
				   break;
				   //Cant Main::rankCompare() :(
			}
			$exp += $exp * ($xpBoost === false ? 1 : (!(time() > $xpBoost[1]) ? $xpBoost[0] : 1));
			$updates[$player] = [$money, $exp];
		}
		$this->setResult($updates);
	}
	
	/**
	 * Sets the player money and EXP
	 *
	 * @param Server $server
	 */
	public function onCompletion(Server $server) : void{
		$updates = $this->getResult();
		foreach($updates as $player => $data){
			list($money, $exp) = $data;
			
			$player = $server->getPlayerExact($player);
			if($player !== null && $player->isOnline()){
				$player->addXp($exp);
				$player->addMoney($money);
				$player->addTitle(TextFormat::colorize("&a\$" . number_format($money) . "+\n&bEXP: " . number_format($exp) . "+"));
			}
		}
		PrisonMiner::$queue = [];
		PrisonMiner::$processingQueue = false;
	}
	
}