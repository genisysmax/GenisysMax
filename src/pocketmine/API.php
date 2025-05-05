<?php

use pocketmine\block\Block;
use pocketmine\entity\Entity;
use pocketmine\event\Event;
use pocketmine\inventory\Inventory;
use pocketmine\inventory\PlayerInventory;
use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\scheduler\AsyncClosureTask;
use pocketmine\scheduler\AsyncTask;
use pocketmine\scheduler\CancellableClosureTask;
use pocketmine\scheduler\ClosureTask;
use pocketmine\scheduler\ServerScheduler;
use pocketmine\scheduler\Task;
use pocketmine\Server;
use pocketmine\utils\Color;
use pocketmine\utils\MainLogger;
use pocketmine\utils\Utils;

define('WORKER_PLAYER_DATA', 0); //Воркер для операций, связанных с игроками
define('WORKER_SERVER_DATA', 1); //Воркер для операций, связанных с сервером
define('WORKER_ARENA_DATA', 2); //Воркер для операций, связанных с островами
define('WORKER_DOP_DATA', 3); //Воркер для всякой доп херни

/*
 * info(msg)
 * error(msg)
 * alert(msg)
 * critical(msg)
 * level(name)
 * loadLevel(name)
 * unloadLevel(name)
 * server()
 * logger()
 * delay(func, time, args) Аналог scheduleDelayedTask. Возвращает ID таска
 * repeat(func, time, args) Аналог scheduleRepeatingTask. Возвращает ID таска
 * cancel(e) e - Эвент, либо ID таска
 * player(e) e - Эвент либо ник игрока (и возвращает игрока по нику, если ник указан)
 * item(e,a=0,b=1,c=null) e - Эвент, либо инвентарь и возвращает itemInHand, либо игрок, и возвращает itemInHand, либо возвращает
 * предмет с ID = e, Meta = a, Count = b и если указан c, то ставит предмету такое имя
 * block(a=0,b=0,c=0,d=null) a - Эвент, либо a - x, b - y, c - z координаты блока, который нужно получить. Если d = null, то блок ищет
 * в default мире, если он не null, ищет в указанном мире
 * entity(e) e - Эвент, либо ID энтити и возвращает энтити по его ID.
 * inv(e) e - Эвент/что угодно, что содержит инвентарь
 * async(task, worker) - Пихает асинк таск в указанный воркер
 * regEvents(plugin, listener = null) Если Listener = null, то за него берется Plugin.
 * randChances(chances) - Выдаёт случайный ключ из массива chances, который содержит шансы выпадения в числах с плавающей точкой
 */

function info($message){
	API::$logger->info($message);
}

function error($message){
	API::$logger->error($message);
}

function alert($message){
	API::$logger->alert($message);
}

function critical($message){
	API::$logger->critical($message);
}

function levelLoaded($name){
	return API::$server->isLevelLoaded($name);
}

function levelGenerated(string $name, string $path = "") : bool{
	return API::$server->isLevelGenerated($name, $path);
}

function level($name = null){
    if($name === null)
        return API::$server->getDefaultLevel();
	return API::$server->getLevelByName($name);
}

function loadLevel($name, string $path = "", $generator = null){
	$server = &API::$server;
	if(!$server->isLevelGenerated($name, $path)) {
		$server->generateLevel($name, $path, null, $generator);
	}
	if(!$server->isLevelLoaded($name)){
		$server->loadLevel($name, $path);
	}
	return $server->getLevelByName($name);
}

function unloadLevel($level){
	if(!($level instanceof Level)) {
		$level = API::$server->getLevelByName($level);
	}
	API::$server->unloadLevel($level);
}

function server(){
	return API::$server;
}

function logger(){
	return API::$logger;
}

function delay(callable $func, int $time, array $args = []) : int{
	if($func instanceof \Closure and count($args) === 0 and Utils::checkCallableSignature(function(int $currentTick) : void{}, $func)){
		$task = new ClosureTask($func);
	}else{
		$task = new Clbck($func, $args);
	}
	return API::$scheduler->scheduleDelayedTask($task, $time)->getTaskId();
}

