<?php

declare(strict_types=1);

namespace kenygamer\Core\command;

use pocketmine\item\Item;
use pocketmine\Player;
use pocketmine\utils\TextFormat;
use kenygamer\Core\LangManager;
use kenygamer\Core\Main;
use kenygamer\Core\Main2;
use kenygamer\Core\task\SendEmailTask;
use jojoe77777\FormAPI\ModalForm;
use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;
use PHPMailer\PHPMailer\PHPMailer;

class TransferCommand extends BaseCommand{
	/** @var int[] */
	private $codes = [];
	/** @var int[] */
	private $cooldown = [];
	
	public function __construct(){
		parent::__construct(
			"transfer",
			"Transfer all stuff from your old account",
			"/transfer",
			[],
			BaseCommand::EXECUTOR_ALL,
			"true"
		);
	}
	
	protected function onExecute($sender, array $args) : bool{
		if(isset($args[0])){ //Email Verification
			$code = intval($args[0]);
			if(!isset($this->codes[$sender->getName()])){
				$sender->sendMessage(TextFormat::colorize("&cYou are not completing any email verification. Did you mean /transfer?"));
				return true;
			}
			if(time() - $this->codes[$sender->getName()][1] > 120){
				unset($this->codes[$sender->getName()]);
				$sender->sendMessage("timedout");
				return true;
			}
			if($code !== $this->codes[$sender->getName()][0]){
				$sender->sendMessage(TextFormat::colorize("&cVerification code does not match. For security reasons, verification was invalidated."));
				unset($this->codes[$sender->getName()]);
				return true;
			}
			$this->transferProgress($from, $sender);
			return true;
		}
		
	    if(!empty($sender->getInventory()->getContents(false)) || !empty($sender->getArmorInventory()->getContents(false))){
	    	$sender->sendMessage("transfer-inventory");
	    	return true;
	    }
		
		$from = Main2::$xuids->get($sender->getXuid());
		
		$form = new SimpleForm(function(Player $player, ?int $option) use($from){
			if(is_int($option)){
				switch($option){
					case 0:
						if(strcasecmp($from, $player->getName()) === 0){
							$player->chat("/transfer");
							break;
						}
						$this->confirmTransferDialog($player, $from, function() use($from, $player){
							return Main2::$xuids->get($player->getXuid()) === $from;
						});
						break;
					case 1:
						if(isset($this->cooldown[$player->getName()]) && time() - $this->cooldown[$player->getName()] < 300){
							$player->sendMessage("in-cooldown");
							break;
						}
						$form = new CustomForm(function(Player $player, ?array $data){
							if($player->isOnline() && $data === null){
								$player->chat("/transfer");
								return;
							}
							if($player->isOnline() && is_array($data)){
								$from = mb_strtolower($data[1]);
								$email = $this->getPlugin()->playerEmails->get($from);
								if(!is_string($email) || strcasecmp($from, $player->getName()) === 0){
									$form = new ModalForm(function(Player $player, ?bool $retry){
										$player->chat("/transfer");
									});
									$form->setTitle(LangManager::translate("error", $player));
									$form->setContent(TextFormat::colorize("&cTransfer not available."));
									$form->setButton1(LangManager::translate("try-again", $player));
									$form->setButton2(LangManager::translate("exit", $player));
									$player->sendForm($form);
								}else{
									$form = new CustomForm(function(Player $player, ?array $data) use($from, $email){
										if($player->isOnline() && $data === null){
											$player->chat("/transfer");
											return;
										}
										if($player->isOnline() && is_array($data)){
											$inputEmail = $data[1];
											//if(strcasecmp($inputEmail, $email) === 0){
											if(password_verify($email, hash("sha512", $inputEmail))){
												$this->cooldown[$player->getName()] = time();
												
												$code = \kenygamer\Core\Main::mt_rand(100000, 999999);
												while(in_array($code, $this->codes)){
													$code = \kenygamer\Core\Main::mt_rand(100000, 999999);
												}
												$this->codes[$player->getName()] = [$code, time()];
												
												$smtp = $this->getPlugin()->getConfig()->get("smtp");
												
												$mail = new PHPMailer();
												/*https://pepipost.com/tutorials/phpmailer-smtp-error-could-not-connect-to-smtp-host/
												https://github.com/PHPMailer/PHPMailer/wiki/Troubleshooting#certificate-verification-failure
												PHP 5.6 has implemented stricter SSL behaviour 
												Quick workaround for PHPMailer >= v5.2.10*/
												
												//Send email by ESMTP (Enhanced Sending Mail Transfer Protocol) -
												//definition of protocol extension in RFC 1869 on 1995.
												$mail->SMTPOptions = ["ssl" => ["verify_peer" => false, "verify_peer_name" => false, "allow_self_signed" => false]];
												
												$mail->isSMTP();
												$mail->isHTML();
												$mail->CharSet = PHPMailer::CHARSET_UTF8; //UTF-8
												$mail->Host = $smtp["host"];
												$mail->Port = $smtp["port"];
												$mail->SMTPAuth = true;
												//$mail->SMTPDebug = 2;
												$mail->Username = $smtp["username"];
												$mail->Password = $smtp["password"];
												$mail->setFrom($smtp["from"]);
												$mail->addAddress($inputEmail, $player->getName());
												//$mail->addReplyTo("me@kenygamer.com");
												$mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS; //tls
												$mail->Subject = "EliteStar Email Verification";
												$mail->Body = "Hello " . $player->getName() . ", <br /><br />You are trying to make a data transfer from your old account " . $from . ". To complete email verification do \"/transfer " . $code . "\" ingame. For your security, this verification code will expire in 2 minutes.";
												$mail->Timeout = 5;
												
												$username = $player->getName();
												$this->getPlugin()->getServer()->getAsyncPool()->submitTask(new SendEmailTask($mail, function() use($username, $inputEmail){
													$player = $this->getPlugin()->getServer()->getPlayerExact($username);
													if($player !== null){
														$player->sendMessage(TextFormat::colorize("&aVerification email was sent to " . $inputEmail));
													}
												}, function() use($username){
													$player = $this->getPlugin()->getServer()->getPlayerExact($username);
													if($player !== null){
														$player->sendMessage("generic-error");
													}
												}));
											}else{
												$form = new ModalForm(function(Player $player, ?bool $retry){
													$player->chat("/transfer");
												});
												$form->setTitle(LangManager::translate("error", $player));
												$form->setContent(TextFormat::colorize("&cEmail is not the same you entered."));
												$form->setButton1(LangManager::translate("try-again", $player));
												$form->setButton2(LangManager::translate("exit", $player));
												$player->sendForm($form);
											}
										}
									});
									$form->setTitle(LangManager::translate("transfer-title", $player));
									
									//$garbledEmail = substr_replace($email, "*", 0, ceil((($at = strpos($email, "@")) - 1) / 2));
									//The email is scrambled.
									$form->addLabel(TextFormat::colorize("&7 Enter the full email address to send you a verification email.")); //\n\n$garbledEmail
									$form->addInput("Email");
									$player->sendForm($form);
								}
							}
						});
						$form->setTitle(LangManager::translate("transfer-title", $player));
						$form->addLabel(TextFormat::colorize("&&7Enter the old account's username. We will send a verification email to confirm it is you."));
						$form->addInput("Username");
						$player->sendForm($form);
						break;
					case 2:
						$form = new CustomForm(function(Player $player, ?array $data){
							if($player->isOnline() && $data === null){
								$player->chat("/transfer");
								return;
							}
							if($player->isOnline() && is_array($data)){
								$email = $data[1];
								if(!filter_var($email, FILTER_VALIDATE_EMAIL) || !checkdnsrr(explode("@", $email)[0], "MX")){
									$form = new ModalForm(function(Player $player, ?bool $retry){
										$player->chat("/transfer");
									});
									$form->setTitle(LangManager::translate("error", $player));
									$form->setContent(TextFormat::colorize("&cThe email entered is invalid."));
									$form->setButton1(LangManager::translate("try-again", $player));
									$form->setButton2(LangManager::translate("exit", $player));
									$player->sendForm($form);
								}else{
									$hashed = hash("sha512", $email);
									foreach($this->getPlugin()->playerEmails->getAll() as $player_ => $email_){
										//if(strcasecmp($email_, $email) === 0){
										if(password_verify($email_, $hashedEmail)){
											$form = new ModalForm(function(Player $player, ?bool $retry){
												$player->chat("/transfer");
											});
											$form->setTitle(LangManager::translate("error", $player));
											$form->setContent(TextFormat::colorize(strcasecmp($player_, $player->getName()) === 0 ? "&cYou already have that email set." : "The entered email is already in use."));
											$form->setButton1(LangManager::translate("try-again", $player));
											$form->setButton2(LangManager::translate("exit", $player));
											$player->sendForm($form);
											return;
										}
									}
									$form = new ModalForm(function(Player $player, ?bool $goback){
										if($player->isOnline() && $goback){
											$player->chat("/transfer");
										}
									});
									$this->getPlugin()->playerEmails->set(mb_strtolower($player->getName()), hash("sha512", $email));
									$form->setTitle(LangManager::translate("error", $player));
									$form->setContent(TextFormat::colorize("&aRecovery successfully set to " . $email));
									$form->setButton1(LangManager::translate("goback", $player));
									$form->setButton2(LangManager::translate("exit", $player));
									$player->sendForm($form);
								}
							}
						});
						$form->setTitle(LangManager::translate("transfer-title", $player));
						$form->addLabel(TextFormat::colorize("&7In case you lose access to this account, you can set a recovery email to transfer all data from this account to the new one."));
						//Argument 2: $this->getPlugin()->playerEmails->get(mb_strtolower($player->getName())
						$form->addInput("Email:", "", "");
						$player->sendForm($form);
						break;
				}
			}
		});
		$form->setTitle(TextFormat::colorize("&r&fTransfer progress"));
		$form->setContent(TextFormat::colorize("&7Transfer all progress from your old account."));
		$form->addButton(TextFormat::colorize("&9Transfer data from old username\n" . (strcasecmp($from, $sender->getName()) === 0 ? "&c(Not available)" : "&a(Detected username change)")));
		$form->addButton(TextFormat::colorize("&9Transfer data from old account"));
		$form->addButton(TextFormat::colorize($this->getPlugin()->playerEmails->exists(mb_strtolower($sender->getName())) ? "&9Change recovery email" : "&9Set recovery email"));
		$sender->sendForm($form);
		
	    return true;
	}
	
