<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main;
use kenygamer\Core\LangManager;

class TopMoneyCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"topmoney",
			"Shows top money of this server",
			"/topmoney [page]",
			[],
			BaseCommand::EXECUTOR_ALL,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		$cache = $this->getPlugin()->getTopMoney();
		$pages = (int) ceil(count($cache) / Main::TOPMONEY_PER_PAGE_LIMIT);
		$page = array_shift($args);
		$page = $page > $pages ? $pages : ($page < 1 ? 1 : $page);
		$msg[] = LangManager::translate("topmoney-tag", $sender, $page, $pages);
		$start = ($page - 1) * Main::TOPMONEY_PER_PAGE_LIMIT;
		$i = 0;
		foreach($cache as $player => $money){
			$i++;
			if($i - 1 < $start){
				continue;
			}
			if(count($msg) - 1 === 5){
				break;
			}
			$msg[] = LangManager::translate("topmoney-format", $sender, $i, $player, number_format((float) $money));
		}
		$sender->sendMessage(implode(PHP_EOL, $msg));
		return true;
	}
}