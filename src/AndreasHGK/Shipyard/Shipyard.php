<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard;

use AndreasHGK\Shipyard\ControllerCooldown;
use AndreasHGK\Shipyard\Ship;
use AndreasHGK\Shipyard\ShipMoveTask;

use pocketmine\level\Position;
use pocketmine\Server;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginCommand;
use pocketmine\command\Command;
use pocketmine\utils\Config;
use pocketmine\level\Level;	
use pocketmine\item\Item;
use pocketmine\nbt\NBT;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\inventory\Inventory;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\scheduler\TaskHandler;
use pocketmine\scheduler\TaskScheduler;
use pocketmine\utils\TextFormat as C;

class Shipyard extends PluginBase implements Listener{

	CONST VERSION = "1.0.0-ALPHA";
	public $id = 280;
	public $ships = [];
	public $control = [];
	public $cooldown = [];
	public $pos1 = [];
	public $pos2 = [];
	public $request = [];
	public $db;
	public $movetasks = [];
	public $waterlevel = 63;

	public $requirewater = false;

	public function onEnable() : void{

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
/* 		@mkdir($this->getDataFolder());
		$this->saveResource("ships.json"); 
		$this->db = new Config($this->getDataFolder() . "ships.json", Config::JSON);
		$this->loadShips(); */
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
								$this->createShip(strtolower($args[1]), $name, $this->pos1[$name], $this->pos2[$name], $sender->getLevel()->getName(), $this->getFacing($sender, true));
                                if(min($this->getShip($args[1])->getPos1()->getY(), $this->getShip($args[1])->getPos2()->getY()) > $this->waterlevel || $this->requirewater == false){
                                    $sender->sendMessage(C::RED."Your ship needs to be in the water");
                                    $this->getShip($args[1])->remove();
                                    return true;
                                } elseif($this->shipCreationChecks($args[1]) == 1){
                                    $sender->sendMessage(C::GREEN."Ship '".strtolower($args[1])."' created.");
                                    $this->saveShips();
                                    return true;
                                }elseif($this->shipCreationChecks($args[1]) == 0){
									$sender->sendMessage(C::RED."Your ship doesn't have a ship core.");
									$this->getShip($args[1])->remove();
									return true;
								}else{
									$sender->sendMessage(C::RED."Your ship can't have more than 1 cores.");
									$this->getShip($args[1])->remove();
									return true;
								}
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
					break;
					
					case "remove":
					$ship = $args[1];
					if($this->shipExists($ship)){
						if($this->getShip($ship)->getOwner() == $name || $sender->IsOp()){
							$this->getShip($ship)->remove();
							$sender->sendMessage(C::GREEN."Ship succesfully removed!");
							$this->saveShips();
						return true;
						}
					}else{
						$sender->sendMessage(C::RED."That ship doesn't exist!");
						return true;
					}
					return true;
					break;
					
					case "list":
					$ownedShips = $this->ownedShips($name);
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
						if($this->getShip($args[1])->getOwner() == $name){
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
					break;
					
					case "info":
					#request a new pos1
					if($this->shipExists($args[1])){
						$sender->sendMessage(C::DARK_GRAY."----= ".C::YELLOW.C::BOLD.$this->getShip($args[1])->getName().C::RESET.C::DARK_GRAY." =----".C::GOLD."\nOwner".C::GRAY." : ".$this->getShip($args[1])->getOwner().C::GOLD."\nClass".C::GRAY." : ".$this->getShip($args[1])->class.C::GOLD."\nSize".C::GRAY." : ".$this->getShip($args[1])->getSize().C::GOLD."\nWorld".C::GRAY." : ".$this->getShip($args[1])->getWorld().C::GOLD."\nPos1".C::	GRAY." : ".$this->getShip($args[1])->getPos1().C::GOLD."\nPos2".C::GRAY." : ".$this->getShip($args[1])->getPos2());
						return true;
					}else{
						$sender->sendMessage(C::GREEN."That ship doesn't exist");
						return true;
					}
					break;
					
					default:
					$sender->sendMessage(C::RED."That subcommand doesn't exist!");
					return true;
					break;
				}
				break;
				
			default:
				return false;
		}
	}
	
	# WORK IN PROGRESS
	public function loadShips() : void{}
	
	# WORK IN PROGRESS
	public function saveShips() : void{}

	public function onInteract(PlayerInteractEvent $event) : void{
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
				    $ship = $this->control[$name];
                    $this->moveShip($player, $this->control[$name], $this->getShip($this->control[$name])->getWorld(), $this->getFacing($player), 5);
				}
			}
		}
	}

	public function ownedShips(string $player) : array{
		#list the ships this player owns
		$ownedships = [];
			foreach($this->ships as $ship){
			if($ship->getOwner() == $player){
				array_push($ownedships, $ship);
			}
		}
		return $ownedships;
	}

	public function blockBreak(BlockBreakEvent $event) : void{
		
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

	public function getInstance(){
	    return $this;
    }

	public function shipCreationChecks(string $ship) : int{
		$cores = $this->getShip($ship)->countCores();
		if($cores == 1){
			return 1;
		}elseif($cores == 0){
			return 0;
		}else{
			return 2;
		}
	}

	public function createShip(string $name, string $owner, Vector3 $pos1, Vector3 $pos2, string $world, string $forward) : void{
		#make a new ship
		new Ship($this, $name, $owner, $pos1, $pos2, $world, $forward);
	}

    public function moveShip(Player $player, string $ship, string $worldstring, string $direction, int $speed) : void{
        #move the ship
        #get initial variables
        $world = $this->getServer()->getLevelByName($worldstring);
        $blocks = $this->getShip($ship)->getBlocks(false);
        $x = $this->getShip($ship)->getPos1()->getX();
        $y = $this->getShip($ship)->getPos1()->getY();
        $z = $this->getShip($ship)->getPos1()->getZ();
        $x1 = $this->getShip($ship)->getPos2()->getX();
        $y1 = $this->getShip($ship)->getPos2()->getY();
        $z1 = $this->getShip($ship)->getPos2()->getZ();

        switch($direction){
            #deterimine in which direction to move the ship
            case "Up":
                $move = new Vector3(0, $speed, 0);
                foreach($blocks as $block){
                    $loc = $block->asVector3();
                    $target = new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ());
                    if($world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::WATER and $world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::AIR and !in_array($world->getBlock($target), $blocks)){
                        $player->sendMessage(C::colorize("§7That area appears to be §cobstructed§7!"));
                        return;
                    }
                }
                $this->getShip($ship)->pos1 = new Vector3($x, $y+$speed, $z);
                $this->getShip($ship)->pos2 = new Vector3($x1, $y1+$speed, $z1);
                break;

            case "Down":
                $move = new Vector3(0, -$speed, 0);
                foreach($blocks as $block){
                    $loc = $block->asVector3();
                    $target = new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ());
                    if($world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::WATER and $world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::AIR and !in_array($world->getBlock($target), $blocks)){
                        $player->sendMessage(C::colorize("§7That area appears to be §cobstructed§7!"));
                        return;
                    }
                }
                $this->getShip($ship)->pos1 = new Vector3($x, $y-$speed, $z);
                $this->getShip($ship)->pos2 = new Vector3($x1, $y1-$speed, $z1);
                break;

            case "West":
                $move = new Vector3($speed, 0, 0);
                foreach($blocks as $block){
                    $loc = $block->asVector3();
                    $target = new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ());
                    if($world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::WATER and $world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::AIR and !in_array($world->getBlock($target), $blocks)){
                        $player->sendMessage(C::colorize("§7That area appears to be §cobstructed§7!"));
                        return;
                    }
                }
                $this->getShip($ship)->pos1 = new Vector3($x+$speed, $y, $z);
                $this->getShip($ship)->pos2 = new Vector3($x1+$speed, $y1, $z1);
                break;

            case "East":
                $move = new Vector3(-$speed, 0, 0);
                foreach($blocks as $block){
                    $loc = $block->asVector3();
                    $target = new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ());
                    if($world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::WATER and $world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::AIR and !in_array($world->getBlock($target), $blocks)){
                        $player->sendMessage(C::colorize("§7That area appears to be §cobstructed§7!"));
                        return;
                    }
                }
                $this->getShip($ship)->pos1 = new Vector3($x-$speed, $y, $z);
                $this->getShip($ship)->pos2 = new Vector3($x1-$speed, $y1, $z1);
                break;

            case "North":
                $move = new Vector3(0, 0, $speed);
                foreach($blocks as $block){
                    $loc = $block->asVector3();
                    $target = new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ());
                    if($world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::WATER and $world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::AIR and !in_array($world->getBlock($target), $blocks)){
                        $player->sendMessage(C::colorize("§7That area appears to be §cobstructed§7!"));
                        return;
                    }
                }
                $this->getShip($ship)->pos1 = new Vector3($x, $y, $z+$speed);
                $this->getShip($ship)->pos2 = new Vector3($x1, $y1, $z1+$speed);
                break;

            case "South":
                $move = new Vector3(0, 0, -$speed);
                foreach($blocks as $block){
                    $loc = $block->asVector3();
                    $target = new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ());
                    if($world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::WATER and $world->getBlockIdAt($target->getX(), $target->getY(), $target->getZ()) != Block::AIR and !in_array($world->getBlock($target), $blocks)){
                        $player->sendMessage(C::colorize("§7That area appears to be §cobstructed§7!"));
                        return;
                    }
                }
                $this->getShip($ship)->pos1 = new Vector3($x, $y, $z-$speed);
                $this->getShip($ship)->pos2 = new Vector3($x1, $y1, $z1-$speed);
                break;

            default:
                $player->sendMessage(C::RED."A movement error has occured!");
                break;
        }

        #make everything air and then place the blocks 5 blocks further
        foreach($blocks as $block){
            if($block->y > $this->waterlevel){
                $world->setBlock($block, Block::get(0));
            }else{
                $world->setBlock($block, Block::get(Block::WATER));
            }

        }
        foreach($blocks as $block){
            $loc = $block->asVector3();
            $world->setBlock(new Vector3($loc->getX()+$move->getX(), $loc->getY()+$move->getY(), $loc->getZ()+$move->getZ()), $block);
        }
        $entities = $world->getEntities();
        foreach ($entities as $entity) {
            $xe = $entity->getLocation()->getX();
            $ye = $entity->getLocation()->getY();
            $ze = $entity->getLocation()->getZ();
            if ($xe >= min($x, $x1)-2 && $xe <= max($x, $x1)+2 && $ye >= min($y, $y1)-2 && $ye <= max($y, $y1)+2 && $ze >= min($z, $z1)-2 && $ze <= max($z, $z1)+2) {
                $pos = new Position($xe+$move->getX(), $ye+$move->getY(), $ze+$move->getZ());
                $entity->teleport($pos, $entity->getYaw(), $entity->getPitch());
            }
        }

        $this->saveShips();
    }

    public function turn($dir): void
    {

    }

    public function compareDirection(string $dir1, string $dir2): int
    {
        switch ($dir1) {
            case "West":
                $dir1s = 4;
                break;

            case "East":
                $dir1s = 2;
                break;

            case "North":
                $dir1s = 1;
                break;

            case "South":
                $dir1s = 3;
                break;
        }
        switch ($dir2) {
            case "West":
                $dir2s = 4;
                break;

            case "East":
                $dir2s = 2;
                break;

            case "North":
                $dir2s = 1;
                break;

            case "South":
                $dir2s = 3;
                break;
        }
        $result = $dir1s - $dir2s; //0: forward, 1: right, 2: backward, 3: left
        switch ($result) {
            case -3:
                return 3;
                break;
            case -2:
                return 2;
                break;
            case -1:
                return 1;
                break;
            case 0:
                return 0;
                break;
            case 1:
                return 3;
                break;
            case 2:
                return 2;
                break;
            case 3:
                return 1;
                break;
        }
        return 0;
    }

	public function getFacing(Player $player, $exclude = false) : string{
		
		#calculate what direction a player is looking. (North, East, South, West, Up, Down)
		$x = $player->getDirectionVector()->x;
		$y = $player->getDirectionVector()->y; 
		$z = $player->getDirectionVector()->z;
		
		if($y >= 0.850 && $exclude == false) return "Up";
		if($y <= -0.85 && $exclude == false) return "Down";
		
		if($x >= 0.50) return "West";
		if($x <= -0.5) return "East";
		if($z >= 0.50) return "North";
		if($z <= -0.5) return "South";
		return "Up";
	}

	public function setControllerCooldown(Player $player) : void{
		
		#set a cooldown for a player
		$task = new ControllerCooldown($this);	
		$handler = $this->getScheduler()->scheduleRepeatingTask($task, 1);
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
		$player->sendMessage(C::DARK_GRAY."----= ".C::YELLOW.C::BOLD."SHIPYARD ".C::RESET.C::GRAY."v".$this->version.C::RESET.C::DARK_GRAY." =----".C::GOLD."\n/shipyard help".C::	GRAY." - view this message".C::GOLD."\n/shipyard get".C::GRAY." - get a ship controller".C::GOLD."\n/shipyard create".C::GRAY." - make a new ship".C::GOLD."\n/shipyard remove".C::GRAY." - remove a ship".C::GOLD."\n/shipyard list".C::GRAY." - list all the ships you own".C::GOLD."\n/shipyard pos1".C::GRAY." - select a first position for your ship".C::GOLD."\n/shipyard pos2".C::GRAY." - select a second position for your ship".C::GOLD."\n/shipyard control".C::GRAY." - take control of a ship".C::GOLD."\n/shipyard info".C::GRAY." - display info of a certain ship");
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
		$this->saveShips();
	}
}