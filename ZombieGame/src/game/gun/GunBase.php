<?php

namespace game\gun;

use pocketmine\player\Player;

abstract class GunBase {
    protected int $bulletCount;
    protected float $damge;
    protected int $rate;
    protected bool $reloadStat;
    protected int $reloadTime;
    
    public function __construct() {
        $this->bulletCount = 30;
        $this->damge = 1.0;
        $this->rate = 70;
        $this->reloadStat = false;
        $this->reloadTime = 5;
    }

    abstract public function startFire(Player $player): void;

    abstract public function stopFire(Player $player): void;

    abstract public function reload(Player $player): void;

    public function setBulletCount(int $bulletCount): void {
        $this->bulletCount = $bulletCount;
    }

    public function getBulletCount(): int {
        return $this->bulletCount;
    }

    public function setDamage(float $damage): self {
        $this->damage = $damage;
        return $this;
    }

    public function getDamage(): float {
        return $this->damge;
    }

    public function setRate(int $rate): self {
        $this->rate = $rate;
        return $this;
    }

    public function getRate(): int {
        return $this->rate;
    }

    public function transformRateToTicks(): int {
        return intval(max(1, min(20 - (19 / 99) * ($this->rate - 1), 20)));
    }

    public function getReloadStat(): bool {
        return $this->reloadStat;
    }

    public function setReloadTime(int $time): self {
        $this->reloadTime = $time;
        return $this;
    }

    public function getReloadTime(): int {
        return $this->reloadTime;
    }

}