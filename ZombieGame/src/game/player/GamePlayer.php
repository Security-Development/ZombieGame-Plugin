<?php

namespace game\player;

use game\gun\GunBase;
use game\gun\M16;
use pocketmine\player\Player;

class GamePlayer {
    private StatManager $statManager;
    private Player $player;
    private GunBase $gun;
    private float $moveSpeed;

    public function __construct(Player $player, $gun=new M16(), float $moveSpeed=1.0) {
        $this->statManager = new StatManager(StatManager::NONE_LEVEL);
        $this->player = $player;
        $this->moveSpeed = $moveSpeed;
        $this->gun = $gun;
    }

    public function getStatManager(): StatManager { 
        return $this->statManager; 
    }

    public function getInstance(): Player {
        return $this->player;
    }

    public function setMoveSpeed(float $speed): void {
        $this->moveSpeed = $speed;
    }

    public function getMoveSpeed(): float {
        return $this->moveSpeed;
    }

    public function getGun(): GunBase {
        return $this->gun;
    }
}