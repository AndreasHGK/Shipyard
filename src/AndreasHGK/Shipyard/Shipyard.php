<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard;

use AndreasHGK\Shipyard\ControllerCooldown;
use AndreasHGK\Shipyard\Ship;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\level\Level;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\inventory\Inventory;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\block\Block;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\TextFormat as C;

class Shipyard extends PluginBase implements Listener{
	
	public $version = "1.0.0-ALPHA";
	public $id = 280;
	public $ships = [];
	public $control = [];
	public $cooldown = [];
	public $pos1 = [];
	public $pos2 = [];
	public $request = [];
	
	public function onEnable() : void{
		$this->getLogger()->info(C::GREEN."enabled Shipyard v".$this->version);
		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onCommand(CommandSender $sender, Command $command, string $label, array $args) : bool{
		$name = $sender->getName(); #get player name as a string
		
		#check if command is ran from console
 		if(!($sender instanceof Player)){
			$sender->sendMessage(C::RED."Please run this command in-game");
			return true;
		}
		switch(strtolower($command->getName())){
			case "shipyard":
				
				#check if sender used arguments
				if(empty($args[0])){
					$this->helpMessage($sender);
					return true;
				}
				switch(strtolower($args[0])){
					case "help":
					$this->helpMessage($sender);
					return true;
					break;
					 
					#give the sender a controller
					case "get":
					$this->giveController($sender);
					$sender->sendMessage(C::YELLOW.C::BOLD."Shipyard: ".C::RESET.C::GRAY."a ship controller has been given");
					return true;
					break;
					
					case "create": 
					#check if everything is set and call createShip
					if(!empty($args[1])){
						if(isset($this->pos1[$name]) && isset($this->pos2[$name])){
							if(!$this->shipExists($args[1])){
								$this->createShip(strtolower($args[1]), $sender, $this->pos1[$name], $this->pos2[$name], $sender->getLevel()->getName());
								$sender->sendMessage(C::GREEN."Ship '".strtolower($args[1])."' created.");
								return true;
							}else{
								$sender->sendMessage(C::RED."That ship already exists!");
								return true;
							}
						} else {
							$sender->sendMessage(C::RED."You didn't set both positions");
							return true;
						}
					} else{
						$sender->sendMessage(C::RED."Please specify a name for your new ship");
						return true;
					}
					return true;
					break;
					
					case "remove":
					$ship = $args[1];
					if($this->shipExists($ship)){
						$this->getShip($ship)->remove();
						$sender->sendMessage(C::GREEN."Ship succesfully removed!");
						return true;
					}else{
						$sender->sendMessage(C::RED."That ship doesn't exist!");
						return true;
					}
					return true;
					break;
					
					case "list":
					$ownedShips = $this->ownedShips($sender);
					if($ownedShips != []){
						$sender->sendMessage(C::YELLOW."Ships you own:");
						foreach($ownedShips as $ship){
							$sender->sendMessage(C::GRAY." -".$ship->getName());
						}
						return true;
					}else{
						$sender->sendMessage(C::RED."You don't own any ships!");
					}
					return true;
					break;
					
					case "pos1":
					#request a new pos1
					$this->request[$name] = 1;
					$sender->sendMessage(C::GREEN."Now selecting first position");
					return true;
					break;
					
					case "pos2":
					#request a new pos1
					$this->request[$name] = 2;
					$sender->sendMessage(C::GREEN."Now selecting second position");
					return true;
					break;
					
					case "control":
					#enable control mode for a player
					if(empty($args[1])){
						if(isset($this->control[$name])){
							$sender->sendMessage(C::GREEN."Stopped controlling ship.");
							unset($this->control[$name]);
							return true;
						}else{
							$sender->sendMessage(C::RED."Please specify which ship you wish to take control of.");
						}
						return true;
					}
					if($this->shipExists($args[1])){
						if($this->getShip($args[1])->getOwner() == $sender){
							if(in_array($args[1], $this->control)){
								$sender->sendMessage(C::RED."Someone is already controlling this ship!");
								return true;
							}else{
								$this->control[$name] = strtolower($args[1]);
								$sender->sendMessage(C::GREEN."Took control of ship '".strtolower($args[1])."' ");
								return true;
							}
						}else{
							$sender->sendMessage(C::RED."You don't own this ship.");
							return true;
						}
					}else{
						$sender->sendMessage(C::RED."That ship doesn't exist.");
						return true;
					}
					return true;
					break;
				}
				
				return true;
				break;
				
			default:
				return false;
		}
	}
	
	public function saveShips(){
		
	}
	
	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		$name = $player->getName();
		$hand = $player->getInventory()->getItemInHand();
		
		#compare the 2 items
		if($this->getControllerItem()->getName() == $hand->getName() && $this->getControllerItem()->getID() == $hand->getID() && $this->getControllerItem()->getLore() == $hand->getLore()){
			
			if(!in_array($player, $this->cooldown)){
				
				#set cooldown and call the ship move function
				$player->sendMessage(C::RED."Direction: ".$this->getFacing($player));
				$this->setControllerCooldown($player);
				if(isset($this->control[$name])){
					$this->moveShip($this->control[$name], $this->getShip($this->control[$name])->getWorld(), $this->getFacing($player), 5);
				}
			}
		}
	}
	
