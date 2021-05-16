<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use pocketmine\Server;

/**
 * @class SQLiteConfig
 * This class is used to manipulate SQLite classes as if they were Config files.
 * - Mutable: can change the table's name at any time.
 * - In-memory: database is in-memory and is saved to disk with self::save() or when switching tables.
 */
class SQLiteConfig{
	/** @var string */
	private $path, $table;
	
	/** @var array Current table data loaded as array */
	private $data = [];
	
	/** @var bool */
	private $saved = true;
	
	public function __construct(string $path, string $table, array $default = []){
		$this->setPath($path);
		$this->setTable($table);
		$this->fillDefaults($default);
		
	}
	
	private function fillDefaults(array $default) : void{
		foreach($default as $key => $value){
			if(is_array($value)){
				if(!isset($this->data[$key]) or !is_array($this->data[$key])){
					$this->data[$key] = [];
				}
				$this->fillDefaults($value, $this->data[$key]);
			}elseif(!isset($this->data[$key])){
				$this->data[$key] = $value;
			}
		}
	}
	
	private function setPath(string $path) : void{
		$ext = pathinfo($path, PATHINFO_EXTENSION);
		if($ext === false || $ext !== "db"){
			throw new \InvalidArgumentException("File extension must be .db, " . $ext . " provided");
		}
		$this->path = $path;
	}
	
	/**
	 * Switch the current table to $table.
	 *
	 */
	public function setTable(string $table) : void{
		if(!$this->saved){
			$this->save();
		}
		if(!preg_match('/^[a-zA-Z0-9_]+$/', $table)){
			throw new \InvalidArgumentException("Table name must be alphanumeric, " . $table . " given");
		}
		$this->table = $table;
		$this->saved = false;
		$this->load();
	}
	
	public function setNested($key, $value) : void{
		$vars = explode(".", $key);
		$base = array_shift($vars);

		if(!isset($this->data[$base]) || !is_array($this->data[$base])){
			$this->data[$base] = [];
		}

		$base =& $this->data[$base];
		
		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(!isset($base[$baseKey])){
				$base[$baseKey] = [];
			}
			$base =& $base[$baseKey];
		}

