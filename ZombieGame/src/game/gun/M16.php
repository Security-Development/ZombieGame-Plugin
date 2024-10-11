<?php

namespace game\gun;

use game\gun\GunBase;
use game\schedulers\ScheduleManager;
use pocketmine\item\Bow;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\Player;
use pocketmine\world\sound\GhastShootSound;


class M16 extends GunBase {

    public function __construct() {
        parent::__construct();
        $this->setBulletCount(25);
        $this->setRate(75);
    }

    public function startFire(Player $player): void {
        ScheduleManager::runTick($player->getUniqueId(), function()use($player): void {
            $handItem = $player?->getInventory()->getItemInHand();

            if ($handItem->getTypeId() == ItemTypeIds::BOW) {
                $duration = $player->getItemUseDuration();
                
                if ($this->reloadStat) {
                    $this->reload($player);
                }
                else if ($this->bulletCount < 1) {
                    $this->reloadStat = true;
                }
                else if ($duration > 0) {
                    $location = $player->getLocation();
                    $location->y += 1.5;
                    $direction = $player->getDirectionVector();
                    $snowball = new Bullet($location, $player);
                    $snowball->setGravity(0.0001);
                    $snowball->setMotion($direction->multiply(3.14 * 5));
                    $snowball->spawnToAll();
                    $player->getWorld()->addSound($player->getPosition(), new GhastShootSound());
                    $player->sendTitle(" ", sprintf("                                 탄약수: %d", $this->bulletCount));
                    $this->bulletCount--;
                }else{ 
                    $this->stopFire($player);
                }
            } 
            else {
                echo "7\n";
                $this->stopFire($player);
                
            }
            
        }, $this->transformRateToTicks());
    }

    public function stopFire(Player $player): void {
        ScheduleManager::cancelTick($player->getUniqueId());

    }

    public function reload(Player $player): void {
        ScheduleManager::runTick($player->getUniqueId(), function() use($player): void {
            $handItem = $player->getInventory()->getItemInHand();

            if ($handItem instanceof Bow) {
                if($this->reloadTime < 1) {
                    ScheduleManager::cancelTick($player->getUniqueId());
                    $this->bulletCount = 30;
                    $this->reloadTime = 5;
                    $this->reloadStat = false;
                    $player->sendTitle(" ", "                                 장전 완료");
                } else { 
                    $player->sendTitle(" ", "                                 장전 중");
                }
                $this->reloadTime--;
            } else {
                $this->reloadTime = 5;
                $this->reloadStat = false;
                $this->stopFire($player);
            }
        });
    }
}
