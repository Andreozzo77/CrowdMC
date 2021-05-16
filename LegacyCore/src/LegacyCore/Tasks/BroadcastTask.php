<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use pocketmine\scheduler\Task;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

/**
 * Class BroadcastTask 
 * @package LegacyCore\Tasks
 */
class BroadcastTask extends Task{

    /** @var Core */
	public $plugin;
	
	/** @var int[] */
	private $broadcasts = [];

	/**
     * BroadcastTask constructor.
     * @param Core $plugin
     */
    public function __construct(Core $plugin) {
        $this->plugin = $plugin;
    }

	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
        $broadcasts = range(1, 52);
        
        $broadcast = $broadcasts[array_rand($broadcasts)];
        if(!in_array($broadcast, $this->broadcasts)){
        	$this->broadcasts[] = $broadcast;
        	LangManager::broadcast("server-broadcast-" . $broadcast);
        }
    }
}