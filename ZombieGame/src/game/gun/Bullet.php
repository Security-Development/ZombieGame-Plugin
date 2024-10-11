<?php
namespace game\gun;

use pocketmine\color\Color;
use pocketmine\entity\projectile\Snowball;
use pocketmine\world\particle\DustParticle;

class Bullet extends Snowball {
    protected function move(float $dx, float $dy, float $dz) : void {
        parent::move($dx, $dy, $dz);
        $this->getWorld()->addParticle($this->getPosition(), new DustParticle(new Color(255, 255, 255)));
    }

}