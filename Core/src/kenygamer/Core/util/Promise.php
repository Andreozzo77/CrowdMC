<?php

declare(strict_types=1);

namespace kenygamer\Core\util;

use Closure;

/**
 * This library is a lightweight implementation of the guzzle/promise
 * project, in accordance with the the Promises/A+ JS standard for
 * interoperable promise chaining.
 *
 * It is used to keep call stack size constant.
 */
final class Promise{
	private const PENDING = "pending";
	private const REJECTED = "rejected";
	private const FULFILLED = "fulfilled";

	/** @var mixed */
	protected $value = null;
	/** @var string */
	protected $now = self::PENDING;
	/** @var Closure[] */
	protected $fulfilled = [];
	/** @var Closure[] */
	protected $rejected = [];
	
	public function __construct(){
	}

	/**
	 * @param Closure $callback
	 * @return Promise
	 */
	public function then(Closure $callback) : Promise{
		if($this->now === self::FULFILLED){
			$callback($this->value);
			return $this;
		}
		$this->fulfilled[] = $callback;
		return $this;
	}

	/**
	 * @param Closure $callback
	 * @return Promise
	 */
	public function catch(Closure $callback) : Promise{
		if($this->now === self::REJECTED){
			$callback($this->value);
			return $this;
		}
		$this->rejected[] = $callback;
		return $this;
	}
	
	/**
	 * Wrapper for self::then() and self::catch()
	 * {@see https://github.com/reactphp/promise}
	 *
	 * @param Closure $onRejection
	 * @param Closure $onCompletion
	 * @return Promise
	 */
	public function done(Closure $onRejection, Closure $onCompletion) : Promise{
		return $this->then($onCompletion)->catch($onRejection);
	}

	/**
	 * @param mixed $value
	 * @return Promise
	 */
	public function resolve($value) : Promise{
		$this->setNow(self::FULFILLED, $value);
		return $this;
	}
	
	/**
	 * @param mixed $reason
	 * @return Promise
	 */
	public function reject($reason) : Promise{
		$this->setNow(self::REJECTED, $reason);
		return $this;
	}

	/**
	 * @param string $now
	 * @param mixed $value
	 * @return Promise
	 */
	private function setNow(string $now, $value) : Promise{
		$this->now = $now;
		$this->value = $value;

		$callbacks = $this->now === self::FULFILLED ? $this->fulfilled : $this->rejected;
		foreach($callbacks as $closure){
			$closure($this->value);
		}
		$this->fulfilled = $this->rejected = [];
		return $this;
	}
	
}