<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard;

use AndreasHGK\Shipyard\Shipyard;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Ship{
	
	private $shipyard;
	public $name;
	public $owner;
	public $pos1;
	public $pos2;
	public $world;
	public $blocks = [];
	
	public function __construct(Shipyard $plugin, string $name, Player $owner, Vector3 $pos1, Vector3 $pos2, string $world){
		$this->shipyard = $plugin;
		$this->name = strtolower($name);
		$this->owner = $owner;
		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
		$this->world = $world;
		$this->create();
	}
	
	#API stuff
	public function getName() : string{
		return $this->name;
	}
	
	public function getOwner() : Player{
		return $this->owner;
	}

	public function getPos1() : array{
		return array($this->pos1->getX(),$this->pos1->getY(),$this->pos1->getZ());
	}
 
	public function getPos2() : array{
		return array($this->pos2->getX(),$this->pos2->getY(),$this->pos2->getZ());
	}
	
	public function getWorld() : string{
		return $this->world;
	}
	
	public function create() : void{
		$this->shipyard->ships[$this->getName()] = $this;
	}
	
	public function remove() : void{
		unset($this->shipyard->ships[$this->getName()]);
	}
	
}