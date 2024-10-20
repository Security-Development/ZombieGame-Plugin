<?php

namespace game\event;

use game\EntryPoint;
use game\gun\Bullet;
use game\schedulers\ScheduleManager;
use game\skin\SkinManager;
use game\ui\GameMenu;
use pocketmine\entity\projectile\Arrow;
use pocketmine\event\entity\EntityDamageByChildEntityEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\ProjectileLaunchEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\inventory\transaction\action\DropItemAction;
use pocketmine\item\Bow;
use pocketmine\item\Item;
use pocketmine\item\ItemIdentifier;
use pocketmine\item\ItemTypeIds;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\world\particle\LavaParticle;
use pocketmine\world\particle\RedstoneParticle;
use pocketmine\world\sound\AnvilFallSound;
use pocketmine\world\sound\GhastSound;

class GameCoreEvent implements Listener { 

    public function onInfection(ZombieInfectionEvent $event) {
        $damager = $event->getDamager();
        $victim = $event->getVictim();
        $victim->getInventory()->clearAll();
        SkinManager::setZombieSkin($victim);
        $victim->setMaxHealth(60);
        $victim->setHealth(60);
        if ($damager != null) {
            $victim->getWorld()->addSound($victim->getPosition(), new GhastSound());

            for($i = 0; $i < 16; $i++) {
                $victim->getWorld()->addParticle($victim->getPosition()->add(mt_rand(-50, 50)/100, 1 + mt_rand(-50, 50)/100, mt_rand(-50, 50)/100), new LavaParticle());
            }

            EntryPoint::getGame()->notifyGamePlayers(sprintf("%s님께서 %s님을 감염시켰습니다.", $damager->getName(), $victim->getName()));
        }
    }

    public function onGameStart(GameStartEvent $event): void {
        $players = $event->getPlayers();

        foreach($players as $player) {
            $player->setMaxHealth(20);
            $player->setHealth(20);
            $player->setGamemode(GameMode::SURVIVAL());
            $player->teleport(EntryPoint::getRandomSpawnVector3());
            $player->getInventory()->clearAll();
            $player->getInventory()->setItem(0, (new Bow(new ItemIdentifier(ItemTypeIds::BOW)))->setCustomName("M16"));
            $player->getInventory()->setItem(9, (new Item(new ItemIdentifier(ItemTypeIds::ARROW)))->setCustomName("탄환"));
        }

        $event->sendMessage("게임이 시작 되었습니다.");
        EntryPoint::setLobby(null);
    }

    public function onGameStop(GameStopEvent $event): void {
        $players = $event->getPlayers();

        foreach($players as $player) {
            $player->getInstance()->setMaxHealth(20);
            $player->getInstance()->setHealth(20);
            $player->getInstance()->teleport($player->getInstance()->getWorld()->getSafeSpawn());
            $player->getInstance()->setGamemode(GameMode::SURVIVAL());
            SkinManager::setHumanSkin(player: $player->getInstance());
            $player->getInstance()->getInventory()->clearAll();
            $player->getInstance()->getInventory()->setItem(8, (new Item(new ItemIdentifier(ItemTypeIds::FEATHER)))->setCustomName("게임메뉴"));
        }

        $event->sendMessage("게임이 끝났습니다.");
        EntryPoint::setGame(null);
    }

    public function onSneak(PlayerToggleSneakEvent $event) : void {
        $player = $event->getPlayer();

        // $player->sendMessage("리스폰 목록에 추가함");
        // EntryPoint::addRespwan($player->getPosition()->asVector3());
        
        if ($player->getInventory()->getItemInHand()->getTypeId() == ItemTypeIds::FEATHER) {
            GameMenu::display($player);
        }

    }

    public function onUse(PlayerItemUseEvent $event) : void {
        $player = $event->getPlayer();
        $item = $event->getItem();

        if (EntryPoint::getGame()?->isHuman($player)) {
            if($item->getTypeId() == ItemTypeIds::BOW && EntryPoint::getGame()->getPlayerInstance($player)?->getGun()->getReloadStat() ^ 1) {
                EntryPoint::getGame()->getPlayerInstance($player)?->getGun()->startFire($event->getPlayer());
            }
        }
    }

