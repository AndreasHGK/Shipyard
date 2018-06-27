<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard;

use AndreasHGK\Shipyard\Shipyard;
use pocketmine\scheduler\Task;

class ControllerCooldown extends Task{
	
	public $plugin;
    public $ticks = 0;
	public $player;
	

    public function __construct(Shipyard $plugin) {
        $this->plugin = $plugin;
    }

    public function getPlugin() {
        return $this->plugin;
    }

    public function onRun($tick) {
        if($this->ticks === 10) {
            $this->getPlugin()->removeTask($this->getTaskId());
        }
        $this->ticks++;
    }
}