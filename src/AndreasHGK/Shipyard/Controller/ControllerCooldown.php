<?php

declare(strict_types=1);

namespace AndreasHGK\Shipyard\Controller;

use AndreasHGK\Shipyard\Shipyard;
use pocketmine\scheduler\Task;

class ControllerCooldown extends Task{
	
	public $plugin;
    public $seconds = 0;
	public $player;
	

    public function __construct(Shipyard $plugin) {
        #parent::__construct($plugin);
        $this->plugin = $plugin;
    }

    public function getPlugin() {
        return $this->plugin;
    }

    public function onRun($tick) {
        if($this->seconds === 1) {
            $this->getPlugin()->removeTask($this->getTaskId());
        }
        $this->seconds++;
    }
}