<?php

namespace LegacyCore\Commands;

use LegacyCore\Core;

use LegacyCore\Tasks\RankUpTask;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\item\Item;
use pocketmine\item\enchantment\Enchantment;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\Durable;
use pocketmine\plugin\Plugin;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class RankUp extends PluginCommand{
	/** @var Core */
	private $plugin;

	public function __construct($name, Core $plugin) {
        parent::__construct($name, $plugin);
        $this->setDescription("Rank up to unlock a prison rank");
        $this->setUsage("/rankup [rank]");
        $this->setAliases(["ru"]);
        $this->setPermission("core.command.rankup");
		$this->plugin = $plugin;
    }
    
    /**
     * Calculates the rank up price
     *
     * @param int $from
     * @param int $to
     *
     * @return float
     */
    public function getRankUpPrice(int $from, int $to) : float{
    	$price = 100000000;
    	
    	if(!($from < $to) || $from < 0 || $to < 0){
			echo "#1 $from $to\n";
    		return 0;
    	}
    	
    	$fromPrice = 0;
    	$toPrice = 0;
    	
    	for($i = count(self::getRanks()) - 1; $price > 0 && !($i < 0); $i--){
    		//$price -= round($price / 2, -1); //round to nearest thousand/2
			$price -= $price / 2;
    		if($i === $from){
    			$fromPrice = $price;
    		}elseif($i === $to){
    			$toPrice = $price;
    		}
    	}
		var_dump(compact("toPrice", "fromPrice"));
    	return $toPrice - $fromPrice;
    }
    
    /**
     * @return string[]
     */
    public static function getRanks() : array{
    	$ranks = range("A", "Z");
    	$ranks[] = "Free";
    	return $ranks;
    }

	/**
     * @param CommandSender $sender
     * @param string $label
     * @param array $args
     *
     * @return bool
     */
    public function execute(CommandSender $sender, string $label, array $args): bool{
		if(!$sender->hasPermission("core.command.rankup")){
			LangManager::send("cmd-noperm", $sender);
			return true;
		}
		if($sender instanceof ConsoleCommandSender){
			LangManager::send("run-ingame", $sender);
			return true;
		}
		
		$ranks = self::getRanks();
		/** @var string */
		$rank = Main::getInstance()->permissionManager->getPlayerPrefix($sender);
		
		$key = array_search($rank, $ranks);
		if($key === false){
			$key = 0;
		}
		
		$args[0] = $args[0] ?? "";
		$next = array_search(ucfirst($args[0]), $ranks);
		
		if($next === false){
			$next = $key + 1;
		}
		var_dump($next . " is next index");
		/** @var string */
		$nextRank = $ranks[$next] ?? "";
		var_dump($nextRank . " is next");
		
		$price = $this->getRankUpPrice($key, $next);
		
		if($price <= 0){
			LangManager::send("core-rankup-error", $sender);
			return true;
		}
		if(Main::getInstance()->reduceMoney($sender, $price)){
			Main::getInstance()->permissionManager->setPlayerPrefix($sender, $nextRank);
			LangManager::send("core-rankup", $sender, $rank, $nextRank, $price);
			$sender->addTitle(LangManager::translate("core-rankup-title1", $sender), LangManager::translate("core-rankup-title2", $sender, $nextRank));
			return true;
		}
		LangManager::send("money-needed-more", $sender, $price - Main::getInstance()->myMoney($sender));
		return true;
	}
	
}