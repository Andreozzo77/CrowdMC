<?php

declare(strict_types=1);

namespace kenygamer\GitHubConnection;

use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

final class CheckCommitTask extends Task{
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$dataPath = Server::getInstance()->getDataPath();
		if(file_exists($dataPath . Main::TEMP_FILE)){
            $commit = file_get_contents($dataPath . Main::TEMP_FILE); // hash
            unlink($dataPath . Main::TEMP_FILE);
			Main::getInstance()->reinstall($commit);
		}
	}
	
}