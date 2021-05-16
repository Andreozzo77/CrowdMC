<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\plugin\Plugin;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\level\Location;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use jojoe77777\FormAPI\SimpleForm;

class Warp extends PluginCommand{
	
	/** @var array */
	public $warp;

	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Teleport to a warp");
        $this->setUsage("/warp");
        $this->setAliases(["warps"]);
        $this->setPermission("core.command.warp");
		$this->plugin = $plugin;
    }

	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args): bool{
		if (!$sender->hasPermission("core.command.warp")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (count($args) < 1) {
			$this->WarpUI($sender);
            return false;
        }
		if (isset($args[0])) {
		    switch(mb_strtolower($args[0])) {
			    case "spawn":
			    case "hub":
			        $this->Spawn($sender);
			        break;
			    case "shop":
			    case "store":
			        $this->Shop($sender);
			        break;
			    case "casino":
			    case "gamble":
			        $this->Casino($sender);
			        break;
			    case "maze":
			    case "labyrinth":
			        $this->Maze($sender);
			        break;
			    case "mine":
			    case "prison":
			        $this->Mine($sender);
			        break;
			    case "pmine":
			    case "pvpmine":
			        $this->plugin->getServer()->getCommandMap()->dispatch($sender, "pvpmine");
			        break;
			    case "warzone":
			    case "wz":
			    case "ffa":
			        $this->plugin->getServer()->getCommandMap()->dispatch($sender, "warzone");
			        break;
			    case "wild":
			    case "wilderness":
			        $this->plugin->getServer()->getCommandMap()->dispatch($sender, "wild");
			        break;
			    case "hotel":
			        $this->Hotel($sender);
			        break;
			    case "minigames":
			        $this->Minigames($sender);
			        break;
				case "workshop":
					$this->Workshop($sender);
					break;
				case "crates":
					$this->Crates($sender);
					break;
				case "tinkerer":
					$this->Tinkerer($sender);
					break;
				case "trader":
					$this->Trader($sender);
					break;
				default:
				    $this->WarpUI($sender);
			        return false;
			}
		}
		return true;
	}
	
	public function Spawn(Player $player) : void{
		$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
		$player->addTitle(LangManager::translate("core-spawn-title1", $player), LangManager::translate("core-spawn-title2", $player), 20, 20, 20);
	}
	
	public function Minigames(Player $player) : void{
		$player->teleport($player->getServer()->getLevelByName("minigames")->getSpawnLocation());
		$player->addTitle(LangManager::translate("core-minigames-title1", $player), LangManager::translate("core-minigames-title2", $player), 20, 20, 20);
	}
	
	public function Shop(Player $player) : void{
		$player->teleport($player->getServer()->getDefaultLevel()->getSpawnLocation());
		$player->addTitle(LangManager::translate("core-shop-title1", $player), LangManager::translate("core-shop-title2", $player), 20, 20, 20);
	}
	
	public function Casino(Player $player) : void{
	    $player->teleport(new Location(45047, 41, -42577, 140, 0, $player->getServer()->getDefaultLevel()));
		$player->addTitle(LangManager::translate("core-casino-title1", $player), LangManager::translate("core-casino-title2", $player), 20, 20, 20);
	}
	
	public function Maze(Player $player) : void{
		$player->teleport($player->getServer()->getLevelByName("maze")->getSpawnLocation());
		$player->addTitle(LangManager::translate("core-maze-title1", $player), LangManager::translate("core-maze-title2", $player), 20, 20, 20);
	}
	
	public function Mine(Player $player) : void{
	    $player->teleport(new Location(22, 30, 125, 0, 0, $player->getServer()->getLevelByName("prison")));
		$player->addTitle(LangManager::translate("core-mine-title1", $player), LangManager::translate("core-mine-title2", $player), 20, 20, 20);
	}
	
	public function Hotel(Player $player) : void{
	    $player->teleport($player->getServer()->getLevelByName("hotel")->getSpawnLocation());
		$player->addTitle(LangManager::translate("core-hotel-title1", $player), LangManager::translate("core-hotel-title2", $player), 20, 20, 20);
	}
	
	public function Crates(Player $player) : void{
	    $player->teleport(new Location(45057, 41, -42595, 0, 0, $player->getServer()->getDefaultLevel()));
		$player->addTitle(LangManager::translate("core-crates-title1", $player), LangManager::translate("core-crates-title2", $player), 20, 20, 20);
	}
	
	public function Workshop(Player $player) : void{
	    $player->teleport(new Location(45232, 30, -42312, 0, 0, $player->getServer()->getDefaultLevel()));
		$player->addTitle(LangManager::translate("core-workshop-title1", $player), LangManager::translate("core-workshop-title2", $player), 20, 20, 20);
	}
	
	public function Tinkerer(Player $player) : void{
	    $player->teleport(new Location(45186, 30, -42272, 0, 0, $player->getServer()->getDefaultLevel()));
		$player->addTitle(LangManager::translate("core-tinkerer-title1", $player), LangManager::translate("core-tinkerer-title2", $player), 20, 20, 20);
	}
	
	public function Trader(Player $player) : void{
	    $player->teleport(new Location(44899, 29, -42445, 0, 0, $player->getServer()->getDefaultLevel()));
		$player->addTitle(LangManager::translate("core-trader-title1", $player), LangManager::translate("core-trader-title2", $player), 20, 20, 20);
	}
	
	
	/**
	 * @param WarpUIs
	 * @param Player $player
     */
	public function WarpUI(Player $player) : void{
		$form = new SimpleForm(function (Player $player, int $data = null) {
			$result = $data;
			if ($result === null) {
				return;
			}
			switch($result){
				case 0:
					$this->Spawn($player);
		          	break;
		   		case 1:
		        	$player->chat("/warp shop");
		        	break;
		    	case 2:
		        	$player->chat("/warp casino");
		        	break;
				case 3:
			    	$player->chat("/warp mine");
			    	break;
				case 4:
				    $player->chat("/pvpmine");
				    break;
			    case 5:
			        $player->chat("/warp warzone");
			        break;
			    case 6:
			        $player->chat("/warp wild");
			        break;
				case 7:
				    //$this->Hotel($player);
				    break;
				case 8:
				    $player->chat("/warp minigames");
				    break;
				case 9:
					$player->chat("/warp crates");
					break;
				case 10:
					$player->chat("/warp workshop");
					break;
				case 11:
					$player->chat("/warp tinkerer");
					break;
				case 12:
					$player->chat("/warp trader");
					break;
			}
		});
		$form->setTitle(LangManager::translate("core-warp-title", $player));
	    $form->setContent(LangManager::translate("core-warp-desc", $player));
	    $form->addButton(TextFormat::RED . "Spawn/Hub");
	    $form->addButton(TextFormat::GOLD . "Shop");
	    $form->addButton(TextFormat::YELLOW . "Casino");
		$form->addButton(TextFormat::GREEN . "Mine");
		$form->addButton(TextFormat::AQUA . "PvP Mine");
		$form->addButton(TextFormat::LIGHT_PURPLE . "Warzone");
		$form->addButton(TextFormat::RED . "Wilderness");
		$form->addButton(TextFormat::GOLD . "N/A");
		$form->addButton(TextFormat::YELLOW . "Minigames");
		$form->addButton(TextFormat::BLUE . "Crates");
		$form->addButton(TextFormat::GREEN . "Workshop");
		$form->addButton(TextFormat::AQUA . "Tinkerer");
		$form->addButton(TextFormat::LIGHT_PURPLE . "Trader");
		$form->sendToPlayer($player);
	}
}