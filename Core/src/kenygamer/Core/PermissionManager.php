<?php

declare(strict_types=1);

namespace kenygamer\Core;

use pocketmine\utils\Config;
use pocketmine\permission\PermissionAttachment;
use pocketmine\IPlayer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat;
use pocketmine\command\ConsoleCommandSender;

class PermissionManager{
	private const DB_PATH = "{plugin.dataFolder}perms.db";
	
	/** @var Group[] */
	private $groups = [];
	/** @var Group */
	private $defaultGroup;
	/** @var \SQLite3 */
	private $db;
	/** @var PermissionAttachment[] */
	private $attachments = [];
	
	private const INSERT_STATEMENT = "INSERT OR REPLACE INTO master (player, groups, perms, prefix, suffix) VALUES (:player, :groups, :perms, :prefix, :suffix);";
	
	public function __destruct(){
		if($this->db instanceof \SQLite3){
			$this->db->close();
		}
	}
	/**
	 * @param Main $plugin
	 */
	public function init(Main $plugin) : void{
		$this->groups = [];
		$plugin->saveResource("groups.yml", true);
	
		$groups = (new Config($plugin->getDataFolder() . "groups.yml", Config::YAML))->getAll();
		$indexA = 0;
		foreach($groups as $group => $data){
			$inheritance = $data["inheritance"] ?? [];
			foreach($inheritance as $indexB => $groupB){
				if($group === $groupB){
					throw new \LogicException("Cannot inherit the same group");
				}
				if(!isset($groups[$groupB])){
					throw new \UnexpectedValueException("Group " . $groupB . " does not exist");
				}
				if($indexB > $indexA++){
					throw new \LogicException("Cannot inherit a group positioned below this group");
				}
			}
			$chat = $data["chat"] ?? null;
			$nametag = $data["nametag"] ?? null;
			if(!is_string($chat) || trim($chat) === ""){
				$plugin->getLogger()->info("No chat format specified for " . $group . ", using default");
			}
			if(!is_string($nametag) || trim($nametag) === ""){
				$plugin->getLogger()->info("No nametag format specified for " . $group . ", using default");
			}
			$this->groups[] = $obj = new Group($group, $data["permissions"], $inheritance, $data["chat"], $data["nametag"]);
			$default = $data["isDefault"] ?? false;
			if($default){
				if(!is_string($this->defaultGroup)){
					$this->defaultGroup = $obj;
				}else{
					$plugin->getLogger()->warning("There can only be one default group, ignored " . $obj->getName());
				}
			}
		}
		if($this->defaultGroup === null){
			$plugin->getLogger()->error("No valid default group");
			$plugin->getServer()->getPluginManager()->disablePlugin($plugin);
		}
		
		if(file_exists($backup = $this->getDatabasePath() . ".backup")){
			$plugin->getLogger()->warning("Restoring SQLite3 database backup...");
			$bin = file_get_contents($backup);
			if(file_put_contents($this->getDatabasePath(), $bin)){
				unlink($backup);
			}else{
				throw new \RuntimeException("Could not write to file " . $this->getDatabasePath()); 
			}
		}
		
		$this->db = new \SQLite3($this->getDatabasePath());
		$this->db->exec("CREATE TABLE IF NOT EXISTS master (player VARCHAR(16) NOT NULL COLLATE NOCASE PRIMARY KEY, groups TEXT NOT NULL, perms TEXT NOT NULL, prefix TEXT NOT NULL, suffix TEXT NOT NULL);");
	}
	
	public function getGroups() : array{
		return $this->groups;
	}
	
	public function getDatabasePath() : string{
		return str_replace("{plugin.dataFolder}", Main::getInstance()->getDataFolder(), self::DB_PATH);
	}
	
	public function getDefaultGroup() : Group{
		return $this->defaultGroup;
	}
	
	/**
	 * @param IPlayer $player
	 * @param Group|string $group
	 */
	public function setPlayerGroup(IPlayer $player, $group) : void{
		if($group instanceof Group){
			$group = $group->getName();
		}
		$server = Main::getInstance()->getServer();
		if($server->getCommandMap()->getCommand("setgroup") === null){
			throw new \RuntimeException("Setgroup command is not registered"); //PermissionManager::setPlayerGroup() relies in
		//the setgroup command
		}
		$server->dispatchCommand(new ConsoleCommandSender(), "setgroup \"" . $player->getName() . "\" Member");
	}
	
