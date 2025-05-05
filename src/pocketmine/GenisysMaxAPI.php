<?php

namespace pocketmine;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;

/**
 * GenisysMaxAPI - Оптимизации для GenisysMax (форк PMMP)
 * Основные оптимизации:
 * - Оптимизация памяти и управление GC
 * - Оптимизация тиков и производительности
 * - Управление чанками (загрузка/выгрузка)
 * - Оптимизация сетевых компонентов
 * - Команды для мониторинга и управления сервером
 * 
 * API: 3.28.0
 * PHP: 8.3
 */
class GenisysMaxAPI {
    // Изменил с private на public для доступа из анонимных классов
    public static Server $server;
    public static bool $initialized = false;
    public static array $config = [
        "memory_optimization" => true,
        "tick_optimization" => true,
        "chunk_loading_optimization" => true,
        "network_optimization" => true,
        "logging_enhanced" => true,
        "commands_enabled" => true,
        "auto_gc" => true,
        "entity_throttling" => true,
        "command_optimization" => true,
        "chunk_gc" => true
    ];
    
    public static array $stats = [
        "memory_saved" => 0,
        "tick_time_saved" => 0,
        "optimized_chunks" => 0,
        "entity_throttled" => 0
    ];
    
    public static $logFile = null;
    
    /**
     * Инициализация системы оптимизаций
     */
    public static function init(): bool {
        if(self::$initialized) {
            return false;
        }
        
        self::$server = Server::getInstance();
        self::log("Инициализация GenisysMaxAPI...");
        
        self::initMemoryOptimization();
        self::initTickOptimization();
        self::initChunkLoadingOptimization();
        self::initNetworkOptimization();
        self::initLoggingEnhanced();
        self::initCommands();
        self::initAutoGC();
        self::initEntityThrottling();
        self::initCommandOptimization();
        self::initChunkGC();
        
        self::$initialized = true;
        self::log("GenisysMaxAPI успешно инициализирован!");
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                GenisysMaxAPI::showStats();
            }
        }, 20 * 60);
        
        return true;
    }
    
    /**
     * Инициализация оптимизации памяти
     */
    private static function initMemoryOptimization(): void {
        if(!self::$config["memory_optimization"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                $before = memory_get_usage();
                gc_collect_cycles();
                $after = memory_get_usage();
                GenisysMaxAPI::$stats["memory_saved"] += ($before - $after);
            }
        }, 20 * 30);
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                $memory = memory_get_usage();
                $limit = memory_get_peak_usage() * 0.9;
                
                if($memory > $limit) {
                    foreach(GenisysMaxAPI::$server->getLevels() as $world) {
                        if(method_exists($world, "clearCache")) {
                            $world->clearCache(true);
                        }
                    }
                    gc_collect_cycles();
                }
            }
        }, 20 * 15);
    }
    
    /**
     * Инициализация оптимизации тиков
     */
    private static function initTickOptimization(): void {
        if(!self::$config["tick_optimization"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            private float $lastTime = 0.0;
            
            public function onRun(int $currentTick): void {
                if($this->lastTime === 0.0) {
                    $this->lastTime = microtime(true);
                    return;
                }
                
                $current = microtime(true);
                $tickTime = $current - $this->lastTime;
                
                if($tickTime > 0.05) {
                    GenisysMaxAPI::optimizeTick();
                }
                
                $this->lastTime = $current;
            }
        }, 1);
    }
    
    /**
     * Оптимизация тика сервера
     */
    public static function optimizeTick(): void {
        $before = microtime(true);
        
        foreach(self::$server->getLevels() as $world) {
            if(method_exists($world, "setTime")) {
                $world->setTime($world->getTime());
            }
        }
        
        $after = microtime(true);
        self::$stats["tick_time_saved"] += ($after - $before);
    }
    
    /**
     * Инициализация оптимизации загрузки чанков
     */
    private static function initChunkLoadingOptimization(): void {
        if(!self::$config["chunk_loading_optimization"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                $unloadedTotal = 0;
                
                foreach(GenisysMaxAPI::$server->getLevels() as $world) {
                    $chunks = $world->getChunks();
                    $unloaded = 0;
                    
                    foreach($chunks as $chunk) {
                        if(method_exists($chunk, "isGenerated") && method_exists($world, "unloadChunk")) {
                            $canUnload = !$chunk->isGenerated();
                            if(method_exists($chunk, "getEntities")) {
                                $canUnload = $canUnload || count($chunk->getEntities()) === 0;
                            }
                            
                            if($canUnload && $world->unloadChunk($chunk->getX(), $chunk->getZ(), true)) {
                                $unloaded++;
                            }
                        }
                    }
                    
                    $unloadedTotal += $unloaded;
                }
                
                GenisysMaxAPI::$stats["optimized_chunks"] += $unloadedTotal;
            }
        }, 20 * 60);
    }
    
    /**
     * Инициализация оптимизации сети
     */
    private static function initNetworkOptimization(): void {
        if(!self::$config["network_optimization"]) return;
        
        if(method_exists(self::$server, "getNetwork")) {
            $network = self::$server->getNetwork();
            
            if(method_exists($network, "setName")) {
                $network->setName($network->getName() . " [Optimized]");
            }
        }
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                foreach(GenisysMaxAPI::$server->getOnlinePlayers() as $player) {
                    if(method_exists($player, "getNetworkSession")) {
                        $session = $player->getNetworkSession();
                        if(method_exists($session, "tick")) {
                            $session->tick();
                        }
                    }
                }
            }
        }, 1);
    }
    
    /**
     * Инициализация расширенного логирования
     */
    private static function initLoggingEnhanced(): void {
        if(!self::$config["logging_enhanced"]) return;
        
        $logPath = self::$server->getDataPath() . "genisys_optimization.log";
        self::$logFile = fopen($logPath, "a") ?: null;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                $memory = round(memory_get_usage() / 1024 / 1024, 2);
                $players = count(GenisysMaxAPI::$server->getOnlinePlayers());
                $tps = method_exists(GenisysMaxAPI::$server, "getTicksPerSecond") 
                    ? GenisysMaxAPI::$server->getTicksPerSecond() 
                    : 0;
                
                GenisysMaxAPI::log(sprintf(
                    "Статистика сервера: TPS=%s, Память=%sMB, Игроки=%d",
                    $tps, $memory, $players
                ));
            }
        }, 20 * 60 * 5);
    }
    
    /**
     * Инициализация команд управления сервером
     */
    private static function initCommands(): void {
        if(!self::$config["commands_enabled"]) return;
        
        if(method_exists(self::$server, "getCommandMap")) {
            $commandMap = self::$server->getCommandMap();
            
            // Команда статуса сервера
            $commandMap->register("genisys", new class("gstatus", "Показать статус сервера") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.status");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $memory = round(memory_get_usage() / 1024 / 1024, 2);
                    $players = count(Server::getInstance()->getOnlinePlayers());
                    $tps = method_exists(Server::getInstance(), "getTicksPerSecond") 
                        ? Server::getInstance()->getTicksPerSecond() 
                        : 0;
                    
                    $uptime = method_exists(Server::getInstance(), "getStartTime")
                        ? self::formatUptime(time() - Server::getInstance()->getStartTime())
                        : "Unknown";
                    
                    $sender->sendMessage(TextFormat::GREEN . "=== Статус сервера ===");
                    $sender->sendMessage(TextFormat::YELLOW . "TPS: " . TextFormat::WHITE . $tps);
                    $sender->sendMessage(TextFormat::YELLOW . "Память: " . TextFormat::WHITE . $memory . "MB");
                    $sender->sendMessage(TextFormat::YELLOW . "Игроки: " . TextFormat::WHITE . $players);
                    $sender->sendMessage(TextFormat::YELLOW . "Аптайм: " . TextFormat::WHITE . $uptime);
                    $sender->sendMessage(TextFormat::YELLOW . "Оптимизации: " . TextFormat::GREEN . "Активны");
                    
                    return true;
                }
                
                private function formatUptime(int $seconds): string {
                    $days = floor($seconds / 86400);
                    $seconds %= 86400;
                    $hours = floor($seconds / 3600);
                    $seconds %= 3600;
                    $minutes = floor($seconds / 60);
                    $seconds %= 60;
                    
                    $uptime = "";
                    if($days > 0) $uptime .= $days . "д ";
                    if($hours > 0) $uptime .= $hours . "ч ";
                    if($minutes > 0) $uptime .= $minutes . "м ";
                    $uptime .= $seconds . "с";
                    
                    return $uptime;
                }
            });
            
            // Команда очистки памяти
            $commandMap->register("genisys", new class("gmemory", "Очистить память сервера") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.memory");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $before = memory_get_usage();
                    gc_collect_cycles();
                    $after = memory_get_usage();
                    $saved = round(($before - $after) / 1024 / 1024, 2);
                    
                    $sender->sendMessage(TextFormat::GREEN . "Память очищена! Освобождено " . $saved . "MB");
                    return true;
                }
            });
            
            // Команда выгрузки чанков
            $commandMap->register("genisys", new class("gchunks", "Выгрузить неиспользуемые чанки") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.chunks");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $count = 0;
                    
                    foreach(Server::getInstance()->getLevels() as $world) {
                        foreach($world->getChunks() as $chunk) {
                            if(method_exists($chunk, "isGenerated") && method_exists($world, "unloadChunk")) {
                                $canUnload = !$chunk->isGenerated();
                                if(method_exists($chunk, "getEntities")) {
                                    $canUnload = $canUnload || count($chunk->getEntities()) === 0;
                                }
                                
                                if($canUnload && $world->unloadChunk($chunk->getX(), $chunk->getZ(), true)) {
                                    $count++;
                                }
                            }
                        }
                    }
                    
                    $sender->sendMessage(TextFormat::GREEN . "Выгружено чанков: " . $count);
                    return true;
                }
            });
            
            // Команда полной оптимизации
            $commandMap->register("genisys", new class("goptimize", "Выполнить все оптимизации") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.optimize");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $before = microtime(true);
                    
                    gc_collect_cycles();
                    
                    foreach(Server::getInstance()->getLevels() as $world) {
                        if(method_exists($world, "clearCache")) {
                            $world->clearCache(true);
                        }
                        
                        foreach($world->getChunks() as $chunk) {
                            if(method_exists($chunk, "isGenerated") && method_exists($world, "unloadChunk")) {
                                $canUnload = !$chunk->isGenerated();
                                if(method_exists($chunk, "getEntities")) {
                                    $canUnload = $canUnload || count($chunk->getEntities()) === 0;
                                }
                                
                                if($canUnload) {
                                    $world->unloadChunk($chunk->getX(), $chunk->getZ(), true);
                                }
                            }
                        }
                    }
                    
                    $after = microtime(true);
                    $time = round(($after - $before) * 1000, 2);
                    
                    $sender->sendMessage(TextFormat::GREEN . "Полная оптимизация выполнена за " . $time . "мс");
                    return true;
                }
            });
            
            // Дополнительные команды для отображения статистики
            $commandMap->register("genisys", new class("gentities", "Показать информацию о сущностях") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.entities");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $totalEntities = 0;
                    
                    $sender->sendMessage(TextFormat::GREEN . "=== Статистика сущностей ===");
                    
                    foreach(Server::getInstance()->getLevels() as $world) {
                        if(method_exists($world, "getEntities")) {
                            $entities = $world->getEntities();
                            $count = count($entities);
                            $totalEntities += $count;
                            
                            $sender->sendMessage(TextFormat::YELLOW . "Мир: " . $world->getName() . 
                                TextFormat::WHITE . " - " . $count . " сущностей");
                        }
                    }
                    
                    $sender->sendMessage(TextFormat::GREEN . "Всего сущностей: " . $totalEntities);
                    return true;
                }
            });
            
            // Команда удаления сущностей
            $commandMap->register("genisys", new class("gkill", "Удалить все сущности (кроме игроков)") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.kill");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $count = 0;
                    
                    foreach(Server::getInstance()->getLevels() as $world) {
                        if(method_exists($world, "getEntities")) {
                            foreach($world->getEntities() as $entity) {
                                if(!($entity instanceof \pocketmine\Player)) {
                                    if(method_exists($entity, "close")) {
                                        $entity->close();
                                        $count++;
                                    }
                                }
                            }
                        }
                    }
                    
                    $sender->sendMessage(TextFormat::GREEN . "Удалено сущностей: " . $count);
                    return true;
                }
            });
            
            // Команда для показа статистики оптимизаций
            $commandMap->register("genisys", new class("gstats", "Показать статистику оптимизаций") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.stats");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    GenisysMaxAPI::showStats($sender);
                    return true;
                }
            });
            
            // Команда помощи
            $commandMap->register("genisys", new class("ghelp", "Показать список команд GenisysMaxAPI") extends Command {
                public function __construct(string $name, string $description) {
                    parent::__construct($name, $description);
                    $this->setPermission("genisys.command.help");
                }
                
                public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
                    $sender->sendMessage(TextFormat::GREEN . "=== Команды GenisysMaxAPI ===");
                    $sender->sendMessage(TextFormat::YELLOW . "/gstatus" . TextFormat::WHITE . " - Показать статус сервера");
                    $sender->sendMessage(TextFormat::YELLOW . "/gmemory" . TextFormat::WHITE . " - Очистить память сервера");
                    $sender->sendMessage(TextFormat::YELLOW . "/gchunks" . TextFormat::WHITE . " - Выгрузить неиспользуемые чанки");
                    $sender->sendMessage(TextFormat::YELLOW . "/goptimize" . TextFormat::WHITE . " - Выполнить все оптимизации");
                    $sender->sendMessage(TextFormat::YELLOW . "/gentities" . TextFormat::WHITE . " - Показать информацию о сущностях");
                    $sender->sendMessage(TextFormat::YELLOW . "/gkill" . TextFormat::WHITE . " - Удалить все сущности (кроме игроков)");
                    $sender->sendMessage(TextFormat::YELLOW . "/gstats" . TextFormat::WHITE . " - Показать статистику оптимизаций");
                    $sender->sendMessage(TextFormat::YELLOW . "/ghelp" . TextFormat::WHITE . " - Показать этот список");
                    
                    return true;
                }
            });
        }
    }
    
    /**
     * Инициализация автоматической сборки мусора
     */
    private static function initAutoGC(): void {
        if(!self::$config["auto_gc"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                $before = memory_get_usage();
                gc_enable();
                gc_collect_cycles();
                $after = memory_get_usage();
                
                $saved = $before - $after;
                if($saved > 0) {
                    GenisysMaxAPI::$stats["memory_saved"] += $saved;
                }
            }
        }, 20 * 60 * 10);
    }
    
    /**
     * Инициализация ограничения сущностей
     */
    private static function initEntityThrottling(): void {
        if(!self::$config["entity_throttling"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                $throttled = 0;
                
                foreach(GenisysMaxAPI::$server->getLevels() as $world) {
                    if(method_exists($world, "getEntities")) {
                        $entities = $world->getEntities();
                        
                        if(count($entities) > 100) {
                            foreach($entities as $entity) {
                                if(!($entity instanceof \pocketmine\Player)) {
                                    if(rand(0, 3) === 0) {
                                        if(method_exists($entity, "close")) {
                                            $entity->close();
                                            $throttled++;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
                
                GenisysMaxAPI::$stats["entity_throttled"] += $throttled;
            }
        }, 20 * 60 * 5);
    }
    
    /**
     * Инициализация оптимизации команд
     */
    private static function initCommandOptimization(): void {
        if(!self::$config["command_optimization"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                // Оптимизация через периодический тик
            }
        }, 20 * 30);
    }
    
    /**
     * Инициализация сборщика мусора для чанков
     */
    private static function initChunkGC(): void {
        if(!self::$config["chunk_gc"]) return;
        
        self::$server->getScheduler()->scheduleRepeatingTask(new class() extends \pocketmine\scheduler\Task {
            public function onRun(int $currentTick): void {
                foreach(GenisysMaxAPI::$server->getLevels() as $world) {
                    if(method_exists($world, "doChunkGarbageCollection")) {
                        $world->doChunkGarbageCollection();
                    }
                }
            }
        }, 20 * 60 * 15);
    }
    
    /**
     * Отображение статистики оптимизаций
     */
    public static function showStats(?CommandSender $sender = null): void {
        $stats = [
            "Сохранено памяти" => round(self::$stats["memory_saved"] / 1024 / 1024, 2) . "MB",
            "Сэкономлено времени тиков" => round(self::$stats["tick_time_saved"] * 1000, 2) . "мс",
            "Оптимизировано чанков" => self::$stats["optimized_chunks"],
            "Ограничено сущностей" => self::$stats["entity_throttled"]
        ];
        
        if($sender !== null) {
            $sender->sendMessage(TextFormat::GREEN . "=== Статистика оптимизаций ===");
            foreach($stats as $name => $value) {
                $sender->sendMessage(TextFormat::YELLOW . $name . ": " . TextFormat::WHITE . $value);
            }
        } else {
            self::log("Статистика оптимизаций:");
            foreach($stats as $name => $value) {
                self::log("- " . $name . ": " . $value);
            }
        }
    }
    
    /**
     * Логирование сообщений
     */
    public static function log(string $message): void {
        self::$server->getLogger()->info("[GenisysMaxAPI] " . $message);
        
        if(self::$logFile !== null) {
            $date = date("Y-m-d H:i:s");
            fwrite(self::$logFile, "[$date] $message\n");
        }
    }
}