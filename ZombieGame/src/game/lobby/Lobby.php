<?php
namespace game\lobby;
use game\EntryPoint;
use game\event\GameStartEvent;
use game\game\Game;
use game\schedulers\ScheduleManager;
use pocketmine\player\Player;

class Lobby {
    const COUNT_DOWN_TIME = 3;
    const MINIMUM_PLAYERS = 2;
    private Player $lobbyHost;
    private array $lobbyPlayers;
    private int $countdown;

    public function __construct(Player $player) {
        $this->lobbyHost = $player;
        $this->lobbyPlayers = [];
        $this->countdown = self::COUNT_DOWN_TIME;

        ScheduleManager::runTick(EntryPoint::getUUID(), function() {
            if ((self::MINIMUM_PLAYERS - 1) < EntryPoint::getLobby()->getLobbyPlayerCount()) {
                if ($this->countdown <= 0 ) {
                    EntryPoint::setGame(new Game($this->lobbyPlayers, Game::RUNNING_TIME));
                    EntryPoint::getGame()->start();
                    (new GameStartEvent($this->lobbyPlayers))->call();
                }
                else {
                    $countDownText = sprintf("게임이 %d초 후에 시작됩니다.", $this->countdown);
                    $this->tipLobbyPlayers($countDownText);
                    $this->countdown--;
                }
            } 
            else {
                if ($this->countdown !== self::COUNT_DOWN_TIME) {
                    $this->countdown = self::COUNT_DOWN_TIME;
                }

                $lobbyPlayerCountText = sprintf("게임 대기 인원 : %d명", $this->getLobbyPlayerCount());
                $this->tipLobbyPlayers($lobbyPlayerCountText);
            }
        });
    }

    public function stop() : void {
        ScheduleManager::cancelTick(EntryPoint::getUUID());
        EntryPoint::setLobby(null);
        $this->notifyLobbyPlayers(sprintf("대기방이 삭제되었습니다.")); 
    }

    public function getHost(): Player {
        return $this->lobbyHost;
    }

    public function isHost(Player $player): bool {
        return $player->getId() == $this->lobbyHost->getId();
    }  
    
    public function join(Player $player): void {
        if (!in_array($player, $this->lobbyPlayers, true)) {
            array_push($this->lobbyPlayers, $player);
        } 

        $this->lobbyPlayers = array_values($this->lobbyPlayers); // reordering
    }

    public function quit(Player $player): void {
        $key = array_search($player, $this->lobbyPlayers, true);
        if ($key !== false) {
            array_splice($this->lobbyPlayers, $key, 1);
            $this->notifyLobbyPlayers(sprintf("%s님께서 로비에서 나가셨습니다.", $player->getName()));
            var_dump($this->lobbyPlayers);
        }
    }

    public function isLobbyParticipant(Player $player): bool {
        return in_array($player, $this->lobbyPlayers, true);
    }

    public function getLobbyPlayerCount(): int {
        return count($this->lobbyPlayers);
    }

    public function getLobbyPlayers(): array {  
        return $this->lobbyPlayers;
    }

    public function tipLobbyPlayers(string $message) : void {
        foreach($this->lobbyPlayers as $player) {
            if ($player->isOnline()) {
                $player->sendTip($message);
            }
            
        }
    }
    public function notifyLobbyPlayers(string $message) : void {
        foreach($this->lobbyPlayers as $player) {
            if ($player->isOnline()) {
                $player->sendMessage($message);
            }
        }
    }



}