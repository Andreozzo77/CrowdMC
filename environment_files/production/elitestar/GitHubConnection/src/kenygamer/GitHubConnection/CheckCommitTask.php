<?php

declare(strict_types=1);

namespace kenygamer\GitHubConnection;

use pocketmine\Server;
use pocketmine\scheduler\Task;
use pocketmine\utils\TextFormat;

final class CheckCommitTask extends Task{
	/** @var Repository[] */
	private $repositories = [];
	
	private const TEMP_FILE = "commit.tmp";
	private const SNAPSHOT_FILE = ".snapshot"; //We call it snapshot when the commit is installed
	
	/**
	 * @param array $plugins
	 */
	public function __construct(array $repositories){
		$this->repositories = [];
		foreach($repositories as $repository => $data){
			try{
				$repository = new Repository($repository, $data);
			}catch(\InvalidArgumentException $e){
				Server::getInstance()->getLogger()->critical("[GitHubConnection] " . $e->getMessage());
			}
			$this->repositories[] = $repository;
		}
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$dataPath = Server::getInstance()->getDataPath();
		if(file_exists($dataPath . self::TEMP_FILE)){
            $commit = file_get_contents($dataPath . self::TEMP_FILE); // hash
            unlink($dataPath . self::TEMP_FILE);
            if(!empty($this->repositories)){
            	Server::getInstance()->getAsyncPool()->submitTask(new InstallTask($this->repositories, self::SNAPSHOT_FILE, $commit));
            }
		}
	}
	
}