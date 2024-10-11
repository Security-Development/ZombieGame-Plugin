<?php

namespace game\schedulers;
use game\EntryPoint;
use game\schedulers\GameTask;

class ScheduleManager {
    private static array $tasks = [];
    public static function runTick(string $key, callable $callback, int $tick=20) : void {
        if (isset(self::$tasks[$key])) {
            self::cancelTick($key);
        }

        $task = new GameTask($callback);
        self::$tasks[$key] = $task;
        EntryPoint::getInstance()->getScheduler()->scheduleRepeatingTask($task, $tick);
    }

    public static function cancelTick(string $key): void {
        if (isset(self::$tasks[$key])) {
            $task = self::$tasks[$key]; 
            try {
                $task->getHandler()->cancel(); 
                unset(self::$tasks[$key]); 
            } catch (\Exception $e) {
                error_log("태스크를 캔슬시키는 중에 에러가 발생했습니다." . $e->getMessage());
            }
        }
    }

}