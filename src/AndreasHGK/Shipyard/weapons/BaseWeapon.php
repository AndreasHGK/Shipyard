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

abstract class BaseWeapon
{

    CONST TYPE = 0;
    protected $ship;
    protected $pos;
    protected $power;

    public function __construct(Ship $ship, Vector3 $pos){
        $this->ship = $ship;
        $this->pos = $pos;
    }

    abstract public function fire() : void{}

    public function getHostShip() : Ship{
        return $this->ship;
    }

    public function getPower() : int{
        return $this->power;
    }

    public function setPower(int $power) : void{
        $this->power = $power;
    }

    public function getType() : int{
        return self::TYPE;
    }

    public function getPos() : Vector3{
        return $this->pos;
    }

    public function getWorld() : String{
        $this->getHostShip()->getWorld();
    }

}