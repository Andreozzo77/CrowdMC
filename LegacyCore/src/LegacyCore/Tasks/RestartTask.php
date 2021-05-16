<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\level\Level;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use kenygamer\Core\koth\KothTask;

class RestartTask extends Task{

    /** @var Core */
    public $time = 0;
	/** @var Core */
    public $plugin;
    
    public const RESTART_TIME = 5400;
    
    private static $instance;
    
	/**
     * RestartTask constructor.
     * @param Core $plugin
     * @param Player $player
     */
    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
        self::$instance = $this;
	}
	
	public static function getInstance() : self{
		return self::$instance;
	}
    
	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
        switch($this->time) {
			case 600:
			$this->plugin->getServer()->broadcastMessage("§l§6» §r§eThe server restarts in §680 minutes§e!");
            $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
            break;
			case 1200:
			$this->plugin->getServer()->broadcastMessage("§l§6» §r§eThe server restarts in §670 minutes§e!");
            $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
            break;
            case 1800:
            $this->plugin->getServer()->broadcastMessage("§l§6» §r§eThe server restarts in §660 minutes§e!");
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'save on');
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'save-all');
            break;
			case 2400:
            $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
			break;
			case 3000:
            $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
            break;
            case 3600:
            $this->plugin->getServer()->broadcastMessage("§l§6» §r§eThe server restarts in §630 §eminutes!");
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'save on');
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'save-all');
            break;
			case 4200:
            $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
            break;
			case 4800:
            $this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
            break;
			case 5340:
			$this->plugin->getServer()->broadcastMessage("§l§c» §r§eWarning! Server is restarting in §61 §eminute!");
			$this->plugin->getServer()->broadcastTitle(TextFormat::RED . "Warning", TextFormat::GOLD . "Server restarts in 1 minute!", 50, 50, 50);
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'mine reset-all');
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'save on');
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), 'save-all');
			break;
            case self::RESTART_TIME:
			$this->plugin->getServer()->dispatchCommand(new ConsoleCommandSender(), "stop");
            break;
        }
		if((class_exists(Main::class) && Main::$giveawayStatus[0]) || (class_exists(KothTask::class) && KothTask::getInstance() instanceof KothTask && KothTask::getInstance()->isEnabled())){
			return;
		}
        $this->time++;
    }
	
}