	public function ownedShips(Player $player) : array{
		$ownedships = [];
		foreach($this->ships as $ship){
			if($ship->getOwner() == $player){
				array_push($ownedships, $ship);
			}
		}
		return $ownedships;
	}
	
	public function blockBreak(BlockBreakEvent $event){
		
		#get block position
		$player = $event->getPlayer();
		$name = $event->getPlayer()->getName();
		$block = $event->getBlock();
		$bpos = $event->getBlock()->asVector3();
		$level = $block->getLevel();
		
		#check if player requests pos1 or 2
		if(isset($this->request[$name])){
			#check what has been requested by a player
			if($this->request[$name] == 1){
				$this->pos1[$name] = $bpos;
				$player->sendMessage(C::RED."POS1: ".$bpos);
			}elseif($this->request[$name] == 2){	
				$this->pos2[$name] = $bpos;
				$player->sendMessage(C::RED."POS2: ".$bpos);
			}
			$event->setCancelled();
			#$level->setBlock($block, $block);			
			unset($this->request[$name]);
		}
	}
	
	public function getShip(string $name) : Ship{
		return $this->ships[$name];
	}
	
	public function shipExists(string $name) : bool{
		return isset($this->ships[$name]);
	}
	
	public function createShip(string $name, Player $owner, Vector3 $pos1, Vector3 $pos2, string $world) : void{
		#make a new ship
		new Ship($this, $name, $owner, $pos1, $pos2, $world);
	}
	