function repeat(callable $func, int $time, array $args = []) : int{
	if($func instanceof \Closure and count($args) === 0){
		if(Utils::checkCallableSignature(function(int $currentTick) : bool{ return false; }, $func)){
			$task = new CancellableClosureTask($func);
		}elseif(Utils::checkCallableSignature(function(int $currentTick) : void{}, $func)){
			$task = new ClosureTask($func);
		}else{
			$task = new Clbck($func, $args);
		}
	}else{
		$task = new Clbck($func, $args);
	}
	return API::$scheduler->scheduleRepeatingTask($task, $time)->getTaskId();
}

function cancel($e){
	if($e instanceof Event){
		$e->setCancelled();
	}else{
		API::$scheduler->cancelTask($e);
	}
}

/**
 * @param $e
 * @return Player
 */
function player($e) {
	if($e instanceof Event) {
		return $e->getPlayer();
	}else {
		return API::$server->getPlayer($e);
	}
}

/**
 * @param $id
 * @param int $meta
 * @param int $count
 * @param null $name
 * @return Item
 */
function item($id, $meta = 0, $count = 1, $name = null){
	if($id instanceof Event){
		return $id->getItem();
	}elseif($id instanceof PlayerInventory){
		return $id->getItemInHand();
	}elseif($id instanceof Player){
		return $id->getInventory()->getItemInHand();
	}else{
		$i = Item::get($id, $meta, $count);
		if($name != null)
			$i->setCustomName("§r".$name);
		return $i;
	}
}

/**
 * @param int $a
 * @param int $b
 * @param int $c
 * @param null $d
 * @return Block
 */
function block($a = 0, $b = 0, $c=0, $d=null){
	if($a instanceof Event){
		return $a->getBlock();
	}else{
		if($d == null)
			$d = API::$server->getDefaultLevel();
		return $d->getBlock(new Vector3($a,$b,$c));
	}
}

/**
 * @param $e
 * @return null|Entity
 */
function entity($e){
	if($e instanceof Event){
		return $e->getEntity();
	}else{
		return API::$server->findEntity($e);
	}
}

/**
 * @param $e
 * @return Inventory
 */
function inv($e){
	return $e->getInventory();
}

/**
 * @deprecated
 */
class Clbck extends Task{

	/** @var callable */
	protected $callable;

	/** @var array */
	protected $args;

	/**
	 * @param callable $callable
	 * @param array	$args
	 */
	public function __construct($callable, array $args = []){
		$this->callable = $callable;
		$this->args = $args;
	}

	public function onRun(int $currentTicks){
		$c = $this->callable;
		$args = $this->args;
		$c(...$args);
	}

}

function regEvents($plugin, $listener = null){
	if($listener === null){
		$listener = $plugin;
	}
	server()->getPluginManager()->registerEvents($listener, $plugin);
}

function async(AsyncTask|\Closure $execute, int $worker = WORKER_DOP_DATA, array $parameters = []) : void{
	if ($execute instanceof \Closure) {
		API::$scheduler->scheduleAsyncTaskToWorker(new AsyncClosureTask($execute, $parameters), $worker);
	} else {
		API::$scheduler->scheduleAsyncTaskToWorker($execute, $worker);
	}
}

function path(){
	return API::$path;
}

function color($r, $g, $b){
    return new Color($r, $g, $b);
}

class API{
	/** @var Server */
	public static $server;

	/** @var MainLogger */
	public static $logger;

	/** @var ServerScheduler */
	public static $scheduler;

	/** @var string */
	public static $path;

	public static function init(){
		self::$server = Server::getInstance();
		self::$logger = self::$server->getLogger();
		self::$scheduler = self::$server->getScheduler();
		self::$path = self::$server->getDataPath();
	}



}
