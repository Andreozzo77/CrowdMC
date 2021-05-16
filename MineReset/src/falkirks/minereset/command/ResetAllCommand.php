<?php
namespace falkirks\minereset\command;


use falkirks\minereset\Mine;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

use kenygamer\Core\LangManager;

class ResetAllCommand extends SubCommand{
    public function execute(CommandSender $sender, $commandLabel, array $args){
        if($sender->hasPermission("minereset.command.resetall")) {
            $success = 0;
            foreach ($this->getApi()->getMineManager() as $mine) {
                if ($mine instanceof Mine) {
                    if ($mine->reset()) {
                        $success++;
                    }
                }
            }
            $count = count($this->getApi()->getMineManager());
            LangManager::broadcast("minereset-all");
        }
        else{
            $sender->sendMessage(TextFormat::RED . "You do not have permission to run this command." . TextFormat::RESET);
        }
    }
}