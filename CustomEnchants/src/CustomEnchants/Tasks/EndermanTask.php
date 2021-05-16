<?php

declare(strict_types=1);

namespace CustomEnchants\Tasks;

use pocketmine\scheduler\Task;
use pocketmine\math\VoxelRayTrace;
use pocketmine\utils\TextFormat as Color;
use pocketmine\Player;
use pocketmine\entity\Entity;
use pocketmine\Server;

use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;

use CustomEnchants\Main;
use CustomEnchants\CustomEnchants\CustomEnchantsIds;

class EndermanTask extends Task{
	/** @var Main */
	private $plugin;
	
	/** @var int[] */
	public static $enderman = [];

	/** @var array */
	private $cooldown = [];
	
	public function __construct(Main $plugin){
		$this->plugin = $plugin;
	}
	
	/**
	 * @param int $currentTick
	 */
	public function onRun(int $currentTick) : void{
		$players = $this->plugin->getServer()->getOnlinePlayers();
		foreach($players as $player){
		    $enchantment = $player->getArmorInventory()->getChestplate()->getEnchantment(CustomEnchantsIds::ENDERMAN);
		    $index = array_search($player->getId(), self::$enderman);
		    if($enchantment !== null){
		    	if($index === false){
		    		self::$enderman[] = $player->getId();
		    		$player->sendPopup(Color::DARK_PURPLE . "You are now an enderman.");
		    	}
		    	
		    	if(isset($this->cooldown[$player->getName()])){
		    		if(!(time() >= $this->cooldown[$player->getName()])){
		    			$player->sendPopup(Color::DARK_PURPLE . "Enderman cooldown. Please wait " . Color::WHITE . strval(($this->cooldown[$player->getName()] - time())) . "s");
		    		}else{
		    			unset($this->cooldown[$player->getName()]);
		    			$player->sendPopup(Color::DARK_GREEN . "Enderman cooldown is over.");
		    		}
		    	}
		    	
		    	if(!isset($this->cooldown[$player->getName()])){
		    		foreach(VoxelRayTrace::inDirection($player->add(0, $player->getEyeHeight(), 0), $player->getDirectionVector(), $enchantment->getLevel() * 20) as $vector3){
		    			foreach(self::getViewers($player) as $p){
		    				if($p->getFloorX() === $vector3->getFloorX() && $p->getFloorY() === $vector3->getFloorY() && $p->getFloorZ() === $vector3->getFloorZ() && $p->getLevel()->getFolderName() === $player->getLevel()->getFolderName() && $p->distance($player) > 5){
		    					$cost = 1000 * $enchantment->getLevel();
		    					if($player->getCurrentTotalXp() - $cost <= 0){
		    						$player->sendPopup(Color::DARK_RED . "You need " . $cost) . " EXP to teleport.";
		    					}else{
		    						$this->cooldown[$player->getName()] = time() + 5;
		    						$player->subtractXp($cost);
		    						$player->teleport($p);
		    						$player->sendPopup(Color::DARK_GREEN . "Teleported to " . Color::DARK_AQUA . $p->getName());
		    					}
		    				}
		    			}
		    		}
		    	}
		    	
		    }elseif($index !== false){
		    	unset(self::$enderman[$index]);
		    	$player->sendPopup(Color::DARK_PURPLE . "You are no longer an enderman.");
		    }
		}
	}
	
	/**
	 * @param Player $player
	 *
	 * @return Player[]
	 */
	public static function getViewers(Player $player) : array{
		$players = [];
		foreach(Server::getInstance()->getOnlinePlayers() as $p){
			if($p->getName() !== $player->getName() && $p->getLevel()->getFolderName() === $player->getLevel()->getFolderName() && $p->isConnected()){
				$players[] = $p;
			}
		}
		return $players;
	}
	
	/**
	 * @param int $entityId
	 * @param bool $motionOnly
	 *
	public static function sendEnderman(int $entityId, bool $motionOnly) : void{
		foreach(Server::getInstance()->getOnlinePlayers() as $player){
			if($player->getId() === $entityId){
		        /*$pk = new RemoveActorPacket();
                $pk->entityUniqueId = $player->getId()+\kenygamer\Core\Main::mt_rand(1, 999);
        
                $pk2 = new AddActorPacket();
                $pk2->entityRuntimeId = Entity::$count++;
                $pk2->type = AddActorPacket::LEGACY_ID_MAP_BC[Entity::ENDERMAN];
                $pk2->position = $player->asVector3();
                $pk2->motion = $player->asVector3();
                $pk2->pitch = $player->getPitch();
                $pk2->yaw = $player->getPitch();
                $pk2->headYaw = $player->getYaw();
		        
		        /*$pk3 = new SetActorMotionPacket();
		        $pk3->entityRuntimeId = $player->getId();
		        $pk3->motion = $player->asVector3();
		    	
		    	foreach(self::getViewers($player) as $p){
		    		if($motionOnly){
		    			$p->dataPacket($pk3);
		    		}else{
		    		    //$p->dataPacket($pk);
		    			$p->dataPacket($pk2);
		    			//$p->dataPacket($pk3);
		    		}
		    	}
		    }
		}
	}*/
		
}