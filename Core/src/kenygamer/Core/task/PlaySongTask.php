<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\math\Vector2;
use pocketmine\network\mcpe\protocol\PlaySoundPacket;
use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;
use xenialdan\libnbs\Layer;
use xenialdan\libnbs\NBSFile;
use xenialdan\libnbs\Song;
use kenygamer\Core\Main;

class PlaySongTask extends Task{
	/** @var Song */
    private $song;
	/** @var string */
    private $songfilename = "";
	/** @var int */
	private $maxTick;
    /** @var int */
    private $lastTick = -1;
    /** @var bool */
    protected $playing = false;
    /** @var int */
    private $tick = -1;
    /** @var Player */
    private $player;

	/**
	 * @parak string $songfilename
	 * @param Song $song
	 * @param Player $player
	 */
    public function __construct(Plugin $owner, string $songfilename, Song $song, Player $player, int $maxTick = -1){
        $this->player = $player;
        $this->song = $song;
        $this->songfilename = $songfilename;
        $this->playing = true;
		$this->maxTick = $maxTick;
		$this->layers = $this->song->getLayerHashMap()->values()->toArray();
    }
	
	/**
	 * @param int $currentTick
	 */
    public function onRun(int $currentTick) : void{
        if(!$this->playing){
            return;
        }
        $this->playTick($this->player);
        $this->tick++;
   	}

	/**
	 * @param Player $player
	 */
    public function playTick(Player $player) : void{
    	if(!$player->isOnline() || ($this->maxTick !== -1 && $this->tick >= $this->maxTick)){
    		Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
    		return;
    	}
		$playerVolume = 100;
        /** @var Layer $layer */
        foreach($this->layers as $layer){
            $note = $layer->getNote($this->tick);
            if($note === null){
                continue;
            }
            $volume = ($layer->getVolume() * (int) $playerVolume) / 100;
            //Shift nbs range for note block sounds (33 - 57) to start at 0
            //Then shift by some extra -12 for the pitch calculation: https://minecraft.gamepedia.com/Note_Block#Notes
            $pitch = 2 ** (($note->getKey() - 45) / 12);
            $sound = NBSFile::MAPPING[$note->instrument] ?? NBSFile::MAPPING[NBSFile::INSTRUMENT_PIANO];
            $pk = new PlaySoundPacket();
            $pk->soundName = $sound;
            $pk->pitch = $pitch;
            $pk->volume = $volume;
            $vector = $player->asVector3();
            if($layer->stereo !== 100){//Not centered, modify position. TODO check
                $yaw = ($player->yaw - 90) % 360;
                $add = (new Vector2(-cos(deg2rad($yaw) - M_PI_2), -sin(deg2rad($yaw) - M_PI_2)))->normalize();
                $multiplier = 2 * ($layer->stereo - 100) / 100;
                $add = $add->multiply($multiplier);
                $vector->add($add->x, 0, $add->y);
            }
            $pk->x = $vector->x;
            $pk->y = $vector->y + $player->getEyeHeight();
            $pk->z = $vector->z;
            $player->dataPacket($pk);
            unset($add, $pk, $vector, $note);
        }
    }
	
}