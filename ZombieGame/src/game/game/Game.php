<?php
namespace game\game;

use game\EntryPoint;
use game\event\GameStopEvent;
use game\event\ZombieInfectionEvent;
use game\event\ZombiePickEvent;
use game\player\GamePlayer;
use game\player\StatManager;
use game\schedulers\ScheduleManager;
use pocketmine\player\Player;

class Game {
    const RUNNING_TIME = 60 * 10;
    const COUNT_DOWN_TIME = 3;
    private array $gamePlayers;
    private int $gameTime;
    private ?GamePlayer $hostZomibe;

    public function __construct(array $players, int $time) {
        $this->gamePlayers = array_map(function(Player $player) : GamePlayer {
            return new GamePlayer($player);
        }, $players);

        $this->gameTime = $time + self::COUNT_DOWN_TIME;
        $this->hostZomibe = null;
    }

    public function start() : void {
        ScheduleManager::runTick(EntryPoint::getUUID(), function(): void {
            if (1 < $this->gameTime) {
                if ($this->gameTime === self::RUNNING_TIME) {
                    $hostZombie = $this->gamePlayers[mt_rand(0, $this->getPlayerCount() - 1)];
                    $this->hostZomibe = $hostZombie;
                    $this->infect($hostZombie->getInstance());
                    (new ZombiePickEvent($hostZombie->getInstance()))->call();
                    $hostZombie->getInstance()->sendTitle("§4당신은 숙주입니다.");
                    $this->notifyGamePlayers(sprintf("%s님께서 숙주좀비가 되셨습니다...\n", $hostZombie->getInstance()->getName()));
                }
                else if ($this->gameTime <= self::RUNNING_TIME) {
                    $zombieCount = $this->getPlayerCountByInfectionStatus(true);
                    if ($zombieCount < 1) {
                        $this->stop();
                        $this->notifyGamePlayers("좀비들이 전부 나가 게임이 종료 되었습니다.");
                    }
                    else if ($this->getPlayerCount() < 1) {
                        $this->stop();
                        $this->notifyGamePlayers("인원이 없어 게임이 종료되었습니다.");
                    }
                    else if ($this->getPlayerCountByInfectionStatus(false) < 1) {
                        $this->stop();
                        $this->notifyGamePlayers("좀비 승리로 게임이 끝났습니다.");
                    }
                    else {
                        $this->tipGamePlayers(sprintf("남은 게임시간 : %d초\n좀비 : %d | 인간 : %d", $this->gameTime, $this->getPlayerCountByInfectionStatus(true), $this->getPlayerCountByInfectionStatus(false)));   
                    }
                }
                else {
                    $this->tipGamePlayers(sprintf("%d초 후에 숙주 좀비가 선택됩니다.", $this->gameTime - self::RUNNING_TIME));
                }
            } else {
                $this->stop();
                $this->notifyGamePlayers("인간 승리로 게임이 끝났습니다.");
            }
            $this->gameTime--;
        });
    }

    public function stop() : void {
        ScheduleManager::cancelTick(EntryPoint::getUUID());
        (new GameStopEvent($this->gamePlayers))->call();
        
    }

    public function tipGamePlayers(string $message) : void {
        foreach( $this->gamePlayers as $player) {
            if ($player->getInstance()->isOnline()) {
                $player->getInstance()->sendTip($message);
            }
        }
    }

    public function notifyGamePlayers(string $message) : void {
        foreach( $this->gamePlayers as $player) {
            if ($player->getInstance()->isOnline()) {
                $player->getInstance()->sendMessage($message);
            }
        }
    }

    public function getPlayerCount(): int {
        return count( $this->gamePlayers);
    }

    public function getPlayerInstance(Player $player): ?GamePlayer {
        $filteredPlayers = array_filter( $this->gamePlayers, function(GamePlayer $gamePlayer) use ($player): bool {
            return $gamePlayer->getInstance()->getId() === $player->getId();
        });
    
        return count($filteredPlayers) > 0 ? reset($filteredPlayers) : null;
    }   

    public function isGameParticipant(Player $player): bool {
        return in_array($this->getPlayerInstance($player), $this->gamePlayers, true);
    }

    public function quit(Player $player): void {
        $key = array_search($this->getPlayerInstance($player), $this->gamePlayers, true);
        if ($key !== false) {
            array_splice($this->gamePlayers, $key, 1);
            $this->notifyGamePlayers(sprintf("%s님께서 게임에서 나가셨습니다.", $player->getName()));
        }
    }

    public function getPlayerCountByInfectionStatus(bool $isInfected): int {
        $count = 0;
    
        foreach ($this->gamePlayers as $player) {
            if ($player->getStatManager()->isInfected() === $isInfected && $player->getStatManager()->getLevel() !== StatManager::ULTIMATE_LEVEL) {
                $count++;
            }
        }
        return $count;
    }

    public function infect(Player $victim, ?Player $damager=null): void {
        $this->getPlayerInstance($victim)->getStatManager()->setLevel(StatManager::HIGH_LEVEL);

        (new ZombieInfectionEvent($victim, $damager))->call();
    }

    public function becomeGhost(Player $player): void {
        $this->getPlayerInstance($player)->getStatManager()->setLevel(StatManager::ULTIMATE_LEVEL);
    }

    public function isZombie(Player $player): bool {
        return $this->getPlayerInstance($player)->getStatManager()->isInfected();

    }

    public function isHuman(Player $player): bool {
        return !$this->getPlayerInstance($player)->getStatManager()->isInfected();
    }

}