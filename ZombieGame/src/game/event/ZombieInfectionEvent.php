<?php

namespace game\event;

use pocketmine\event\Event;
use pocketmine\player\Player;

class ZombieInfectionEvent extends Event{
    private Player $victim;
    private ?Player $damager;

    public function __construct(Player $victim, ?Player $damager=null) {
        $this->victim = $victim;
        $this->damager = $damager;
    }

    public function getVictim(): Player {
        return $this->victim;
    }

    public function getDamager(): ?Player {
        return $this->damager;
    }

}