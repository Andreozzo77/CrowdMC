<?php

declare(strict_types=1);

namespace kenygamer\GitHubConnection;

use pocketmine\Server;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

final class Main extends PluginBase{
	public const TEMP_FILE = "commit.tmp";
	public const SNAPSHOT_FILE = ".snapshot"; //We call it snapshot when the commit is installed
	/** @var Repository[] */
	private $repositories = [];
	/** @var self|null */
	private static $instance = null;	
	
	public function onCommand(CommandSender $sender, Command $cmd, string $label, array $args) : bool{
		//return false; //NOPE
		$this->reinstall(@file_get_contents(Server::getInstance()->getDataPath() . self::SNAPSHOT_FILE) ?? "-1");
		return true;
	}
	
	public function reinstall(string $commit) : void{
		
		Server::getInstance()->getAsyncPool()->submitTask(new InstallTask($this->repositories, self::SNAPSHOT_FILE, $commit));
	}
	
	public function onDisable() : void{
		self::$instance = null;
	}
	
	public function onEnable() : void{
		self::$instance = $this;
		$errors = false;
		
		//Check required extensions
		$exts = ["git", "zip"];
		$missingExts = [];
        foreach($exts as $ext){
        	if(empty(shell_exec("command -v " . $ext))){
        		$missingExts[] = $ext;
        	}
        }
        if(count($missingExts) > 0){
        	$this->getLogger()->critical("These extensions are required but not found: " . implode(", ", $missingExts));
        	$errors = true;
        }
        
        if(posix_getuid() !== 0){
        	$this->getLogger()->critical("You must run PocketMine-MP as root/sudo");
        	$errors = true;
        }
        if($errors){
        	$this->getServer()->getPluginManager()->disablePlugin($this);
        }else{
        	$config = (new Config($this->getServer()->getDataPath() . "GitHubConnection.js", Config::JSON))->getAll();
			$repositories = $config["repositories"] ?? [];
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
        $this->getScheduler()->scheduleRepeatingTask(new CheckCommitTask(), intval($config["check_rate"] ?? 5) * 20);
	}
	
}