	/**
	 * @param Player $player
	 * @param string $from
	 * @param \Closure $success
	 */
	private function confirmTransferDialog(Player $player, string $from, \Closure $success) : void{
		/*$reflec = new \ReflectionFunction($success);
		$reflec->getReturnType()->__toString() === "boolean"*/
		
		$form = new ModalForm(function(Player $player, ?bool $confirm) use($success, $from){
			if($confirm){
				if($success()){ //Redundant, except if email had a dialog box
					$this->transferProgress($from, $player);
				}else{
					$player->sendMessage("generic-error");
				}
			}
		});
		$form->setTitle(LangManager::translate("transfer-title", $player));
		$form->setContent(LangManager::translate("transfer-confirm", $player, $from));
		$form->setButton1(LangManager::translate("yes", $player));
		$form->setButton2(LangManager::translate("no", $player));
		$player->sendForm($form);
	}
	
	/**
	 * @param string $from
	 * @param Player $to
	 */
	private function transferProgress(string $from, Player $to) : bool{
		/** @var IPlayer */
		$from = $this->getPlugin()->getServer()->getOfflinePlayer($from);
		
		/* Tokens */
		$this->getPlugin()->addTokens($to, $tokens = $this->getPlugin()->getTokens($from));
		$this->getPlugin()->subtractTokens($from, $tokens);
		/* Money */
		$to->addMoney($money = $this->getPlugin()->myMoney($from));
		$this->getPlugin()->reduceMoney($from, $money);
		/* Account Groups */
		$group = $this->getPlugin()->getAccountGroup($from->getName());
		if($group !== null){
			$group->removeUsername($from->getName());
			$group->addUsername($to->getName());
		}
		/* Vote Points */
		$stats = $this->getPlugin()->stats;
		$stats->set($to->getName(), $stats->get($from->getName(), []));
		$stats->remove($from->getName());
		/* Permissions */
		$manager = $this->getPlugin()->permissionManager;
		$oldGroups = $manager->getPlayerGroups($from);
		if($manager->getPlayerGroup($from) !== ($defGroup = $manager->getDefaultGroup())){
			foreach($oldGroups as $group){
				if($group->getName() !== $defGroup){ //Default group is a ghost group
					$manager->removePlayerFromGroup($from, $group);
					$manager->addPlayerToGroup($to, $group);
			    }
			}
		}
		$oldPrefix = $manager->getPlayerPrefix($from);
		if($oldPrefix !== ""){
			$manager->setPlayerPrefix($to, $oldPrefix);
			$manager->setPlayerPrefix($from, "");
		}
		$permissions = $manager->getPlayerPermissions($from);
		foreach($permissions as $permission){
			$manager->removePlayerPermission($from, $permission);
			$manager->addPlayerPermission($to, $permission);
		}
		/* Tags */
		$tags = $this->getPlugin()->tags;
		$tags->set($to->getName(), $stats->get($from->getName(), []));
		$tags->remove($from->getName());
		/* Auctions */
		$auctions = $this->getPlugin()->auctions;
		foreach($auctions as $auction){
			if($auction->seller === $from->getName()){
				$auction->seller = $to>getName();
			}
		}
		/* Lands */
		$lands = $this->getPlugin()->landManager->getAll();
		foreach($lands as $land){
			if($land->owner === $from->getName()){
				$land->owner = $to->getName();
			}
		}
		
		$property = (new \ReflectionClass($from))->getProperty("namedtag");
		$property->setAccessible(true);
		$namedtag = $property->getValue($from);
		if($namedtag !== null){
			/* Experience */
			$to->addXp($xp = $namedtag->getInt("XpTotal", 0, true));
			/* Inventory */
			$inventoryTag = $namedtag->getListTag("Inventory");
			if($inventoryTag !== null){
				foreach($inventoryTag as $i => $item){
					$slot = $item->getByte("Slot");
			    	if($slot >= 100 and $slot < 104){
			    		$to->getArmorInventory()->setItem($slot - 100, Item::nbtDeserialize($item));
			    	}elseif($slot >= 9 and $slot < $to->getInventory()->getSize() + 9){
			    		$to->getInventory()->setItem($slot - 9, Item::nbtDeserialize($item));
			    	}
				}
				$namedtag->removeTag("Inventory");
			}
		}
		/* Homes */
		$homes = $this->getPlugin()->getPlugin("LegacyCore")->homes;
		$homes->set($to->getName(), $homes->get($from->getName(), []));
		$homes->remove($from->getName());
		/* Faction */
		$fp = $this->getPlugin()->getPlugin("FactionsPro");
		if($fp->isInFaction($to->getName())){
			$faction = $fp->getPlayerFaction($from->getName());
			$fp->db->query("DELETE FROM master WHERE player='" . $to->getName() . "';");
				
			$stmt = $fp->db->prepare("INSERT INTO master (player, faction, rank) VALUES (:player, :faction, :rank);");
			$stmt->bindValue(":player", $to->getName());
			$stmt->bindValue(":faction", $faction);
		    	
			$resultArr = $fp->db->query("SELECT rank FROM master WHERE player='" . $from->getName() . "';")->fetchArray(SQLITE3_ASSOC);
	
			$stmt->bindValue(":rank", $resultArr["rank"]);
		    $stmt->execute();
			    	
			$fp->db->query("DELETE FROM master WHERE player='" . $from->getName() . "';");
		}
		/* Pet */
		$blockpets = $this->getPlugin()->getPlugin("BlockPets");
		$pet = $blockpets->getPetByName($from->getName() . "'s Pet", $from->getName());
		if($pet !== null){
			$blockpets->removePet($pet);
			$blockpets->getDatabase()->unregisterPet($pet);
		    	
			$newPet = $blockpets->createPet("Wolf", $to, $to->getName() . "'s Pet", 1.0);
			if($newPet !== null){ //Safety check: which plugin would cancel this?
				$newPet->register();
			}
		}
		/* Locked Chests */
		$pg = $this->getPlugin()->pg;
		foreach($pg->getAll() as $loc => $data){
			if($data["owner"] === $from->getName()){
				$data["owner"] = $to->getName();
				$pg->set($loc, $data);
			}
		}
		/*$pgDb = $this->getPlugin()->getPlugin("PocketGuard")->getDatabaseManager()->db;
		$result = $pgDb->query("SELECT * FROM chests WHERE owner = \"" . $from->getName() . "\"");
		while($data = $result->fetchArray(SQLITE3_ASSOC)){
			$owner = $data["owner"];
			$x = $data["x"];
		    $y = $data["y"];
		    $z = $data["z"];
		    $attribute = $data["attribute"];
		    $passcode = $data["passcode"];
		    if($owner === $from->getName()){
		    	$pgDb->exec("INSERT INTO chests (owner, x, y, z, attribute, passcode) VALUES (\"" . $to->getName() . "\", $x, $y, $z, $attribute, \"$passcode\")");
		    }
		}
		$pgDb->exec("DELETE FROM chests WHERE owner = \"" . $from->getName() . "\"");*/
		
		if($namedtag !== null){
			$this->getPlugin()->getServer()->saveOfflinePlayerData($from->getName(), $namedtag); //Update Inventory
		}
		
		Main2::$xuids->set($to->getXuid(), $to->getName()); //Update username with XUID
		Main::getInstance()->playerEmails->remove(mb_strtolower($from->getName()));
		$to->sendMessage("transfer");
		return true;
	}
	
}