<?php

namespace game\event;

use pocketmine\event\player\PlayerEvent;
use pocketmine\player\Player;

class ZombiePickEvent extends PlayerEvent {
    public function __construct(Player $player) {
        $this->player = $player;
    }

}