    public function onLaunch(ProjectileLaunchEvent $event) : void {
        if( $event->getEntity() instanceof Arrow) {
            $event->cancel();
        }
    }

    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event): void {
        $damager = $event->getDamager();
        $victim = $event->getEntity();

        if($damager instanceof Player && $victim instanceof Player) {
            if (EntryPoint::getGame()?->isZombie($damager) && EntryPoint::getGame()?->isHuman($victim)) {
                if ( $damager->getPosition()->distance($victim->getPosition()) > 2) {
                    $event->cancel();
                } 
                else {
                    EntryPoint::getGame()->infect($victim, $damager);
                }
            }
        }
    }

    public function onEntityDamage(EntityDamageEvent $event): void {
        if ($event->isCancelled()) {
			return;
		}    
		$entity = $event->getEntity();
	    
		if ($entity instanceof Player and $entity->getHealth() - $event->getBaseDamage() <= 0) {
			if ($entity->getGamemode() === GameMode::CREATIVE()){
				return;
            }
            $time = 5;
            $event->cancel();
            $entity->setGamemode(GameMode::SPECTATOR());
            if (EntryPoint::getGame()?->isZombie($entity)) {
                ScheduleManager::runTick($entity->getUniqueId(), function() use($entity, &$time): void {

                    if ($time < 1) {
                        $entity->setGamemode( GameMode::SURVIVAL());
                        $entity->setHealth(60);
                        $entity->teleport(EntryPoint::getRandomSpawnVector3() );
                        ScheduleManager::cancelTick($entity->getUniqueId());
                    } else {
                        $entity->sendTitle(" ", sprintf("리스폰 중... %d초", $time));
                    }
                    
                    $time--;
                }, 20);
            } 
            else if(EntryPoint::getGame()?->isHuman($entity)) {
                EntryPoint::getGame()->becomeGhost($entity);
                $entity->sendTitle("당신은 죽었습니다.");
            } else {
                $entity->setGamemode(GameMode::SURVIVAL());
                $entity->teleport($entity->getWorld()->getSafeSpawn());
            }
		}
    }

    public function onChildEntityDamage(EntityDamageByChildEntityEvent $event) : void {
        $damager = $event->getDamager();
        $victim = $event->getEntity();
        $child = $event->getChild();

        if (EntryPoint::checkGameStarted()) {
            if ($victim instanceof Player && $damager instanceof Player) {

                if (EntryPoint::getGame()?->isZombie($victim)) {
                    $event->setKnockBack(0.115);
                }
            }
            if ($child instanceof Bullet && $damager instanceof Player) {
                $i = 0;
                for($i = 0; $i < 10; $i++) {
                    $victim->getWorld()->addParticle($victim->getPosition()->add(mt_rand(-50, 50)/100, 1 + mt_rand(-50, 50)/100, mt_rand(-50, 50)/100), new RedstoneParticle());
                }
                $damager->getWorld()->addSound($damager->getPosition(), new AnvilFallSound());
                $event->setBaseDamage(EntryPoint::getGame()->getPlayerInstance($damager)?->getGun()->getDamage() ?? 0);
                $event->setAttackCooldown(5);
            } 
        }
    }


    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        $event->setXpDropAmount(0);
        $event->setDrops([]);
    }

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $player->teleport($player->getWorld()->getSafeSpawn());
        $player->setMaxHealth(20);
        $player->setHealth(20);
        $player->setGamemode(GameMode::SURVIVAL());
        $player->setNameTagVisible(false);
        $player->setNameTagAlwaysVisible(false);
        $player->getInventory()->clearAll();
        $player->getInventory()->setItem(8, (new Item(new ItemIdentifier(ItemTypeIds::FEATHER)))->setCustomName("게임메뉴"));
        SkinManager::setHumanSkin($player);
    }

    public function onQuit(PlayerQuitEvent $event): void {
        $player = $event->getPlayer();
        
        if (EntryPoint::getLobby()?->isHost($player)) {
            EntryPoint::getLobby()->stop();
        }

        if (EntryPoint::getLobby()?->isLobbyParticipant($player)) {
            EntryPoint::getLobby()->quit($player);
        }

        if (EntryPoint::getGame()?->isGameParticipant($player)) {
            EntryPoint::getGame()->quit($player);
        }
    }

    public function onHunger(PlayerExhaustEvent $event): void {
        $event->cancel();   
    }

    public function onDrop(PlayerDropItemEvent $event): void {
        $item = $event->getItem();
        if ($item->getTypeId() === ItemTypeIds::FEATHER||
        $item->getTypeId() === ItemTypeIds::BOW ||
        $item->getTypeId() === ItemTypeIds::ARROW
        ) {
            $event->cancel(); 
        }
    }
}