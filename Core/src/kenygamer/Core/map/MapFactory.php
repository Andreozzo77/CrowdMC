<?php

declare(strict_types=1);

namespace kenygamer\Core\map;

use kenygamer\Core\Main;
use kenygamer\Core\map\MapRenderer;
use kenygamer\Core\task\MapImageFetchTask;
use kenygamer\Core\util\ImageUtils;
use pocketmine\utils\Config;

final class MapFactory{
	/** @var MapFactory|null */
	private static $instance = null;
	/** @var int */
	private $id = 0;
	/** @var MapData[] */
	private $mapData = [];
	
	public function __construct(){
		self::$instance = $this;
		$this->fetchAsyncTask();
	}
	
	/**
	 * @return self|null
	 */
	public static function getInstance() : ?self{
		return self::$instance;
	}

	public function fetchAsyncTask() : void{
		$plugin = Main::getInstance();
		$files = [];
		foreach($plugin->recursiveSearch($plugin->getDataFolder() . "images") as $file){
			$fname = pathinfo($file, PATHINFO_FILENAME);
			if(is_numeric($fnane)){
				$files[(int) $fileName] = $file;
			}
		}
		$plugin->getServer()->getAsyncPool()->submitTask(new MapImageFetchTask($files));
		foreach($plugin->recursiveSearch($plugin->getDataFolder() . "data") as $file){
			$info = pathinfo($file);
			if($info["extension"] === "json" && is_numeric($info["filename"])){
				$data = json_decode(file_get_contents($file), true);
					list($x, $y, $z) = explode(":", $data["center"]);
				$mapData = new MapData($data["id"], [], $data["displayPlayers"], new Vector3((float) $x, (float) $y, (float) $z));
				$this->mapData[$mapData->getMapId()] = $mapData;
			}
		}
		$this->id = $plugin->generics->get("mapId", $this->id);
	}

	public function save() : void{
		$plugin = Main::getInstance();
		$plugin->generics->set("mapId", $this->id);
		$plugin->getLogger()->notice("Saving images...");
		$start = microtime(true);
		foreach($this->mapData as $id => $data){
			if(!file_exists($file = $plugin->getDataFolder() . "images/" . $id . ".png")){
				$img = ImageUtils::colorArrayToImage($data->getColors());
				@imagepng($file, $img);
				@fclose($img);
			}
			if(!file_exists($file = $plugin->getDataFolder() . "data/" . $id . ".json")){
				file_put_contents($file, json_encode($data));
			}
		}
		$plugin->getLogger()->notice("Image save successful (took " . round(microtime(true) - $start, 2) . " ms)");
	}

	/**
	 * @param MapData $data
	 */
	public function registerData(MapData $data) : void{
		$this->mapData[$data->getMapId()] = $data;
	}

	/**
	 * @param int $id
	 * @param Color[] $colors
	 */
	public function updateColors(int $id, array $colors) : void{
		if(!isset($this->mapData[$id])){
			return;
		}
		$data = $this->mapData[$id];
		$data->setColors($colors);
	}

	/**
	 * @param int $mapId
	 * @return MapData|null
	 */
	public function getMapData(int $mapId) : ?MapData{
		return $this->mapData[$mapId] ?? null;
	}

	/**
	 * @return int
	 */
	public function nextId() : int{
		$this->id += 1;
		return $this->id;
	}
}