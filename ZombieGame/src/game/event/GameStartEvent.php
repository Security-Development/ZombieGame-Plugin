<?php

namespace game\event;

use pocketmine\event\Event;

class GameStartEvent extends Event {
    private array $players;

    public function __construct(array $players) {
        $this->players = $players;
    }

    public function getPlayers(): array {
        return $this->players;
    }

    public function sendMessage(string $message): void {
        foreach($this->players as $player) {
            $player->sendMessage($message);
        }
    }
}