<?php

namespace LegacyCore\Commands\Warp;

use LegacyCore\Core;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\entity\Effect;
use pocketmine\entity\EffectInstance;
use pocketmine\plugin\Plugin;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use pocketmine\math\Vector3;

use kenygamer\Core\LangManager;

class PvPMine extends PluginCommand{
	
	/** @var array */
	public $warp;
	
	/** @var array */
	public $plugin;

	public function __construct($name, Core $plugin){
        parent::__construct($name, $plugin);
        $this->setDescription("Warp to PvP mine");
        $this->setUsage("/pvpmine");
        $this->setAliases(["pm"]);
		$this->setPermission("core.command.pvpmine");
		$this->plugin = $plugin;
    }
	
	/**
     * @param CommandSender $sender
     * @param string $alias
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $alias, array $args): bool{
        if(!$sender->hasPermission("core.command.pvpmine")) {
	      	LangManager::send("cmd-noperm", $sender);
            return false;
        }
		if ($sender instanceof ConsoleCommandSender) {
			LangManager::send("run-ingame", $sender);
			return false;
		}
		if (!isset($this->plugin->warp[$sender->getLowerCaseName()]) || time() > $this->plugin->warp[$sender->getLowerCaseName()] || $sender->hasPermission("core.cooldown.bypass")) {
            $this->plugin->warp[$sender->getLowerCaseName()] = time() + 15;
            $random = 1;
            switch($random){
	    	    case 1:
		     	$level = $this->plugin->getServer()->getLevelByName("prison");
                $x = 1;
                $y = 32;
                $z = 0;
                $sender->teleport(new Position($x, $y, $z, $level));
				$sender->addTitle(LangManager::translate("core-pvpmine-title1", $sender), LangManager::translate("core-pvpmine-title2", $sender), 20, 20, 20);
				$this->Miner($sender);
			    return true;
		    	/*case 2:
		    	$level = $this->plugin->getServer()->getLevelByName("prison");
                $x = 933;
                $y = 103;
                $z = 971;
                $sender->teleport(new Position($x, $y, $z, $level));
		    	$sender->sendMessage(TextFormat::GREEN . "Teleporting To PvP Mine");
                $sender->addTitle(TextFormat::GOLD . "PvP Prison Mine", TextFormat::AQUA . "Be careful PvP is enabled here!", 20, 20, 20);
				$this->Miner($sender);
		    	return true;
		    	case 3:
		    	$level = $this->plugin->getServer()->getLevelByName("prison");
                $x = 933;
                $y = 86;
                $z = 932;
                $sender->teleport(new Position($x, $y, $z, $level));
		    	$sender->sendMessage(TextFormat::GREEN . "Teleporting To PvP Mine");
                $sender->addTitle(TextFormat::GOLD . "PvP Mine Prison", TextFormat::AQUA . "Be Careful They Enabled PvP In Prison", 20, 20, 20);
				$this->Miner($sender);
		    	return true;
			    case 4:
			    $level = $this->plugin->getServer()->getLevelByName("prison");
                $x = 923;
                $y = 85;
                $z = 938;
                $sender->teleport(new Position($x, $y, $z, $level));
		    	$sender->sendMessage(TextFormat::GREEN . "Teleporting To PvP Mine");
                $sender->addTitle(TextFormat::GOLD . "PvP Mine Prison", TextFormat::AQUA . "Be Careful They Enabled PvP In Prison", 20, 20, 20);
				$this->Miner($sender);
			    return true;
				default:
				return false;*/
			}
		} else {
			LangManager::send("in-cooldown", $sender);
			return false;
		}
	}

	/**
     * @public miner
     */
	public function Miner(Player $player) {
        if (isset($this->plugin->pvpmine[$player->getLowerCaseName()])) {
            if ((time() - $this->plugin->pvpmine[$player->getLowerCaseName()]) > $this->plugin->pmtimer) {
                LangManager::send("invulnerable", $player, $this->plugin->pvpmine);
            }
        } else {
            LangManager::send("invulnerable", $player, $this->plugin->pvpmine);
        }
        $this->plugin->pvpmine[$player->getLowerCaseName()] = time();
    }
}