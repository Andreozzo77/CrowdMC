<?php

namespace LegacyCore\Tasks;

use LegacyCore\Core;

use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\math\Vector3;
use pocketmine\plugin\Plugin;
use pocketmine\level\Position;
use pocketmine\network\mcpe\protocol\LevelEventPacket;

class GuardianTask extends Task{
	
	/** @var array */
	public $player;
	/** @var array */
	public $plugin;

	/**
     * GuardianTask constructor.
     * @param Core $plugin
     * @param Player $player
     */
    public function __construct(Core $plugin, Player $player){
        $this->plugin = $plugin;
		$this->player = $player;
	}

	/**
     * @param $currentTick
     */
    public function onRun(int $currentTick) : void{
    	if(!$this->player->isOnline()){
    		return;
    	}
        $pk = new LevelEventPacket();
        $pk->evid = LevelEventPacket::EVENT_GUARDIAN_CURSE;
        $pk->data = 0;
        $pk->position = $this->player->asVector3();
		$this->player->dataPacket($pk);
		
		$pk3 = new LevelEventPacket();
		$pk3->evid = LevelEventPacket::EVENT_SOUND_ANVIL_FALL;
		$pk3->data = 0;
		$pk3->position = $this->player->asVector3();
		$this->player->dataPacket($pk3);
    }
}