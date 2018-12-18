<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard\weapons;

use AndreasHGK\Shipyard\Shipyard;
use AndreasHGK\Shipyard\Ship;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\block\Block;
use pocketmine\level\Level;
use pocketmine\entity\Entity;
use pocketmine\entity\PrimedTNT;

class Bomb{
	
	private $ship;
	public $pos;
	
	public function __construct(Ship $ship, Vector3 $pos){
		$this->ship = $ship;
		$this->pos = $pos;
	}
	
	public function fire() : void{
		Entity::createEntity("PrimedTNT", $this->ship->getWorld())->spawn();

	}