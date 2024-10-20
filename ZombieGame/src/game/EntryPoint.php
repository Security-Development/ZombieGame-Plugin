<?php

namespace game;
use game\event\GameCoreEvent;
use game\game\Game;
use game\gun\Bullet;
use game\lobby\Lobby;
use pocketmine\entity\EntityDataHelper;
use pocketmine\entity\EntityFactory;
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\SingletonTrait;
use pocketmine\world\World;
use Ramsey\Uuid\Nonstandard\Uuid;

class EntryPoint extends PluginBase {
    use SingletonTrait;

    private static ?Game $game = null;
    private static ?Lobby $lobby = null;
    private static string $uuid;
    private static Config $cache;

    public function onEnable(): void {
        $this->saveDefaultConfig();
        $pluginManger = $this->getServer()->getPluginManager();
        $pluginManger->registerEvents(new GameCoreEvent, $this);
        self::$uuid = Uuid::uuid4()->toString();
        EntityFactory::getInstance()->register(Bullet::class,  function(World $world, CompoundTag $nbt): Bullet {
            return new Bullet(EntityDataHelper::parseLocation($nbt, $world), null, $nbt);
        }, ['Bullet', 'minecraft:bullet']);

        self::setInstance($this);
    }

    public function onLoad(): void {
        self::$cache = new Config($this->getDataFolder()."cache.yml", Config::YAML);
    }

    public static function getGame(): ?Game {
        return self::$game;
    }

    public static function setGame(?Game $game): void {
        self::$game = $game;
    }

    public static function getLobby(): ?Lobby {
        return self::$lobby;
    }

    public static function setLobby(?Lobby $lobby): void {
        self::$lobby = $lobby;
    }

    public static function checkGameStarted(): bool {
        return self::$game !== null;
    }

    public static function getUUID():string {
        return self::$uuid;
    }

    public static function getCache(): Config {
        return self::$cache;
    }

    public static function getRandomSpawnVector3(): Vector3 {
        $vectors = self::$cache->get("RespawnPoints");

        if (empty($vectors)) {
            throw new \Exception("리스폰 포인트가 없습니다.");
        }

        $randomIndex = array_rand($vectors);

        return unserialize($vectors[$randomIndex]);
    }

    public static function addRespwan(Vector3 $vector): void {
        $vectors = self::$cache->get("RespawnPoints");
        $vectors[] = $vector;

        self::$cache->set("RespawnPoints", $vectors);
        self::$cache->save();
    }

}