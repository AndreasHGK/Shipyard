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
	public $pos1;
	public $pos2;
	public $level;
	public $blocks = [];
	
	public function __construct(Shipyard $plugin, string $name, Player $owner, Vector3 $pos1, Vector3 $pos2, string $level){
		$this->shipyard = $plugin;
		$this->name = strtolower($name);
		$this->owner = $owner;
		$this->pos1 = $pos1;
		$this->pos2 = $pos2;
		$this->level = $level;
		$this->create();
	}
	
	#API stuff
	public function getName() : string{
		return $this->name;
	}
	
	public function getOwner() : Player{
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
	
	public function getSize() : int{
		#will be used for ship classes in the future
		$length = abs($this->pos1->getX() - $this->pos2->getX());
		$height = abs($this->pos1->getY() - $this->pos2->getY());
		$width = abs($this->pos1->getZ() - $this->pos2->getZ());
		$size = $length*$height*$width;
		return $size;
	}
	
	public function getBlocks() : array{
		$blocks = [];

		for($x = min($this->getPos1()->getX(), $this->getPos2()->getX()); $x <= max($this->getPos1()->getX(), $this->getPos2()->getX()); $x++){
			for($y = min($this->getPos1()->getY(), $this->getPos2()->getY()); $y <= max($this->getPos1()->getY(), $this->getPos2()->getY()); $y++){
				for($z = min($this->getPos1()->getZ(), $this->getPos2()->getZ()); $z <= max($this->getPos1()->getZ(), $this->getPos2()->getZ()); $z++){
					$pos = new Vector3($x, $y, $z);
					$block = $this->shipyard->getServer()->getLevelByName($this->level)->getBlock($pos);
					array_push($blocks, $block);
				}
			}
		}
		return $blocks;
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