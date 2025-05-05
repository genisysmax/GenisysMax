<?php

/*
 *
 *    _____            _               __  __            
 *   / ____|          (_)             |  \/  |           
 *  | |  __  ___ _ __  _ ___ _   _ ___| \  / | __ ___  __
 *  | | |_ |/ _ \ '_ \| / __| | | / __| |\/| |/ _` \ \/ /
 *  | |__| |  __/ | | | \__ \ |_| \__ \ |  | | (_| |>  < 
 *   \_____|\___|_| |_|_|___/\__, |___/_|  |_|\__,_/_/\_\
 *                            __/ |                      
 *                           |___/                       
 *
 * This program is licensed under the GPLv3 license.
 * You are free to modify and redistribute it under the same license.
 *
 * @author LINUXOV
 * @link vk.com/linuxof
 *
*/



declare(strict_types=1);

namespace pocketmine\scheduler;

use pmmp\thread\Runnable;
use pmmp\thread\ThreadSafeArray;
use pocketmine\Server;
use pocketmine\thread\NonThreadSafeValue;

/**
 * Class used to run async tasks in other threads.
 *
 * An AsyncTask does not have its own thread. It is queued into an AsyncPool and executed if there is an async worker
 * with no AsyncTask running. Therefore, an AsyncTask SHOULD NOT execute for more than a few seconds. For tasks that
 * run for a long time or infinitely, start another {@link \pocketmine\Thread} instead.
 *
 * WARNING: Do not call PocketMine-MP API methods, or save objects (and arrays containing objects) from/on other Threads!!
 */
abstract class AsyncTask extends Runnable{

	private static ?\ArrayObject $threadLocalStorage = null;

	/** @var AsyncWorker $worker */
	public $worker = null;
	public $workerId = 0;

	/** @var ThreadSafeArray */
	public ThreadSafeArray $progressUpdates;

	private NonThreadSafeValue|string|int|bool|null|float $result = null;
	private $cancelRun = false;
	private $isGarbage = false;
	/** @var int|null */
	private $taskId = null;

	private $crashed = false;

	/**
	 * Constructs a new instance of AsyncTask. Subclasses don't need to call this constructor unless an argument is to be passed. ONLY construct this class from the main thread.
	 * <br>
	 * If an argument is passed into this constructor, it will be stored in a thread-local storage (in ServerScheduler), which MUST be retrieved through {@link #fetchLocal} when {@link #onCompletion} is called.
	 * Otherwise, a NOTICE level message will be raised and the reference will be removed after onCompletion exits.
	 * <br>
	 * If null or no argument is passed, do <em>not</em> call {@link #fetchLocal}, or an exception will be thrown.
	 * <br>
	 * WARNING: Use this method carefully. It might take a long time before an AsyncTask is completed. PocketMine will keep a strong reference to objects passed in this method.
	 * This may result in a light memory leak. Usually this does not cause memory failure, but be aware that the object may be no longer usable when the AsyncTask completes.
	 * (E.g. a {@link \pocketmine\Level} object is no longer usable because it is unloaded while the AsyncTask is executing, or even a plugin might be unloaded)
	 * Since PocketMine keeps a strong reference, the objects are still valid, but the implementation is responsible for checking whether these objects are still usable.
	 *
	 * @param mixed $complexData the data to store, pass null to store nothing. Scalar types can be safely stored in class properties directly instead of using this thread-local storage.
	 */
	public function __construct($complexData = null){
		if($complexData === null){
			return;
		}

		Server::getInstance()->getScheduler()->storeLocalComplex($this, $complexData);
		$this->progressUpdates = new ThreadSafeArray;
	}

	public function run() : void{
		$this->result = null;

		if($this->cancelRun !== true){
			try{
				$this->onRun();
			}catch(\Throwable $e){
				$this->crashed = true;
				
				\GlobalLogger::get()->logException($e);
			}
		}

		$this->setGarbage();
	}

	/**
	 * Saves mixed data in thread-local storage. Data stored using this storage is **only accessible from the thread it
	 * was stored on**. Data stored using this method will **not** be serialized.
	 * This can be used to store references to variables which you need later on on the same thread, but not others.
	 *
	 * For example, plugin references could be stored in the constructor of the async task (which is called on the main
	 * thread) using this, and then fetched in onCompletion() (which is also called on the main thread), without them
	 * becoming serialized.
	 *
	 * Scalar types can be stored directly in class properties instead of using this storage.
	 *
	 * Objects stored in this storage can be retrieved using fetchLocal() on the same thread that this method was called
	 * from.
	 *
	 * @param mixed  $complexData the data to store
	 */
	protected function storeLocal(string $key, $complexData) : void{
		if(self::$threadLocalStorage === null){
			/*
			 * It's necessary to use an object (not array) here because pthreads is stupid. Non-default array statics
			 * will be inherited when task classes are copied to the worker thread, which would cause unwanted
			 * inheritance of primitive thread-locals, which we really don't want for various reasons.
			 * It won't try to inherit objects though, so this is the easiest solution.
			 */
			self::$threadLocalStorage = new \ArrayObject();
		}
		self::$threadLocalStorage[spl_object_id($this)][$key] = $complexData;
	}

	public function isCrashed() : bool{
		return $this->crashed;
	}

