<?php

declare(strict_types=1);

namespace kenygamer\GitHubConnection;

use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\Terminal;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\utils\TextFormat;
use pocketmine\utils\Config;

final class InstallTask extends AsyncTask{
	/** @var string */
	private $repositories, $commitFile, $commit;
	
	/**
	 * @param Repository[] $repositories
	 * @param string $commitFile
	 * @param string $commit
	 */
	public function __construct(array $repositories, string $commitFile, string $commit){
		$this->repositories = serialize($repositories);
		$this->commitFile = $commitFile;
		$this->commit = $commit;
	}
	
	public function onRun() : void{
		if(!Terminal::isInit()){
			Terminal::init();
		}
		$repositories = unserialize($this->repositories);
		$start = microtime(true);
		$totPlugins = 0;
		foreach($repositories as $repositoryObj){
			/** @var string */
			$repository = $repositoryObj->getRepository();
			$branch = $repositoryObj->getBranch();
			$user = $repositoryObj->getUser();
			$password = $repositoryObj->getPassword();
			$repo = $repositoryObj->getRepo();
			/** @var string[] */
			$plugins = $repositoryObj->getPlugins();
			
			$start = microtime(true);
			$pluginPath = \pocketmine\PLUGIN_PATH;
			
			shell_exec('rm -rf "' . $pluginPath . $repo . '"'); //Fix git error: Target directory not empty
			if($repositoryObj->getVisibility() === Repository::REPO_PUBLIC){
				shell_exec('cd ' . $pluginPath . ' && git clone -b "' . $branch . '" https://github.com/' . $repository . ' > /dev/null 2>&1');
			}else{
				shell_exec('cd ' . $pluginPath . ' && git clone -b "' . $branch . '" https://' . $user . ':' . $password . '@github.com/' . $repository . ' > /dev/null 2>&1');
			}
			
			if(file_exists($pluginPath . $repo . '/filemap.yml')){
				echo Terminal::$COLOR_YELLOW . "[GitHubConnection] " . Terminal::$COLOR_AQUA . " Parsing filemap.yml..." . PHP_EOL;
				$cfg = new Config($pluginPath . $repo . '/filemap.yml', Config::YAML);
				foreach($cfg->getAll() as $source => $dest){
					$source = str_replace("%serverPath%", \pocketmine\PATH, $source);
					$dest = str_replace("%serverPath%", \pocketmine\PATH, $dest);
					if(!(copy($source, $dest))){
						echo Terminal::$COLOR_YELLOW . "[GitHubConnection] " . Terminal::$COLOR_RED . "Failed to copy " . $source . " => " . $dest . ". This may be because the directory tree is not full." . PHP_EOL;
					}else{
						echo Terminal::$COLOR_YELLOW . "[GitHubConnection] " . Terminal::$COLOR_GREEN . "Copied " . $source . " => " . $dest . PHP_EOL;
					}
				}
			}
			$totPlugins += count($plugins);
			foreach($plugins as $plugin){
				shell_exec('rm -rf "' . $pluginPath . $plugin . '"');
				shell_exec('mv "' . $pluginPath . $repo . '/' . $plugin . '" "' . $pluginPath . '"');
				shell_exec(\pocketmine\PATH . '/bin/php7/bin/php -r \'$phar = new Phar("' . $pluginPath . $plugin . '.phar"); $phar->buildFromDirectory("' . $pluginPath . $plugin . '");\'');
				shell_exec('rm -rf "' . $pluginPath . $plugin . '"');
			}
			shell_exec('rm -rf "' . $pluginPath . $repo . '"');
		}
		$diff = microtime(true) - $start;
		echo Terminal::$COLOR_YELLOW . "[GitHubConnection]" . Terminal::$COLOR_GREEN . " Installed " . $totPlugins . " plugins from " . count($repositories) . " repositories, took " . round($diff, 2) . "s" . PHP_EOL;
	}
	
	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) : void{
		$server->dispatchCommand(new ConsoleCommandSender(), "stop");
		file_put_contents($this->dataPath . $this->commitFile, $this->commit);
	}
	
}