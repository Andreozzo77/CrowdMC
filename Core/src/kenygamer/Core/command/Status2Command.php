<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\network\mcpe\protocol\ProtocolInfo;
use kenygamer\Core\Main;
use LegacyCore\Core;

class Status2Command extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"status2",
			"Print a JSON blob with server status",
			"/status2",
			[],
			BaseCommand::EXECUTOR_ALL,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$players = $this->getPlugin()->getServer()->getOnlinePlayers();
		$pingSum = 0;
		
		$data = [
			"ip" => $this->getPlugin()->serverIp,
			"port" => $this->getPlugin()->getServer()->getPort(),
			"status" => $this->getPlugin()->maintenanceMode ? 0 : 1,
			"maintenanceReason" => $this->getPlugin()->getMaintenanceReason(),
			"mcpeVersion" => ProtocolInfo::MINECRAFT_VERSION,
			"tps" => $this->getPlugin()->getServer()->getTicksPerSecondAverage(),
			"load" => $this->getPlugin()->getServerLoadAverage(),
			//No clue what the hell happens with array keys of array_map ret theyre just weird symbols
			//that cant be json encoded
			"players" => array_values(array_map(function(Player $player) use(&$pingSum) : string{
				$pingSum += $player->getPing();
				return $player->getName();
			}, $players)),
			"maxPlayers" => $this->getPlugin()->getServer()->getMaxPlayers(),
			"averagePing" => count($players) > 0 ? round($pingSum / count($players)) : 0,
			"snapshot" => Core::$snapshot
		];
		$sender->sendMessage(json_encode($data, JSON_UNESCAPED_UNICODE));
		return true;
	}
	
}