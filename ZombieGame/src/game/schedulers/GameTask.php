<?php

namespace game\schedulers;

use pocketmine\scheduler\Task;

class GameTask extends Task {
    private $callback;

    public function __construct(callable $callback) {
        $this->callback = $callback;
    }

    public function onRun() : void {
        ($this->callback)();
    }

}