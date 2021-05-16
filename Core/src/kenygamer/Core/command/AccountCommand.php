<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use kenygamer\Core\account\AccountGroup;
use kenygamer\Core\LangManager;
use kenygamer\Core\listener\MiscListener2;
use pocketmine\Player;
use pocketmine\IPlayer;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use LegacyCore\Core;

class AccountCommand extends BaseCommand{
	/** @var string[] */
	private $groupRequests = [];
	
	public function __construct(){
		parent::__construct(
			"account",
			"Switch between accounts without logging out Xbox Live",
			"/account",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
 		$group = $this->getPlugin()->getAccountGroup($sender->getName());
 		if(!isset($args[0])){
 			$form = new SimpleForm(function(Player $player, ?string $action){
 				if(is_string($action)){
 					$player->getServer()->dispatchCommand($player, "account {$action}");
 				}
 			});
 			$form->setTitle(LangManager::translate("account-title", $sender));
 			$form->setContent(LangManager::translate("account-desc", $sender));
 			if($group instanceof AccountGroup){
 				$form->addButton(LangManager::translate("account-manage", $sender), -1, "", "manage");
 			}else{
 				$form->addButton(LangManager::translate("account-group", $sender), -1, "", "group");
 			}
 			$sender->sendForm($form);
 		}else{
 			switch($args[0]){
 				case "manage":
 				    if($group instanceof AccountGroup){
 				    	$accounts = $group->exempt($sender->getName());
 				    	if(isset($args[1]) && $group->contains($account = $args[1])){
 				    		$form = new SimpleForm(function(Player $player, ?string $action) use($account, $group){
 				    			if(is_string($action) && ($action === "switch" xor $action === "ungroup")){
 				    				if($action === "switch"){
 				    				    MiscListener2::$switcher[$player->getUniqueId()->toString()] = $account;
										$player->sendMessage("account-login", $account);
 				    				    $player->transfer($this->getPlugin()->serverIp, $player->getServer()->getPort());
 				    				}else{
 				    					$g = $this->getPlugin()->getAccountGroup($account);
 				    					if($g instanceof AccountGroup && $g === $group){
 				    						$group->removeUsername($account);
 				    						$p = $player->getServer()->getPlayerExact($account);
 				    						$p_uniqueId = array_search($account, MiscListener2::$switcher);
 				    						if($p_uniqueId !== false){
 				    							unset(MiscListener2::$switcher[$p_uniqueId]);
 				    							if($p !== null){
 				    								$this->transfer($p);
 				    							}
 				    						}
 				    						unset(MiscListener2::$switcher[$player->getUniqueId()->toString()]);
 				    						if(count($group->getUsernames()) < 2){
 				    							$this->getPlugin()->removeAccountGroup($group);
 				    						}
											$player->sendMessage("account-ungrouped", $account);
 				    					}
 				    				}
 				    			}else{
 				    				$player->getServer()->dispatchCommand($player, "account manage");
 				    			}
 				    		});
 				    		$form->setTitle(LangManager::translate("account-title", $sender));
 				    		$form->setContent(LangManager::translate("account-managing", $sender, $account));
 				    		$form->addButton(LangManager::translate("account-switch", $sender), -1, "", "switch");
 				    		$form->addButton(LangManager::translate("account-ungroup", $sender), -1, "", "ungroup");
 				    		$form->sendToPlayer($sender);
 				    		break;
 				    	}
 				    	
 				    	$form = new SimpleForm(function(Player $player, ?string $account){
 				    		if(is_string($account)){
 				    			$player->getServer()->dispatchCommand($player, "account manage {$account}");
 				    		}else{
 				    			$player->getServer()->dispatchCommand($player, "account");
 				    		}
 				    	});
 				    	$form->setTitle(LangManager::translate("account-title", $sender));
 				    	$form->setContent(LangManager::translate("account-manage-choose", $sender));
 				    	if(empty($accounts)){
 				    		$this->getPlugin()->removeAccountGroup($group);
 				    		$sender->getServer()->dispatchCommand($sender, "account");
 				    	}else{
 				    		foreach($accounts as $account){
 				    			$form->addButton(LangManager::translate("account-account", $sender, $account), -1, "", $account);
 				    		}
 				    		$sender->sendForm($form);
 				    	}
 				    }
 				    break;
 			    case "group":
 			        if(isset($args[1])){
 			        	$requester = mb_strtolower($args[1]);
 			        	foreach($this->groupRequests as $req => $receiver){
 			        		if($receiver === $sender->getName()){
 			        			unset($this->groupRequests[$req]);
 			        			$g = $this->getPlugin()->getAccountGroup($req);
 			        			if($g instanceof AccountGroup){
 			        				if(!$g->addUsername($sender->getName())){
										$sender->sendMessage("account-group-failed");
 			        					break 2;
 			        				}
 			        			}else{
 			        				$this->getPlugin()->createAccountGroup($req, $receiver);
 			        			}
								$sender->sendMessage("account-grouped");
 			        			break 2;
 			        		}
 			        	}
						$sender->sendMessage("account-unauthorized", $requester);
 			        }
 			        if(!$group instanceof AccountGroup){
 			        	$form = new CustomForm(function(Player $player, $data){
 			        		if(is_array($data) && isset($data[2]) && is_string($username = $data[2])){
 			        			$p = $player->getServer()->getOfflinePlayer($username);
 			        			if($p instanceof IPlayer){
 			        				if(strcasecmp($p->getName(), $player->getName()) === 0){
										$player->sendMessage("account-other");
 			        				}else{
 			        					$this->groupRequests[$player->getName()] = $username;
 			        					LangManager::send("account-finish", $player, $username, $player->getName());
 			        				}
 			        			}
 			        		}else{
 			        			$player->getServer()->dispatchCommand($player, "account");
 			        		}
 			        	});
 			        	$form->setTitle(LangManager::translate("account-title", $sender));
 			        	$form->addLabel(LangManager::translate("account-disclaimer", $sender));
 			        	$form->addLabel(LangManager::translate("account-enterusername", $sender));
 			        	$form->addInput(LangManager::translate("account-username", $sender));
 			        	$sender->sendForm($form);
 			        }
 			        break;
 			    default:
 			        return false;
 			}
 		}
 		return true;
 	}
	
}