<?php

namespace game\skin;

use game\EntryPoint;
use pocketmine\entity\Skin;
use pocketmine\player\Player;

class SkinManager {
    const ZOMBIE = "GameZombie";
    const HUMAN = "GameHuman";
    private static function setSkin(Player $player, string $type): void {
        $dataFolder = EntryPoint::getInstance()->getDataFolder()."/".$type.".";
        $imagePath = $dataFolder."png";

        $img = @imagecreatefrompng($imagePath);
        if ($img === false) {
            throw new \RuntimeException("이미지를 불러오는 데 실패했습니다: $imagePath");
        }

        $skinBytes = "";
        $width = imagesx($img);
        $height = imagesy($img);
 
        for ($y = 0; $y < $height; $y++) {
            for ($x = 0; $x < $width; $x++) {
                $colorAt = imagecolorat($img, $x, $y);
                $alpha = (($colorAt >> 24) & 0xFF) ^ 0xFF;
                $red = ($colorAt >> 16) & 0xFF;
                $green = ($colorAt >> 8) & 0xFF;
                $blue = $colorAt & 0xFF;

                $skinBytes .= chr($red) . chr($green) . chr($blue) . chr($alpha);
            }
        }
        
        @imagedestroy($img);

        $skinId = $player->getSkin()->getSkinId();
        $jsonData = file_get_contents($dataFolder . "json");
        $player->setSkin(new Skin($skinId, $skinBytes, "", "geometry.$type", $jsonData));
        $player->sendSkin();
    }

    public static function setZombieSkin(Player $player): void {
        self::setSkin($player, self::ZOMBIE);
    }

    public static function setHumanSkin(Player $player): void {
        self::setSkin($player, self::HUMAN);
    }
}