	/**
	 * @param IPlayer $player
	 * @param string $permission
     * @return bool
	 */
	public function addPlayerPermission(IPlayer $player, string $permission) : bool{
		$isNegative = substr($permission, 0, 1) === "-";
		if($isNegative){
			$permission = substr($permission, 1);
		}
		if(!in_array($permission, $permissions = $this->getPlayerPermissions($player))){
			$permissions[] = $permission;
			$stmt = $this->db->prepare(self::INSERT_STATEMENT);
			$stmt->bindValue(":player", $player->getName());
			$stmt->bindValue(":groups", json_encode($this->getPlayerGroups($player, true), JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(":perms", json_encode($permissions, JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(":prefix", $this->getPlayerPrefix($player));
			$stmt->bindValue(":suffix", $this->getPlayerSuffix($player));
			$stmt->execute();
			if($player instanceof Player){
				$this->updatePermissions($player);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param IPlayer $player
	 * @param string $permission
	 * @return bool
	 */
	public function removePlayerPermission(IPlayer $player, string $permission) : bool{
		if(in_array($permission, $permissions = $this->getPlayerPermissions($player))){
			unset($permissions[array_search($permission, $permissions)]);
			$stmt = $this->db->prepare(self::INSERT_STATEMENT);
			$stmt->bindValue(":player", $player->getName());
			$stmt->bindValue(":groups", json_encode($this->getPlayerGroups($player, true), JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(":perms", json_encode($permissions, JSON_UNESCAPED_UNICODE));
			$stmt->bindValue(":prefix", $this->getPlayerPrefix($player));
			$stmt->bindValue(":suffix", $this->getPlayerSuffix($player));
			
			$stmt->execute();
			if($player instanceof Player){
				$this->updatePermissions($player);
			}
			return true;
		}
		return false;
	}
	
	/**
	 * @param IPlayer $player
	 * @return string[]
	 */
	public function getPlayerPermissions(IPlayer $player) : array{
		$result = $this->db->query("SELECT perms FROM master WHERE(player = '" . $player->getName() . "');")->fetchArray(SQLITE3_ASSOC);
		return (array) json_decode($result["perms"] ?? "");
	}
	
	/**
	 * @param IPlayer $player
	 * @param string|Group $group
	 * @return bool
	 * @internal
	 */
	public function addPlayerToGroup(IPlayer $player, $group) : bool{
		if($group instanceof Group){
			$group = $group->getName();
		}
		$oldGroup = $this->getPlayerGroup($player);
		
		$groups = $this->getPlayerGroups($player, true);
		if(in_array($group, $groups)){
			return false;
			throw new \BadMethodCallException($player->getName() . " is already added to group " . $group);
		}
		$groups[] = $group;
		if(!$this->getGroup($group)){
			throw new \InvalidArgumentException("Group " . $group . " does not exist");
		}
		$stmt = $this->db->prepare(self::INSERT_STATEMENT);
		$stmt->bindValue(":player", $player->getName());
		$stmt->bindValue(":groups", json_encode($groups, JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":perms", json_encode($this->getPlayerPermissions($player), JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":prefix", $this->getPlayerPrefix($player));
		$stmt->bindValue(":suffix", $this->getPlayerSuffix($player));
		$stmt->execute();
		if($player instanceof Player){
			$this->updatePermissions($player);
			$player->setNametag($this->getNametag($player));
			
			if($oldGroup !== ($newGroup = $this->getPlayerGroup($player))){
				Main::getInstance()->updateDiscordEntry($player->getName(), "3", $newGroup->getName());
			}
		}
		return true;
	}
	
	/**
	 * @param IPlayer $player
	 * @return string|Group
	 * @return bool
	 * @internal
	 */
	public function removePlayerFromGroup(IPlayer $player, $group) : bool{
		if($group instanceof Group){
			$group = $group->getName();
		}
		$groups = $this->getPlayerGroups($player, true, false); //false to stop recursion
		$i = array_search($group, $groups);
		if($i === false){
			return false;
		}
		unset($groups[$i]);
		$stmt = $this->db->prepare(self::INSERT_STATEMENT);
		$stmt->bindValue(":player", $player->getName());
		$stmt->bindValue(":groups", json_encode($groups, JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":perms", json_encode($this->getPlayerPermissions($player), JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":prefix", $this->getPlayerPrefix($player));
		$stmt->bindValue(":suffix", $this->getPlayerSuffix($player));
		$stmt->execute();
		if($player instanceof Player){
			$this->updatePermissions($player);
		}
		return true;
	}
	
	/**
	 * @param IPlayer $player
	 * @param bool $names
	 * @param bool $removeNonexistingGroups
	 * @return string|Group[]
	 */
	public function getPlayerGroups(IPlayer $player, bool $names = false, bool $removeNonexistingGroups = true) : array{
		$result = $this->db->query("SELECT groups FROM master WHERE player='" . $player->getName() . "';")->fetchArray(SQLITE3_ASSOC);
		$groups = (array) json_decode($result["groups"] ?? "");
		$ret = [];
		foreach($groups as $group){
			$obj = $this->getGroup($group);
			if(!$obj){
				if($removeNonexistingGroups){
					$this->removePlayerFromGroup($player, $group);
				}
			}else{
				$ret[] = $obj;
			}
		}
		if(empty($ret)){
			return [$names ? $this->getDefaultGroup()->getName() : $this->getDefaultGroup()];
		}
		return $names ? array_map(function($group){
			return $group->getName();
		}, $ret) : $ret;
	}
	
	/**
	 * Returns the toppest group the player is in.
	 *
	 * @param IPlayer $player
	 * @return Group
	 */
	public function getPlayerGroup(IPlayer $player) : Group{
		$groups = $this->getPlayerGroups($player);
		usort($groups, function($a, $b) : int{
			$indexA = $indexB = -1;
			foreach($this->groups as $i => $group){
				if($group->getName() === $a->getName()){
					$indexA = $i;
				}
			}
			foreach($this->groups as $i => $group){
				if($group->getName() === $b->getName()){
					$indexB = $i;
				}
			}
			return $indexA <=> $indexB;
		});
		return end($groups);
	}
	
	public function getGroup(string $group) : ?Group{
		foreach($this->groups as $groupB){
			if($groupB->getName() === $group){
				return $groupB;
			}
		}
		return null;
	}
	
	/**
	 * @param IPlayer $player
	 * @return string
	 */
	public function getPlayerPrefix(IPlayer $player) : string{
		$result = $this->db->query("SELECT prefix FROM master WHERE player='" . $player->getName() . "';")->fetchArray(SQLITE3_ASSOC);
		return (string) $result["prefix"];
	}
	
	/**
	 * @param IPlayer $player
	 * @param string $prefix
	 */
	public function setPlayerPrefix(IPlayer $player, string $prefix) : void{
		$stmt = $this->db->prepare(self::INSERT_STATEMENT);
		$stmt->bindValue(":player", $player->getName());
		$stmt->bindValue(":groups", json_encode($this->getPlayerGroups($player, true), JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":perms", json_encode($this->getPlayerPermissions($player), JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":prefix", $prefix);
		$stmt->bindValue(":suffix", $this->getPlayerSuffix($player));
		$stmt->execute();
		if($player instanceof Player){
			$player->setNametag($this->getNametag($player));
		}
	}
	
	/**
	 * @param IPlayer $player
	 * @param string $suffix
	 */
	public function setPlayerSuffix(IPlayer $player, string $suffix) : void{
		$stmt = $this->db->prepare(self::INSERT_STATEMENT);
		$stmt->bindValue(":player", $player->getName());
		$stmt->bindValue(":groups", json_encode($this->getPlayerGroups($player, true), JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":perms", json_encode($this->getPlayerPermissions($player), JSON_UNESCAPED_UNICODE));
		$stmt->bindValue(":prefix", $this->getPlayerPrefix($player));
		$stmt->bindValue(":suffix", $suffix);
		$stmt->execute();
		if($player instanceof Player){
			$player->setNametag($this->getNametag($player));
		}
	}

	/**
	 * @param IPlayer $player
	 * @return string
	 */
	public function getPlayerSuffix(IPlayer $player) : string{
		$result = $this->db->query("SELECT suffix FROM master WHERE player='" . $player->getName() . "';")->fetchArray(SQLITE3_ASSOC);
		return (string) $result["suffix"];
	}
	
	/**
	 * @param Player $player
	 * @param bool $login
	 */
	public function updatePermissions(Player $player, bool $login = false) : void{
		$haystack = [];
		foreach($this->getPlayerGroups($player) as $group){
			$haystack = array_merge($haystack, $group->getPermissions());
		}
		$haystack = array_merge($haystack, $this->getPlayerPermissions($player));
		$permissions = [];
		foreach($haystack as $perm){
			$isNegative = substr($perm, 0, 1) === "-";
			if($isNegative){
				$permissions[substr($perm, 1)] = false;
			}else{
				$permissions[$perm] = true;
			}
		}
		
		$attachment = $this->attachments[$player->getUniqueId()->toString()] ?? null;
		if($attachment === null || $login){
			$attachment = $this->attachments[$player->getUniqueId()->toString()] = $player->addAttachment(Main::getInstance());
		}
		$attachment->clearPermissions();
		$attachment->setPermissions($permissions);
	}
	
	/**
	 * @param Player $player
	 * @param string $format
	 * @return string
	 */
	private function replaceFormatTags(Player $player, string $format) : string{
		$plugin = Main::getInstance();
		$format = str_repLace([
			"{display_name}", "{fac_name}", "{fac_rank}", "{world}", "{prefix}", "{suffix}", "{prestige}", "{ping}"
		], [
			$player->getDisplayName(), $plugin->getPlugin("FactionsPro")->getPlayerFaction($player->getName()),
			$plugin->getPlugin("FactionsPro")->getPlayerRank($player->getName()), $player->getLevel()->getFolderName(),
			$this->getPlayerPrefix($player), $this->getPlayerSuffix($player), $plugin->getEntry($player, Main::ENTRY_PRESTIGE),
			number_format($player->getPing())
		], $format);
		return $format;
	}
	
	/**
	 * @param Player $player
	 * @return string
	 */
	public function getNametag(Player $player) : string{
		return TextFormat::colorize($this->replaceFormatTags($player, $this->getPlayerGroup($player)->getNametagFormat()));
	}
	
	/**
	 * @param Player $player
	 * @param string $msg
	 * @return string
	 */
	public function getChatFormat(Player $player, string $msg) : string{
		return TextFormat::colorize(str_replace("{msg}", $player->hasPermission("core.coloredMessages") ? $msg : TextFormat::clean($msg), $this->replaceFormatTags($player, $this->getPlayerGroup($player)->getChatFormat())));
	}
	
}