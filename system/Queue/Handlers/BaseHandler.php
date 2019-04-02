<?php namespace CodeIgniter\Queue\Handlers;

/**
 * Base Queue handler.
 */

abstract class BaseHandler
{
	/**
	 * @var string
	 */
	protected $defaultExchange;

	/**
	 * @var string
	 */
	protected $defaultQueue;

	/**
	 * @var array
	 */
	protected $exchangeMap;

	/**
	 * constructor.
	 *
	 * @param array         $groupConfig
	 * @param \Config\Queue $config
	 */
	public function __construct($groupConfig, $config)
	{
		$this->defaultExchange = $config->defaultExchange;
		$this->defaultQueue    = $config->defaultQueue;
		$this->exchangeMap     = $config->exchangeMap;
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $routingKey
	 * @param string $exchangeName
	 */
	abstract public function send($data, string $routingKey = '', string $exchangeName = '');

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queueName
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function fetch(callable $callback, string $queueName = '') : bool;

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queueName
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function receive(callable $callback, string $queueName = '') : bool;
}