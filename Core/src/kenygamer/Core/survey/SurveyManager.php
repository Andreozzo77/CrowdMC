<?php

declare(strict_types=1);

namespace kenygamer\Core\survey;

use pocketmine\permission\{
	Permission,
	PermissionManager
};
use pocketmine\utils\{
	Config, TextFormat
};
use kenygamer\Core\Main;
use kenygamer\Core\util\SQLiteConfig;

use function is_string;
use function trim;
use function preg_match;
use function count;
use function intval;
use function boolval;
use function strval;
use function ctype_alnum;
use function array_shift;
use function array_keys;
use function str_repeat;

class SurveyManager{
	/** @var Main */
	private $plugin;
	/** @var Config */
	private $config;
	/** @var Survey[] */
	private $surveys = [];
	/** @var bool */
	private static $init = false;
	
	/**
	 * @param Main $plugin
	 */
	public function __construct(Main $plugin){
		if(($surveyManager = $plugin->getSurveyManager()) instanceof SurveyManager && $surveyManager::isInit()){
			throw new \BadMethodCallException("Survey manager is already initialized");
		}
		$this->config = new SQLiteConfig($plugin->getDataFolder() . "server.db", "surveys");
		$all = $this->config->getAll();
		
		foreach($plugin->getConfig()->get("surveys") as $name => $settings){
			if(!is_string($name) || trim($name) !== $name || !ctype_alnum($name)){
				throw new \RuntimeException("Survey name must only contain letters and numbers");
			}
			if(!is_array($settings) || count($settings) < 3 || !isset($settings["expiry"]) || !isset($settings["vote-stats"]) || !isset($settings["form"]) || !is_array($formData = $settings["form"]) || count($formData) < 4 || !isset($formData["button1"]) || empty($formData["button1"]) || !isset($formData["button2"]) || empty($formData["button2"]) || !isset($formData["title"]) || !isset($formData["content"])){
				throw new \RuntimeException("Survey data could not be parsed due to missing fields");
			}
			if($this->getSurvey($name) !== null){
				throw new \RuntimeException("A survey with that name already exists");
			}
			$content = $formData["content"];
			if(is_array($content) && count($content) > 0){
				$c = array_shift($content);
				foreach($content as $i => $msg){
					$c .= str_repeat(TextFormat::EOL, 2) . $msg;
				}
			}elseif(is_string($content)){
				$c = $content;
			}else{
				$c = "";
			}
			$formData["button1"] = !is_string($formData["button1"]) ? " " : $formData["button1"];
			$formData["button2"] = !is_string($formData["button2"]) ? " " : $formData["button2"];
			$formData["title"] = strval($formData["title"]);
			$formData["content"] = $c;
			$this->surveys[] = new Survey($name, intval($settings["expiry"]), boolval($settings["vote-stats"]), $formData, $all[$name] ?? []);
			
			$perm = new Permission("core.survey." . $name, "Allows access to vote on survey " . $name . " we are conducting.", Permission::DEFAULT_TRUE);
			PermissionManager::getInstance()->addPermission($perm);
		}
		$this->logCleanup();
		self::$init = true;
	}
	
	/**
	 * @return bool
	 */
	public static function isInit() : bool{
		return self::$init;
	}
	
	private function logCleanup() : void{
		foreach(array_keys($this->config->getAll()) as $name){
			if($this->getSurvey($name) === null){
				$this->config->remove($name);
			}
		}
	}
	
	/**
	 * @api
	 * @return Survey[]
	 */
	public function getSurveys() : array{
		return $this->surveys;
	}
	
	/**
	 * @api
	 * @param string $name
	 * @return Survey|null
	 */
	public function getSurvey(string $name) : ?Survey{
		foreach($this->surveys as $survey){
			if($survey->getName() === $name){
				return $survey;
			}
		}
		return null;
	}
	
	/**
	 * @api
	 * @return bool
	 */
	public function saveSurveys() : void{
		$surveys = [];
		foreach($this->surveys as $survey){
			$surveys[$survey->getName()] = $survey->votes;
		}
		$this->config->setAll($surveys);
		$this->config->save();
	}
	
}