		$base = $value;
	}
	
	public function getNested($key, $default = null){
		$vars = explode(".", $key);
		$base = array_shift($vars);
		if(isset($this->data[$base])){
			$base = $this->data[$base];
		}else{
			return $default;
		}

		while(count($vars) > 0){
			$baseKey = array_shift($vars);
			if(is_array($base) and isset($base[$baseKey])){
				$base = $base[$baseKey];
			}else{
				return $default;
			}
		}
		return $base;
	}
	
	public function removeNested($key){
		$nodes = explode(".", $key);
		$currentNode =& $this->data;
		while(count($nodes) > 0){
			$nodeName = array_shift($nodes);
			if(isset($currentNode[$nodeName])){
				if(count($vars) === 0){
					unset($currentNode[$nodeName]);
				}elseif(is_array($currentNode[$nodeName])){
					$currentNode =& $currentNode[$nodeName];
				}
			}else{
				break;
			}
		}
	}
	
	public function get($key, $default = false){
		return $this->data[$key] ?? $default;
	}
	
	public function setAll(array $data) : void{
		$this->data = $data;
	}
	
	public function getAll(bool $keys = false) : array{
		return $this->data;
	}
	
	public function set($key, $value = true){
		$this->data[$key] = $value;
	}
	
	public function exists($key, bool $lowercase = false) : bool{
		if($lowercase){
			$key = strtolower($key);
		}
		return isset($this->data[$key]);
	}
	
	public function remove($key) : void{
		unset($this->data[$key]);
	}
	
	//TODO: Find out why SQLite3 data is corrupted
	public function getFullPath() : string{
		return $this->path . "_" . $this->table;
	}
	
	public function getDatabaseData(\SQLite3 &$db = null) : array{
		
		if(!file_exists($this->getFullPath())){
			touch($this->getFullPath());
		}
		return (array) json_decode(file_get_contents($this->getFullPath()), true);
		
		$db = new \SQLite3($this->path);
		if(!$this->tableExists($db, $this->table)){
			return [];
		}
		
		$result = $db->query("SELECT * FROM '" . $this->table . "';");
		$data = $result->fetchArray(SQLITE3_ASSOC);
		if($data === false){
			//TODO: ~~race~~ strange condition. need to investigate
			$data = [];
		}
		$values = array_values($data);
		
		$values = array_map(function($value){
				return !is_int($value) && !is_bool($value) ? json_decode($value, true) : $value;
		}, $values);
		$ret = [];
		foreach(array_keys($data) as $i => $key){
			$ret[$key] = $values[$i];
		}
		return $ret;
	}
	
	public function load() : void{
		if(!file_exists($this->getFullPath())){
			touch($this->getFullPath());
		}
		$this->data = (array) json_decode(file_get_contents($this->getFullPath()), true);
		return;
		
		if(Server::getInstance() !== null){
			Server::getInstance()->getLogger()->debug("Loading database " . $this->path . "...");
		}
		$this->data = [];
		$tables = 0;
		if(file_exists($this->path)){
			if(filesize($this->path) < 1){
				@unlink($this->path);
				Server::getInstance()->getLogger()->error(__METHOD__ . ": Database " . $this->path . " is truncated");
				return;
			}
			
			$tableWithoutSuffix = $this->table;
			$this->data = $this->getDatabaseData($db);
			if(count($this->data)){
				$tables++;
				$i = 2;
				while($this->tableExists($db, $this->table = $tableWithoutSuffix . "_" . $i++)){
					$this->data = $this->data + $this->getDatabaseData($db);
					$tables++;
				}
			}
			$this->table = $tableWithoutSuffix;
		}
		if(Server::getInstance() !== null){
			Server::getInstance()->getLogger()->debug("Loaded " . count($this->data) . " columns on " . $tables . " tables");
		}
	}
	
	public function tableExists(\SQLite3 $db, string $table) : bool{
		//sqlite_master system table lists all database objects in the database and the SQL used to create each object
		$exists = $db->query("SELECT count(*) FROM sqlite_master WHERE type='table' AND name='" . $table . "'");
		$exists = $exists->fetchArray(SQLITE3_ASSOC)["count(*)"];
		return $exists > 0;
	}
	
	public function save() : void{
		file_put_contents($this->getFullPath(), json_encode($this->data));
		return;
		
		if(Server::getInstance() !== null){
			Server::getInstance()->getLogger()->debug("Saving database " . $this->path . "...");
		}
		$db = new \SQLite3($this->path);
		
		$tableWithoutSuffix = $this->table;
		$i = 2;
		$chunks = array_chunk($this->data, 2000, true);
		foreach($chunks as $chunk){
			$currentI = $i++;
			if(count($chunks) > 1){
				$this->table = $tableWithoutSuffix . "_" . $currentI;
			}
			$temp_table = $this->table . "_Temp";
			if($this->tableExists($db, $temp_table)){
				$db->exec("DROP TABLE IF EXISTS '" . $this->table . "';");
				$db->exec("ALTER TABLE " . $temp_table . " RENAME TO '" . $this->table . "';");
			}
			//id INTEGER NOT NULL PRIMARY KEY AUTOINCREMENT (stored by sqlite_sequence system table)
			$keys = array_map(function($key){
			return "'" . str_replace("'", "\''", $key) . "'";
			}, array_keys($chunk));
			$db->exec($query = "CREATE TABLE '" . $temp_table . "' (" . implode(" INTEGER NOT NULL, ", $keys) . " INTEGER NOT NULL);");
		
			$values = array_values($chunk);
			$data = [];
			foreach(array_keys($chunk) as $i => $key){
				$value = $values[$i];
				$value = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value; //Key is column
				if($value === null){
					continue;
				}
				$data[$key] = $value; //TODO: drop column. There is no built-in way so it's tedious asf
			}
			$count = count($data);
			if($count < 1){
				$db->exec("DROP TABLE IF EXISTS '" . $this->table . "';");
				$db->exec("DROP TABLE IF EXISTS '" . $temp_table . "';");
				return;
			}
			$values = "";
			$values_ = array_values($data); 
			foreach($values_ as $i2 => $value){;
				$values .= ":" . $i2 . ($i2 === $count - 1 ? "" : ", ");
			}
			
			$keys = "";
			$keys_ = array_keys($data); 
			foreach($keys_ as $i3 => $key){;
				$keys .= "'" . $key . "'" . ($i3 === $count - 1 ? "" : ", ");
			}
			
			$query = "INSERT OR REPLACE INTO '" . $temp_table . "' (" . $keys . ") VALUES (" . $values . ");";
			//var_dump($query);
			$stmt = $db->prepare($query);
			foreach($values_ as $i => $value){
				$stmt->bindValue(":" . $i, $value);
			}
			$stmt->execute();
		
			$db->exec("DROP TABLE IF EXISTS '" . $this->table . "';");
			$db->exec("ALTER TABLE '" . $temp_table . "' RENAME TO '" . $this->table . "';");
		}
		$db->close();
		$this->saved = true;
		if(Server::getInstance() !== null){
			Server::getInstance()->getLogger()->debug("Saving successful.");
		}
	}
	
}