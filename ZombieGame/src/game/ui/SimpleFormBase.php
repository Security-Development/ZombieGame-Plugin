<?php

namespace game\ui;

use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;

abstract class SimpleFormBase {

    public static function display(Player $player) : void {
        $instance = new static();
        $form = new SimpleForm(function (Player $player, ?int $data) use ($instance) {
            $instance->handleSelection($player, $data);
        });

        $instance->degsin($form, $player);
        
        $player->sendForm($form);

        $instance = null;
    }

    abstract public function degsin(SimpleForm $form, ?Player $player=null) : void;

    abstract public function handleSelection(Player $player, ?int $data) : void;
}
