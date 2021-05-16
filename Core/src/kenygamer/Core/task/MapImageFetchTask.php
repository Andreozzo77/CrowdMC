<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\AsyncTask;
use pocketmine\Server;
use pocketmine\utils\Color;

use kenygamer\Core\map\MapFactory;
use kenygamer\Core\util\ImageUtils;

final class MapImageFetchTask extends AsyncTask{
	/** @var string */
	protected $files;

	/**
	 * @param array $files
	 */
	public function __construct(array $files){
		$this->files = serialize($files);
	}

	public function onRun() : void{
		$files = unserialize($this->files);
		$resources = [];
		foreach($files as $id => $file){
			$resources[$id] = ImageUtils::imageToColorArray($this->fetch($file));
		}
		$this->setResult($resources);
	}

	/**
	 * @param Server $server
	 */
	public function onCompletion(Server $server) : void{
		$results = $this->getResult();
		$factory = MapFactory::getInstance();
		foreach($results as $id => $result){
			$factory->updateColors($id, $result);
		}
	}

	/**
	 * @param string $png
	 * @return resource|null
	 */
	private function fetch(string $png){
		if(!file_exists($png) || pathinfo($png, PATHINFO_EXTENSION) !== "png"){
			return null;
		}
		$image = @imagecreatefrompng($png);
		if($image === false){
			return null;
		}
		return $image;
	}
	
}