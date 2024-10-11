<?php

namespace game\player;

class StatManager {
    const NONE_LEVEL = 0;
    const LOW_LEVEL = 1;
    const MEDIUM_LEVEL = 2;
    const HIGH_LEVEL = 3;
    const ULTIMATE_LEVEL = 4;
    
    private int $level;
    
    public function __construct(int $level) {
        $this->setLevel($level);
    }
    public function setLevel(int $level): void {
        if ($level < self::NONE_LEVEL || $level > self::ULTIMATE_LEVEL) {
            throw new \InvalidArgumentException("유효하지 않은 레벨입니다.");
        }

        $this->level = $level;
    }

    public function getLevel(): int {
        return $this->level;
    }

    public function isInfected() : bool {
        return self::NONE_LEVEL < $this->level && $this->level < self::ULTIMATE_LEVEL;
    }

    public function isGhost(): bool {
        return $this->level === self::ULTIMATE_LEVEL;
    }


}