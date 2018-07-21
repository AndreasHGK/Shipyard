<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard;

use AndreasHGK\Shipyard\Shipyard;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\level\Level;

class Ship{
	
	private $shipyard;
	public $name;
	public $owner;
	#public $faction;
	public $pos1;
	public $pos2;
	public $level;
	public $blocks = [];
	public $class;
	
	public function __construct(Shipyard $plugin, string $name, string $owner, Vector3 $pos1, Vector3 $pos2, string $level){
		$this->shipyard = $plugin;
		$this->name = strtolower($name);
		$this->owner = $owner;
		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
		$this->level = $level;
		$this->create();
		$this->class = $this->getClass();
	}
	
	#API stuff
	public function setOwner(string $name) : void{
		$this->owner = $name;
	}
	
	public function getName() : string{
		return $this->name;
	}
	
	public function getOwner() : string{
		return $this->owner;
	}

	public function getPos1() : Vector3{
		return $this->pos1;
	}
 
	public function getPos2() : Vector3{
		return $this->pos2;
	}
	
	public function getPos1AsArray() : array{
		return array($this->pos1->getX(),$this->pos1->getY(),$this->pos1->getZ());
	}
 
	public function getPos2AsArray() : array{
		return array($this->pos2->getX(),$this->pos2->getY(),$this->pos2->getZ());
	}
	
	public function getBlocks() : array{
		$blocks = [];

		for($x = min($this->getPos1()->getX(), $this->getPos2()->getX()); $x <= max($this->getPos1()->getX(), $this->getPos2()->getX()); $x++){
			for($y = min($this->getPos1()->getY(), $this->getPos2()->getY()); $y <= max($this->getPos1()->getY(), $this->getPos2()->getY()); $y++){
				for($z = min($this->getPos1()->getZ(), $this->getPos2()->getZ()); $z <= max($this->getPos1()->getZ(), $this->getPos2()->getZ()); $z++){
						$pos = new Vector3($x, $y, $z);
						$block = $this->shipyard->getServer()->getLevelByName($this->level)->getBlock($pos);
					if($block->getId() != 0){	
						array_push($blocks, $block);
					}
				}
			}
		}
		return $blocks;
	}
	
	public function countCores() : int{
		$blocks = $this->getBlocks();
		$cores = 0;
		foreach($blocks as $block){
			if($block->getId() == 49){
				$cores++;
			}
		}
		return $cores;
	}
	
	public function getSize() : int{
		return count($this->getBlocks());
	}
	
	public function getClass() : string{
		switch(true){
			case $this->getSize() <= 20:
			return "drone";
			break;
			
			case $this->getSize() <= 250:
			return "fighter";
			break;
			
			case $this->getSize() <= 500:
			return "bomber";
			break;
			
			case $this->getSize() <= 10000:
			return "corvette";
			break;
			
			case $this->getSize() <= 50000:
			return "frigate";
			break;
			
			case $this->getSize() <= 250000:
			return "cruiser";
			break;
			
			#more than 250K
			default:
			return "capital";
			break;
		}
	}
	
	public function getBlocks3D() : array{
		$array = [];

		for($x = 0; $x <= abs($this->getPos1()->getX() - $this->getPos2()->getX()); $x++){
			for($y = 0; $y <= abs($this->getPos1()->getY() - $this->getPos2()->getY()); $y++){
				for($z = 0; $z <= abs($this->getPos1()->getZ() - $this->getPos2()->getZ()); $z++){
					$pos = new Vector3($x, $y, $z);
					$block = $this->shipyard->getServer()->getLevelByName($this->level)->getBlock($pos);
					array_push($array[$y[$z]], $block);
				}
			}
		}
		return $array;
	}
	
	public function getWorld() : string{
		return $this->level;
	}
	
	public function create() : void{
		$this->shipyard->ships[$this->getName()] = $this;
	}
	
	public function remove() : void{
		unset($this->shipyard->ships[$this->getName()]);
	}
	
}