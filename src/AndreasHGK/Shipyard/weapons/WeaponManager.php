<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard\weapons;

use AndreasHGK\Shipyard\Shipyard;
use AndreasHGK\Shipyard\Ship;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\block\Block;
use AndreasHGK\Shipyard\weapons\BaseWeapon;
use AndreasHGK\Shipyard\weapons\Cannon;

class WeaponManager{

    protected $weapons;
    protected $ship;
    protected $baseBlocks = [];
    protected $level;
    protected $shipyard;

    public function __construct(Ship $ship, array $baseBlocks){
        $this->ship = $ship;
        $this->baseBlocks = $baseBlocks;
        $this->shipyard = $ship->shipyard;
        $this->level = $this->shipyard->getServer()->getLevelByName($ship->getWorld());
    }

    public function init() : void{
        foreach($this->baseBlocks as $bb){
            $multiple = false;
            $power = 1;
            $pos = $bb->asPosition();
            $x = $pos->x;
            $y = $pos->y;
            $z = $pos->z;
            $face = $bb->getFac
            if($this->level->getBlockIdAt($x+1, $y, $z) == Block::IRON_BLOCK){
                $power = 2;
                $offset = 2;
                for($i = 0; $i < 5; ++$i){
                    if($this->level->getBlockIdAt($x+$offset, $y, $z) == Block::IRON_BLOCK){
                        $power = $offset;
                        $offset++;
                    }else{
                        break;
                    }
                }
            }
            if($this->level->getBlockIdAt($x-1, $y, $z) == Block::IRON_BLOCK){
                if($power != 1){
                    $multiple = true;
                }
                $power = 2;
                $offset = 2;
                for($i = 0; $i < 5; ++$i){
                    if($this->level->getBlockIdAt($x-$offset, $y, $z) == Block::IRON_BLOCK){
                        $power = $offset;
                        $offset++;
                    }else{
                        break;
                    }
                }
                if($multiple == true){
                    $power = 1;
                }
            }
            if($this->level->getBlockIdAt($x, $y, $z+1) == Block::IRON_BLOCK){
                if($power != 1){
                    $multiple = true;
                }
                $power = 2;
                $offset = 2;
                for($i = 0; $i < 5; ++$i){
                    if($this->level->getBlockIdAt($x, $y, $z+$offset) == Block::IRON_BLOCK){
                        $power = $offset;
                        $offset++;
                    }else{
                        break;
                    }
                }
                if($multiple == true){
                    $power = 1;
                }
            }
            if($this->level->getBlockIdAt($x, $y, $z-1) == Block::IRON_BLOCK){
                if($power != 1){
                    $multiple = true;
                }
                $power = 2;
                $offset = 2;
                for($i = 0; $i < 5; ++$i){
                    if($this->level->getBlockIdAt($x, $y, $z-$offset) == Block::IRON_BLOCK){
                        $power = $offset;
                        $offset++;
                    }else{
                        break;
                    }
                }
                if($multiple == true){
                    $power = 1;
                }
            }
        }
    }

    public function addWeapon() : void{

    }


}