	/**
	 * @return mixed
	 */
	public function getResult(){
		if($this->result instanceof NonThreadSafeValue){
			return $this->result->deserialize();
		}
		return $this->result;
	}

	public function cancelRun(){
		$this->cancelRun = true;
	}

	public function hasCancelledRun() : bool{
		return $this->cancelRun === true;
	}

	/**
	 * @return bool
	 */
	public function hasResult() : bool{
		return $this->getResult() !== null;
	}

	/**
	 * @param mixed $result
	 * @param bool  $serialize
	 */
	public function setResult($result, bool $serialize = true){
		$this->result = is_scalar($result) || is_null($result) ? $result : new NonThreadSafeValue($result);
	}

	public function setTaskId(int $taskId){
		$this->taskId = $taskId;
	}

	/**
	 * @return int|null
	 */
	public function getTaskId(){
		return $this->taskId;
	}

	/**
	 * Gets something into the local thread store.
	 * You have to initialize this in some way from the task on run
	 *
	 * @param string $identifier
	 * @return mixed
	 */
	public function getFromThreadStore(string $identifier){
		global $store;
		return ($this->isGarbage() or !isset($store[$identifier])) ? null : $store[$identifier];
	}

	/**
	 * Saves something into the local thread store.
	 * This might get deleted at any moment.
	 *
	 * @param string $identifier
	 * @param mixed  $value
	 */
	public function saveToThreadStore(string $identifier, $value){
		global $store;
		if(!$this->isGarbage()){
			$store[$identifier] = $value;
		}
	}

	/**
	 * Actions to execute when run
	 *
	 * @return void
	 */
	abstract public function onRun();

	/**
	 * Actions to execute when completed (on main thread)
	 * Implement this if you want to handle the data in your AsyncTask after it has been processed
	 *
	 * @param Server $server
	 *
	 * @return void
	 */
	public function onCompletion(Server $server){

	}

	/**
	 * Call this method from {@link AsyncTask#onRun} (AsyncTask execution thread) to schedule a call to
	 * {@link AsyncTask#onProgressUpdate} from the main thread with the given progress parameter.
	 *
	 * @param mixed $progress A value that can be safely serialize()'ed.
	 */
	public function publishProgress($progress) {
		$this->progressUpdates[] = igbinary_serialize($progress) ?? throw new \InvalidArgumentException("Progress must be serializable");
	}

	/**
	 * @internal Only call from AsyncPool.php on the main thread
	 *
	 * @param Server $server
	 */
	public function checkProgressUpdates(Server $server){
		while($this->progressUpdates->count() !== 0){
			$progress = $this->progressUpdates->shift();
			$this->onProgressUpdate($server, igbinary_unserialize($progress));
		}
	}

	/**
	 * Called from the main thread after {@link AsyncTask#publishProgress} is called.
	 * All {@link AsyncTask#publishProgress} calls should result in {@link AsyncTask#onProgressUpdate} calls before
	 * {@link AsyncTask#onCompletion} is called.
	 *
	 * @param Server $server
	 * @param mixed  $progress The parameter passed to {@link AsyncTask#publishProgress}. It is serialize()'ed
	 *                         and then unserialize()'ed, as if it has been cloned.
	 */
	public function onProgressUpdate(Server $server, $progress){

	}

	/**
	 * Call this method from {@link AsyncTask#onCompletion} to fetch the data stored in the constructor, if any, and
	 * clears it from the storage.
	 *
	 * Do not call this method from {@link AsyncTask#onProgressUpdate}, because this method deletes the data and cannot
	 * be used in the next {@link AsyncTask#onProgressUpdate} call or from {@link AsyncTask#onCompletion}. Use
	 * {@link AsyncTask#peekLocal} instead.
	 *
	 * @param Server $server default null
	 *
	 * @return mixed
	 *
	 * @throws \RuntimeException if no data were stored by this AsyncTask instance.
	 */
	protected function fetchLocal(Server $server = null){
		if($server === null){
			$server = Server::getInstance();
			assert($server !== null, "Call this method only from the main thread!");
		}

		return $server->getScheduler()->fetchLocalComplex($this);
	}

	/**
	 * Call this method from {@link AsyncTask#onProgressUpdate} to fetch the data stored in the constructor.
	 *
	 * Use {@link AsyncTask#peekLocal} instead from {@link AsyncTask#onCompletion}, because this method does not delete
	 * the data, and not clearing the data will result in a warning for memory leak after {@link AsyncTask#onCompletion}
	 * finished executing.
	 *
	 * @param Server|null $server default null
	 *
	 * @return mixed
	 *
	 * @throws \RuntimeException if no data were stored by this AsyncTask instance
	 */
	protected function peekLocal(Server $server = null){
		if($server === null){
			$server = Server::getInstance();
			assert($server !== null, "Call this method only from the main thread!");
		}

		return $server->getScheduler()->peekLocalComplex($this);
	}

	public function cleanObject() : void{
		if (self::$threadLocalStorage !== null && isset(self::$threadLocalStorage[$this])) {
			unset(self::$threadLocalStorage[$this]);
		}
        $this->setGarbage();
	}

	public function isGarbage() : bool{
		return $this->isGarbage;
	}

	public function setGarbage(){
		$this->isGarbage = true;
	}
}

