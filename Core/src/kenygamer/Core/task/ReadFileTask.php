<?php

declare(strict_types=1);

namespace kenygamer\Core\task;

use pocketmine\scheduler\Task;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

use kenygamer\Core\Main;

final class ReadFileTask extends Task{
	/** @var Player */
	private $player;
	/** @var string */
	private $path;
	/** @var int */
	private $mode;
	/** @var bool */
	private $truncate;
	/** @vae int */
	private $stripLen;
	
	/** @var string[] */
	private $sentLines = [];
	/** @var int */
	private $index = 0;
	
	public const READ_MODE_LIVE = 0;
	public const READ_MODE_CONTAIN = 1;
	
	public function __construct(Player $player, string $path, int $mode, bool $truncate, int $stripLen){
		$this->player = $player;
		$this->path = $path;
		$this->mode = $mode;
		$this->truncate = $truncate;
		$this->stripLen = $stripLen;
		$this->setFileReadMode(true);
	}
	
	private function cancelTask() : void{
		Main::getInstance()->getScheduler()->cancelTask($this->getTaskId());
	}
	
	/**
	 * @param bool $mode
	 */
	private function setFileReadMode(bool $mode) : void{
		if(Main::getInstance() instanceof Main){
			if($mode){
				Main::getInstance()->fileReadMode[] = $this->player->getName();
			}else{
				unset(Main::getInstance()->fileReadMode[array_search($this->player->getName(), Main::getInstance()->fileReadMode)]);
			}
		}
	}
	
	/**
	 * Returns last $lines lines of the file.
	 * @param int $lines
	 * @return string[]
	 */
	private function fetchLastLines(int $lines) : array{
		try{
			$file = new \SplFileObject($this->path, "r");
		}catch(\RuntimeException $e){
			$this->cancelTask();
			return [];
	    }
	    $file->setFlags(\SplFileObject::SKIP_EMPTY | \SplFileObject::READ_AHEAD);
	    $file->seek(PHP_INT_MAX);
	    $last_line = $file->key();
	    $lines = new \LimitIterator($file, max(0, $last_line - $lines), $last_line);
	    $arr = iterator_to_array($lines);
	    foreach($arr as $i => $line){
	    	$arr[$i] = substr($line, $this->stripLen);
	    }
	    return $arr;
	}
	
	private function sendLines(array $lines) : void{
		$this->setFileReadMode(false);
		$output = "";
		foreach($lines as $i => $line){
			$ln = str_replace(["\r", "\n"], "", $line); //clean line
			
			$output .= TextFormat::DARK_PURPLE . "•" . TextFormat::WHITE;
			if(strlen($ln) > 81){
				$parts = str_split($ln, 81);
				if(count($parts) > 2){ //longer than 162 chars!
				    $output .= "..." . substr($parts[$this->index], 0, 159) . "..."; //shorten to 159ch to add ellipsis to end
				}else{
					$output .= "..." . $parts[$this->index];
				}
			}else{
				$output .= $ln;
			}
			$output = $output . ($line !== end($lines) ? "\n" : "");
		}
		$this->player->sendMessage($output);
		$this->setFileReadMode(true);
		
		if(++$this->index > 1){
			$this->index = 0;
		}
	}
	
	public function onRun(int $currentTick) : void{
		if(!$this->player->isOnline()){
			$this->cancelTask();
			return;
		}
		if($this->mode === self::READ_MODE_CONTAIN){
			$lines = $this->fetchLastLines(500);
			$this->sendLines($lines);
			$this->cancelTask();
		}elseif($this->mode === self::READ_MODE_LIVE){
			$lines = array_values($this->fetchLastLines(19)); 
			$send = [];
			foreach($lines as $line){
				if(!in_array($line, $this->sentLines)){
					$send[] = $line;
					$this->sentLines[] = $line;
				}
			}
			$this->sentLines = array_slice($this->sentLines, 0, -19);
			if(count($send) > 0){
				$this->sendLines($send);
			}
		}
	}
	
	public function onCancel() : void{
		$this->setFileReadMode(false);
		unset(Main::getInstance()->readingFile[$this->player->getName()]);
		if($this->truncate){
			@fclose(@fopen($this->path, "w"));
		}
	}
	
}