	public function moveShip(string $ship, string $world, string $direction, int $speed) : void{
		#move the ship
		$world = $this->getServer()->getLevelByName($world);
		$blocks = $this->getShip($ship)->getBlocks();
		$x = $this->getShip($ship)->getPos1()->getX();
		$y = $this->getShip($ship)->getPos1()->getY();
		$z = $this->getShip($ship)->getPos1()->getZ();
		$x1 = $this->getShip($ship)->getPos2()->getX();
		$y1 = $this->getShip($ship)->getPos2()->getY();
		$z1 = $this->getShip($ship)->getPos2()->getZ();
		
		switch($direction){
			case "Up":
			$move = new Vector3(0, $speed, 0);
			$this->getShip($ship)->pos1 = new Vector3($x, $y+$speed, $z);
			$this->getShip($ship)->pos2 = new Vector3($x1, $y1+$speed, $z1);
			
			case "Down":
			$move = new Vector3(0, -$speed, 0);
			$this->getShip($ship)->pos1 = new Vector3($x, $y-$speed, $z);
			$this->getShip($ship)->pos2 = new Vector3($x1, $y1-$speed, $z1);
			
			case "South":
			$move = new Vector3($speed, 0, 0);
			$this->getShip($ship)->pos1 = new Vector3($x+$speed, $y, $z);
			$this->getShip($ship)->pos2 = new Vector3($x1+$speed, $y1, $z1);
			
			case "East":
			$move = new Vector3(-$speed, 0, 0);
			$this->getShip($ship)->pos1 = new Vector3($x-$speed, $y, $z);
			$this->getShip($ship)->pos2 = new Vector3($x1-$speed, $y1, $z1);
			
			case "North":
			$move = new Vector3(0, 0, $speed);
			$this->getShip($ship)->pos1 = new Vector3($x, $y, $z+$speed);
			$this->getShip($ship)->pos2 = new Vector3($x1, $y1, $z1+$speed);
			
			case "West":
			$move = new Vector3(0, 0, -$speed);
			$this->getShip($ship)->pos1 = new Vector3($x, $y, $z-$speed);
			$this->getShip($ship)->pos2 = new Vector3($x1, $y1, $z1-$speed);
		}
		foreach($blocks as $block){
			$world->setBlock($block, Block::get(0));
		}
		foreach($blocks as $block){
			$loc = $block->asVector3();
			$world->setBlock(new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ()), $block);
		}
	}
	
	public function getFacing(Player $player) : string{
		
		#calculate what direction a player is looking. (North, East, South, West, Up, Down)
		$x = $player->getDirectionVector()->x;
		$y = $player->getDirectionVector()->y; 
		$z = $player->getDirectionVector()->z;
		
		if($y >= 0.850) return "Up";
		if($y <= -0.85) return "Down";
		
		if($x >= 0.50) return "South";
		if($x <= -0.5) return "East";
		if($z >= 0.50) return "North";
		if($z <= -0.5) return "West";
		return null;
	}
	
	public function setControllerCooldown(Player $player) : void{
		
		#set a cooldown for a player
		$task = new ControllerCooldown($this);	
		$handler = $this->getScheduler()->scheduleRepeatingTask($task, 20);
		$task->setHandler($handler);
		$this->cooldown[$task->getTaskId()] = $player;
	}
	
	public function removeTask($id) : void{
		
		#remove the cooldown
		unset($this->cooldown[$id]);
		$this->getScheduler()->cancelTask($id);
	}

	public function helpMessage(Player $player) : void{
		
		#send a help message
		$player->sendMessage(C::DARK_GRAY."----= ".C::YELLOW.C::BOLD."SHIPYARD ".C::RESET.C::GRAY."v".$this->version.C::RESET.C::DARK_GRAY." =----".C::GOLD."\n/shipyard help".C::	GRAY." - view this message".C::GOLD."\n/shipyard get".C::GRAY." - get a ship controller".C::GOLD."\n/shipyard create".C::GRAY." - make a new ship".C::GOLD."\n/shipyard remove".C::GRAY." - remove a ship".C::GOLD."\n/shipyard list".C::GRAY." - list all the ships you own".C::GOLD."\n/shipyard pos1".C::GRAY." - select a first position for your ship".C::GOLD."\n/shipyard pos2".C::GRAY." - select a second position for your ship".C::GOLD."\n/shipyard control".C::GRAY." - take control of a ship");
	}
	
	public function giveController(Player $player) : void{
		
		#give the controller item
		$player->getInventory()->addItem($this->getControllerItem());
	}
	
	public function getControllerItem() : Item{
		
		#set up the controller item
		$crtl = Item::get($this->id, 0, 1);
		$crtl->setCustomName(C::RESET.C::YELLOW.C::BOLD."Ship Controller");
		$crtl->setLore([C::RESET.C::GOLD."Use this to control your ship"]);
		return $crtl;
	}
	
	public function onDisable() : void{
		$this->getLogger()->info(C::RED."disabled Shipyard v".$this->version);
	}
}