<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard;

use AndreasHGK\Shipyard\Controller\ControllerCooldown;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\Player;
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\inventory\Inventory;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\Listener;
use pocketmine\scheduler\Task;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\TextFormat as C;

class Shipyard extends PluginBase implements Listener{
	
	public $version = "0.1.0-ALPHA";
	public $id = 280;
	public $cooldown = [];

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
					
					#give the sender a controller
					case "get":
					$this->giveController($sender);
					$sender->sendMessage(C::YELLOW.C::BOLD."Shipyard: ".C::RESET.C::GRAY."a ship controller has been given");
					return true;
					
					case "create":
					$sender->sendMessage(C::RED."comming in a future version!");
					return true;
					
					case "remove":
					$sender->sendMessage(C::RED."comming in a future version!");
					return true;
					
					case "list":
					$sender->sendMessage(C::RED."comming in a future version!");
					return true;
				}
				
				return true;
				break;
				
			default:
				return false;
		}
	}
	
	public function onInteract(PlayerInteractEvent $event){
		$player = $event->getPlayer();
		#$name = $player->getName();
		$hand = $player->getInventory()->getItemInHand();
		
		#compare the 2 items
		if($this->getControllerItem()->getName() == $hand->getName() && $this->getControllerItem()->getID() == $hand->getID() && $this->getControllerItem()->getLore() == $hand->getLore()){
			
			if(!in_array($player, $this->cooldown)){
				
				#make the ship move and set cooldown for player
				$player->sendMessage(C::RED."X: ".$player->getDirectionVector()->x);
				$player->sendMessage(C::RED."Y: ".$player->getDirectionVector()->y);
				$player->sendMessage(C::RED."Z: ".$player->getDirectionVector()->z);
				$player->sendMessage(C::RED."Direction: ".$this->getFacing($player));
				$this->setControllerCooldown($player);
			}
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
		if($z <= -0.5) return "West"; #prime example of my OCD
		return null;
	}
	
	public function setControllerCooldown(Player $player) {
		
		#set a cooldown for a player
		$task = new ControllerCooldown($this);	
		$handler = $this->getScheduler()->scheduleRepeatingTask($task, 20);
		$task->setHandler($handler);
		$this->cooldown[$task->getTaskId()] = $player;
	}
	
	public function removeTask($id) {
		
		#remove the cooldown
		unset($this->cooldown[$id]);
		$this->getScheduler()->cancelTask($id);
	}

	public function helpMessage(Player $player) : void{
		
		#send a help message
		$player->sendMessage(C::DARK_GRAY."----= ".C::YELLOW.C::BOLD."SHIPYARD ".C::RESET.C::GRAY."v".$this->version.C::RESET.C::DARK_GRAY." =----".C::GOLD."\n/shipyard help".C::	GRAY." - view this message".C::GOLD."\n/shipyard get".C::GRAY." - get a ship controller".C::GOLD."\n/shipyard create".C::GRAY." - make a new ship".C::GOLD."\n/shipyard remove".C::GRAY." - remove a ship".C::GOLD."\n/shipyard list".C::GRAY." - list all the ships you own");
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