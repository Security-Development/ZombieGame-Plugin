<?php

namespace game\ui;
use game\EntryPoint;
use game\lobby\Lobby;
use game\schedulers\ScheduleManager;
use jojoe77777\FormAPI\SimpleForm;
use pocketmine\player\Player;

class GameMenu extends SimpleFormBase {

    public function degsin(SimpleForm $form, ?Player $player=null) : void {
        $form->setTitle("좀비게임 메뉴");
        $form->addButton("대기방 생성");
        $form->addButton("대기방 삭제");
        $form->addButton("대기방 입장");
    }

    public function handleSelection(Player $player, ?int $data) : void {
        if (is_null($data)) {
            $player->sendMessage("게임메뉴를 닫으셨습니다.");
            return;
        }

        if ($data == 0) {
            if ( EntryPoint::checkGameStarted()) {
                $player->sendMessage("게임이 이미 시작되었습니다.\n다음 게임때 참여해보세요!");
            }
            else if (is_null(EntryPoint::getLobby())) {
                EntryPoint::setLobby(new Lobby($player));
                EntryPoint::getLobby()->join($player);
                $notificationMessage = sprintf("%s님께서 좀비게임 대기방을 생성하셨습니다.\n재미있는 좀비게임에 참여해보세요!", $player->getName());
                $player->getServer()->broadcastMessage($notificationMessage);
            } 
            else {
                if (EntryPoint::getLobby()->isHost($player)) {
                    $player->sendMessage("당신이 이미 대기방을 만드셨습니다.");
                } 
                else {
                    $player->sendMessage(sprintf("이미 %s님께서 대기방을 생성하셨습니다.\n게임에 참여해보세요!", EntryPoint::getLobby()->getHost()->getName()));
                }
            }
        } 
        else if ($data == 1) {
            if (is_null(EntryPoint::getLobby())) {
                $player->sendMessage("대기방이 존재하지 않습니다.");
            }
            else if(EntryPoint::getLobby()->isHost($player)) {
                ScheduleManager::cancelTick(EntryPoint::getUUID());
                $notificationMessage = sprintf("%s님께서 좀비게임 대기방을 삭제했습니다.\n좀비게임을 하고 싶으신 분은 게임 대기방을 생성해보세요!", $player->getName());
                EntryPoint::getLobby()->notifyLobbyPlayers($notificationMessage);
                EntryPoint::setLobby(null);
            } 
            else {
                $player->sendMessage("당신은 방장이 아니기 때문에 대기방을 삭제할 수 없습니다.");
            }
        }
        else if ($data == 2) {
            if ( EntryPoint::checkGameStarted()) {
                $player->sendMessage("게임이 이미 시작되었습니다.\n다음 게임때 참여해보세요!");
            }
            else if (is_null(EntryPoint::getLobby())) {
                $player->sendMessage("대기방이 존재하지 않습니다.");
            }
            else if (in_array($player, EntryPoint::getLobby()->getLobbyPlayers())) {
                $player->sendMessage("당신은 이미 대기방에 참여했습니다.");
            } else {
                EntryPoint::getLobby()?->join($player);
                $player->sendMessage("좀비게임 대기방에 참석했습니다.");
                EntryPoint::getLobby()->notifyLobbyPlayers(sprintf("%s님께서 대기방에 들어오셨습니다.", $player->getName()));

            }
            
        }

    }

}
