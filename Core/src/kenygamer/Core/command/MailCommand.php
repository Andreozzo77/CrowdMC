<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\Player;
use pocketmine\utils\TextFormat;
use kenygamer\Core\Main;
use kenygamer\Core\LangManager;
use jojoe77777\FormAPI\CustomForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\ModalForm;

class MailCommand extends BaseCommand{
	private static $MESSAGE_FORMAT = "&bFrom: &f%s\n&eWhen: &f%s\n&dMessage:\n&f%s\n&f=-=-=-=-=-=-=-=-=-=-=-=-=-=-=";
	/**
	 * Indicators for who sent a message regarding a particular mailbox.
	 * Eventually it is convenient to call the sender the owner of the mailbox.
	 */
	private const MESSAGE_WHO_SENDER = 0;
	private const MESSAGE_WHO_RECIPIENT = 1;
	
	/**
	 * Indicators for read receipts. Placed in the messages, it can be in more than one.
	 */
	private const MAILBOX_UNREAD = -1;
	private const MAILBOX_READ = 0;
	
	public function __construct(){
		parent::__construct(
			"mail",
			"Mail Command",
			"/mail /write/read/clear/clear-all",
			[],
			BaseCommand::EXECUTOR_PLAYER,
			"op" //This is intentional
		);
	}
	
	/**
	 * @param Player $player
	 * @param string $cmd Command without slash.
	 */
	private function run(Player $player, string $cmd) : void{
		if($this->defaultPermission === "op"){
			$player->getServer()->dispatchCommand($player, $cmd);
		}else{
			$player->chat("/" . $cmd);
		}
	}
	
	/**
	 * Code reutilization.
	 * @param Player $player
	 * @param string $target
	 */
	private function createMailbox(Player $player, string $target) : void{
		$mail = $this->getPlugin()->mail->get(mb_strtolower($player->getName()), []);
		if(!isset($mail[mb_strtolower($target)])){
			$mail[mb_strtolower($target)] = [];
		}
		$this->getPlugin()->mail->set(mb_strtolower($player->getName()), $mail);
		$this->runCommand($player, "mail read " . $target);
	}
	
	/**
	 * @param Player|string $player
	 * @param Player|string $target
	 * @return array 
	 */
	private function getChatRoom($player, $target = null) : array{
		if($player instanceof Player){
			$player = $player->getName();
		}
		if($target instanceof Player){
			$target = $target->getName();
		}
		$chatrooms = $this->getPlugin()->mail->get(mb_strtolower($player), []);
		if($target === null){
			return $chatrooms;
		}
		return $chatrooms[mb_strtolower($target)] ?? [];
	}
	
	/**
	 * Pushes a message to mailboxes where $player is the sender and $target is the recipient.
	 *
	 * @param Player $player
	 * @param string $target
	 * @param string $message
	 */
	private function pushToMailbox(Player $player, string $target, string $message) : void{
		$senderMailbox = $this->getChatRoom($player, $target);
		$recipientMailbox = $this->getChatRoom($target, $player);
		
		$recipientMailbox[] = [
			"time" => time(),
			"who" => self::MESSAGE_WHO_RECIPIENT,
			"message" => $message,
			"read" => false
		];
		if(count($recipientMailbox) > 100){
			array_shift($recipientMailbox);
		}
		$recipient = $this->getPlugin()->getServer()->getPlayerExact($target);
		if($recipient !== null){
			$recipient->addTitle(TextFormat::colorize("&eNew Message"), TextFormat::colorize("&fFrom " . $player->getName() . " (&b/mail read&f)"), 15, 15, 15);
		}
		
		$senderMailbox[] = [
			"time" => time(),
			"who" => self::MESSAGE_WHO_SENDER,
			"message" => $message,
			"read" => true //Kinda redundant - Sender always sees its own messages
		];
		if(count($senderMailbox) > 100){
			array_shift($senderMailbox);
		}
		
		$this->getPlugin()->mail->setNested(mb_strtolower($player->getName()) . "." . mb_strtolower($target), $senderMailbox);
		$this->getPlugin()->mail->setNested(mb_strtolower($target) . "." . mb_strtolower($player->getName()), $recipientMailbox);
	}
	
	/**
	 * Marks $player new messages as read by the $target, where $player is the sender and $target is the recipient.
	 *
	 * @param string $player
	 * @param Player $target
	 */
	private function markSeenMailbox(string $player, Player $target) : void{
		$senderMailbox = $this->getChatRoom($player, $target);
		foreach($senderMailbox as $i => $message){
			if($message["who"] === self::MESSAGE_WHO_RECIPIENT && !$message["read"]){
				//echo "Marked messages sent by " . $message["who"] . " as seen" . PHP_EOL;
				$senderMailbox[$i]["read"] = true;
			}
		}
		$this->getPlugin()->mail->setNested(mb_strtolower($player) . "." . mb_strtolower($target->getName()), $senderMailbox);
	}
	
