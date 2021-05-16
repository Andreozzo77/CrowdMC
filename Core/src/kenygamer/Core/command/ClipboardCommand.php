<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\Main2;
use kenygamer\Core\clipboard\ClipboardManager;
use pocketmine\utils\TextFormat;

class ClipboardCommand extends BaseCommand{
	
	public function __construct(){
		parent::__construct(
			"clipboard",
			"Tools for working with clipboards",
			"/clipboards <pos1/pos2/create/list/delete>",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		try{
	    	$manager = ClipboardManager::getInstance();
	    	switch(array_shift($args)){
	    		case "pos1":
	    		    $manager->setSessionVar($sender, "pos1", $sender->asVector3());
	    		    $sender->sendMessage("pos1", $sender->asVector3()->__toString());
	    		    break;
	    		case "pos2":
	    		    $manager->setSessionVar($sender, "pos2", $sender->asVector3());
	    		    $sender->sendMessage("pos2", $sender->asVector3()->__toString());
	    		    break;
	    		case "create":
	    		    $pos1 = $manager->getSessionVar($sender, "pos1");
	    		    $pos2 = $manager->getSessionVar($sender, "pos2");
	    		    if($pos1 === null || $pos2 === null){
	    		    	$sender->sendMessage("pos-setall");
	    		    	break;
	    		    }
	    		    $name = array_shift($args);
	    		    if($name === null || trim($name) === ""){
	    		    	$sender->sendMessage("clipboard-create");
	    		    	break;
	    		    }
	    		    $manager->createClipboard($pos1, $pos2, $sender->getLevel(), $name, ClipboardManager::parseFlags($args), function() use($sender, $name){
	    		    	$sender->sendMessage("clipboard-created", $name);
	    		    });
	    		    $sender->sendMessage("clipboard-creating");
	    		    break;
	    		case "paste":
	    		    $name = array_shift($args);
	    		    if($name === null || trim($name) === ""){
	    		    	$sender->sendMessage("clipboard-paste");
	    		    	break;
	    		    }
	    		    $manager->pasteClipboard($sender->asPosition(), $name, ClipboardManager::parseFlags($args), [Main2::class, "pasteCallback", $sender->getName()]);
	    		    $sender->sendMessage("clipboard-pasting");
					break;
	    		case "delete":
	    		    $name = array_shift($args);
	    		    if($name === null || trim($name) === ""){
	    		    	$sender->sendMessage("clipboard-delete");
	    		    	break;
	    		    }
	    		    if(in_array($name, $manager->getAllClipboards())){
	    		    	$manager->deleteClipboard($name);
	    		    	$sender->sendMessage("clipboard-deleted", $name);
	    		    }else{
	    		    	$sender->sendMessage("clipboard-notfound", $name);
	    		    }
					break;
	    		case "list":
	    		    $clipboards = $manager->getAllClipboards();
	    		    $list = [];
	    		    foreach($clipboards as $clipboard){
	    		    	$list[] = $clipboard . " (" . round(intval(exec("du -s -B1 " . $manager->getPath() . $clipboard)) / 1024 / 1024, 2) . " MB)";
	    		    }
	    		    $sender->sendMessage("clipboard-list", implode(", ", $list));
	    		    break;
	    	}
	    }catch(ClipboardException $e){
	    	$sender->sendMessage(TextFormat::RED . $e->getMessage());
	    }
	    return true;
	}
	
}