	private function deleteChatRoom(Player $player, string $target) : void{
		$all = $this->getChatRoom($player);
		unset($all[mb_strtolower($target)]);
		$this->getPlugin()->mail->set(mb_strtolower($player->getName()), $all);
	}
	
	/**
	 * Formats a message.
	 * @param array $message
	 * @param string $who Because message who is one of {@see self::MESSAGE_WHO_*}
	 * @return string
	 */
	private function formatMessage(array $message, string $who) : string{
		return TextFormat::colorize(
			sprintf(
				self::$MESSAGE_FORMAT, $who, $this->getPlugin()->formatTime($this->getPlugin()->getTimeEllapsed($message["time"])), $message["message"]
			)
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		switch(array_shift($args)){
			case "write": //Send a message to player
				$form = new CustomForm(function(Player $player, ?array $data){
					if($player->isOnline() && $data === null){
						$this->runCommand($player, "mail");
						return;
					}
					if($player->isOnline()){
						$target = $data[1] ?? "";
						$chatrooms = $this->getChatRoom($player);
						
						$path = $this->getPlugin()->getServer()->getDataPath() . "players/";
						$players = array_map(function(string $filename) use($path) : string{
							return mb_strtolower(str_replace([$path, ".dat"], "", $filename));
						}, glob($path . "*.dat"));
						
						$closestOnline = $this->getPlugin()->getPlayer($target);
						if($closestOnline !== null && $closestOnline->getName() === $player->getName()){
							$closestOnline = null;
						}
						unset($players[array_search(mb_strtolower($player->getName()), $players)]);
						$this->getPlugin()->getClosestMatch($target, $players, $matches);
						
						if($closestOnline !== null){
							$matches = [];
							$matches[] = $closestOnline->getName();
						}
						if(count($matches) > 0){
							$form = new CustomForm(function(Player $player, ?array $data) use($matches){
								if($player->isOnline() && $data === null){
									$this->runCommand($player, "mail write");
									return;
								}
								$select = $data[1];
								if(is_int($select) && isset($matches[$select])){
									$this->createMailbox($player, $matches[$select]);
								}
							});
							$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fSend a message to player"));
							$form->addLabel(TextFormat::colorize("&7We've found (" . count($matches) . " players with a similar username, click the one you want message."));
							$form->addDropdown("Players", $matches);
							$player->sendForm($form);
						}else{
							$form = new ModalForm(function(Player $player, ?bool $goback){
								if($player->isOnline() && !$goback){
									$this->runCommand($player, "mail");
									return;
								}
								$this->runCommand($player, "mail write");
							});
							$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fSend a message to player"));
							$form->setContent(LangManager::translate("player-notfound", $player));
							$form->setButton1(LangManager::translate("continue", $player));
							$form->setButton2(LangManager::translate("cancel", $player));
							$player->sendForm($form);
						}
					}
				});
				$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fSend a message to player"));
				$form->addLabel(TextFormat::colorize("&7Type in the player you want to send a message to."));
				$form->addInput("Player");
				$sender->sendForm($form);
				break;
			case "read": //Open chatrooms
				$chatrooms = $this->getChatRoom($sender);
				$lastActive = [null, -1]; //-1 is used to compare times
				foreach($chatrooms as $chatroom => $messages){
					if(count($messages) > 0 && end($messages)["time"] > $lastActive[1]){
						$lastActive = [$chatroom, end($messages)["time"]];
					}
				}
				if($lastActive[1] === -1){ //No messages
					$lastActive[1] = time();
				}
				$form = new SimpleForm(function(Player $player, ?int $chatroom) use($chatrooms){
					if($player->isOnline() && $chatroom === null){
						return;
					}
					$keys = array_keys($chatrooms);
					if($player->isOnline() && !isset($keys[$chatroom])){
						$this->runCommand($player, "mail read");
						return;
					}
					if($player->isOnline()){
						if(count($chatrooms) > 0){
							$who = array_keys($chatrooms)[$chatroom];
							
							$this->markSeenMailbox($who, $player);
							//The fact that the client resends the form labels is ridiculous, but oh well
							$form = new CustomForm(function(Player $player, ?array $data) use($who){
								if($player->isOnline() && !isset($data["_msg"])){
									$this->runCommand($player, "mail read");
									return;
								}
								if($player->isOnline() && trim($msg = TextFormat::clean($data["_msg"])) !== ""){
									$this->pushToMailbox($player, $who, $msg);
									$form = new ModalForm(function(Player $player, ?bool $goback) use($who){
										if($player->isOnline() && !$goback){
											$this->runCommand($player, "mail read");
											return;
										}
										if($player->isOnline()){
											$this->runCommand($player, "mail read"); //TODO
										}
									});
									$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fSend a message to player"));
									$form->setContent(TextFormat::colorize("&aSent message to " . $who . "!"));
									$form->setButton1(LangManager::translate("continue", $player));
									$form->setButton2(LangManager::translate("cancel", $player));
									$player->sendForm($form);
								}
							});
							
							$onlineStr = $this->getPlugin()->getServer()->getPlayerExact($who) !== null ? "&aONLINE" : "&cOFFLINE";
							$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &f" . $who . " Chat Room (" . $onlineStr . "&f)")); 
							foreach($chatrooms[$who] as $message){
								$form->addLabel($this->formatMessage($message, $message["who"] === self::MESSAGE_WHO_SENDER ? $player->getName() : $who));
							}
							$form->addInput("Send a message", "", null, "_msg");
							$player->sendForm($form);
							
						}else{ //Player clicked the "You have no messages" button
							$this->runCommand("mail read");
						}
					}
				});
				$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fYour Chat Rooms (" . count($chatrooms) . ")"));
				if(count($chatrooms) < 1){
					$form->setContent(TextFormat::colorize("&7Last Active: Never"));
					$form->addButton(TextFormat::colorize("&fYou have no chat rooms."));
				}else{
					if($lastActive[0] !== null){
						$form->setContent(TextFormat::colorize("&7Last Active: &b" . $lastActive[0] . "&f (" . $this->getPlugin()->formatTime($this->getPlugin()->getTimeEllapsed($lastActive[1])) . " ago)"));
					}
				}
				foreach($chatrooms as $chatroom_ => $messages){
					$seenStr = "&cNot seen";
					if(count($messages) > 0){
						$seenStr = "&cNot seen";
						$last = end($messages);
						if($last["who"] === self::MESSAGE_WHO_RECIPIENT && !$last["read"]){ //They've sent a message
							$seenStr = "&aNew messages";
						}elseif($last["who"] === self::MESSAGE_WHO_RECIPIENT && $last["read"]){ //I've sent them a message
							$seenStr = "&aSeen";
						}
					}else{
						$seenStr = "&fNo messages yet";
					}
					$form->addButton(TextFormat::colorize("&b" . $chatroom_ . " &fChat Room\n&eMessages: &f" . count($messages) . "&e | " . $seenStr));
				}
				$sender->sendForm($form);
				
				break;
			case "view": //View unread messages
				$form = new SimpleForm(function(Player $player, ?int $data){
					if($player->isOnline() && $data === null){
						$this->runCommand($player, "mail");
						return;
					}
					$form = new ModalForm(function(Player $player, ?bool $goback){
						if($player->isOnline() && !$goback){
							return;
						}
						if($player->isOnline()){
							$this->runCommand($player, "mail view");
						}
					});
					$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fMark messages as read"));
					$form->setContent(TextFormat::colorize("&aMarked messages as read."));
					$form->setButton1(LangManager::translate("goback", $player));
					$form->setButton2(LangManager::translate("exit", $player));
					$player->sendForm($form);
				});
				
				$unread = [];
				$chatrooms = $this->getChatRoom($sender);
				foreach($chatrooms as $chatroom => $messages){
					
					foreach($messages as $message){
						if($message["who"] === self::MESSAGE_WHO_SENDER && !$message["read"]){
							$unread[] = [$chatroom, $message];
						}
					}
					$this->markSeenMailbox($chatroom, $sender); //After we have processed the messages
				}
				//Sort by newest (descending order)
				usort($unread, function(array $array1, array $array2) : array{ //No preserve keys, as opposed to uasort()
					return $array1[1]["time"] < $array2[1]["time"] ? -1 : 1;
				});
				$content = "";
				foreach($unread as $i => $array){
					$timezone = $this->getPlugin()->getEntry($sender, Main::ENTRY_TIMEZONE);
					if($timezone !== null){
						$array[1]["time"] = $this->getPlugin()->getTimeOnTimezone($timezone);
					}
					$content .= $this->formatMessage($array[1], $array[0]);
				}
				$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fMark messages as read"));
				$form->setContent($content);
				$form->addButton(TextFormat::colorize("&aMark all (" . count($unread) . ") messages as read"));
				$sender->sendForm($form);
				break;
			case "clear":
				$chatrooms = $this->getChatRoom($sender);
				if(count($chatrooms) < 1){
					$this->runCommand($sender, "/mail");
					break;
				}
				$form = new CustomForm(function(Player $player, ?array $data) use($chatrooms){
					if($player->isOnline() && $data === null){
						$this->runCommand($player, "mail");
						return;
					}
					if($player->isOnline() && isset($data[1])){
						$chatroom = array_keys($chatrooms)[$data[1]];
						
						//Confirm Dialog
						$form = new ModalForm(function(Player $player, ?bool $continue) use($chatroom){
							if($player->isOnline() && !$continue){
								$this->runCommand($player, "mail");
								return;
							}
							$messageCount = count($this->getChatRoom($player, $chatroom));
							$this->deleteChatRoom($player, $chatroom);
							//Success Dialog
							$form = new ModalForm(function(Player $player, ?bool $goback) use($chatroom, $messageCount){
								if($player->isOnline() && !$goback){
									$this->runCommand($player, "mail");
									return;
								}
								$this->runCommand($player, "mail clear");
							});
							$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fCleared " . $chatroom . " Chat Room"));
							$form->setContent(TextFormat::colorize("&aCleared " . $chatroom . " Chat Room (" . $messageCount . " messsages)"));
							$form->setButton1(LangManager::translate("continue", $player));
							$form->setButton2(LangManager::translate("cancel", $player));
							$player->sendForm($form);
						});
						
						$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fClear " . $chatroom . " Chat Room"));
						$form->setContent(TextFormat::colorize("&bAre you sure you want to clear " . $chatroom . " Chat Room?"));
						$form->setButton1(LangManager::translate("continue", $player));
						$form->setButton2(LangManager::translate("cancel", $player));
						$player->sendForm($form);
					}
				});
				$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fClear a Chat Room"));
				$form->addLabel(TextFormat::colorize("&7Select the chatroom you wish to clear."));
				$form->addDropdown("Chat Room", array_keys($chatrooms));
				$sender->sendForm($form);
				break;
			case "clear-all":
				$chatrooms = $this->getChatRoom($sender); //Yes, this is intentionally stored in a variable
				if(count($chatrooms) < 1){
					$this->runCommand($sender, "mail");
					break;
				}
				$form = new ModalForm(function(Player $player, ?bool $continue) use($chatrooms){
					if($player->isOnline() && !$continue){
						$this->runCommand($player, "mail");
						return;
					}
					if($player->isOnline() && $continue){
						$names = array_keys($chatrooms);
						$messageCount = 0;
						$chatRoomCount = count($names);
						foreach($names as $name){
							$messageCount += count($this->getChatRoom($player, $name));
							$this->deleteChatRoom($player, $name);
						}
						$form = new ModalForm(function(Player $player, ?bool $goback) use($messageCount, $chatRoomCount){
							if($player->isOnline() && !$goback){
								return;
							}
							$this->runCommand($player, "mail");
						});
						$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fCleared " . $chatRoomCount . " chat rooms"));
						$form->setContent(TextFormat::colorize("&aCleared " . $chatRoomCount . " chat rooms (" . $messageCount . " total messages)"));
						$form->setButton1(LangManager::translate("goback", $player));
						$form->setButton2(LangManager::translate("exit", $player));
						$player->sendForm($form);
					}
				});
				$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] > &fClear all chat rooms"));
				$form->setContent(TextFormat::colorize("&cAre you sure you want to clear " . count($chatrooms) . " chatrooms?"));
				$form->setButton1(LangManager::translate("continue", $sender));
				$form->setButton2(LangManager::translate("cancel", $sender));
				$sender->sendForm($form);
				break;
			default:
				$form = new SimpleForm(function(Player $player, ?string $opt){
					if($player->isOnline() && is_string($opt)){
						$this->runCommand($player, "mail " . $opt);
					}
				});
				$form->setTitle(TextFormat::colorize("&d[&fServerMail&d] >"));
				$form->addButton(TextFormat::colorize("&fSend a message to player"), -1, "", "write");
				$form->addButton(TextFormat::colorize("&fOpen chatrooms"), -1, "", "read");
				$form->addButton(TextFormat::colorize("&fView unread messages"), -1, "", "view");
				$form->addButton(TextFormat::colorize("&fClear a chat room"), -1, "", "clear");
				$form->addButton(TextFormat::colorize("&fClear all chat rooms"), -1, "", "clear-all");
				$sender->sendForm($form);
		}
		return true;
	}
}
