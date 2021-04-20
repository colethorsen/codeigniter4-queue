<?php namespace CodeIgniter\Queue;

use CodeIgniter\Queue\Exceptions\QueueException;

/**
 * Queue class.
 */
class Queue implements QueueInterface
{
	/**
	 * Config object.
	 *
	 * @var \Config\Queue
	 */
	protected $config;

	/**
	 * Config of the connection connection to use
	 *
	 * @var array
	 */
	protected $connectionConfig;

	/**
	 * Constructor.
	 *
	 * @param \Config\Queue $config
	 * @param string|array  $connection The name of the connection to use,
	 *                              or an array of configuration settings.
	 */
	public function __construct($config, $connection = '')
	{
		if (is_array($connection))
		{
			$connectionConfig = $connection;
			$connection       = 'custom';
		}
		else
		{
			if ($connection === '')
			{
				$connection = ENVIRONMENT === 'testing' ? 'tests' : (string) $config->defaultConnection;
			}

			if (isset($config->$connection))
			{
				$connectionConfig = $config->$connection;
			}
			else
			{
				throw QueueException::forInvalidconnection($connection);
			}
		}

		$this->connectionConfig = $connectionConfig;
		$this->config           = $config;
	}

	/**
	 * connecting queueing system.
	 *
	 * @return CodeIgniter\Queue\Handlers\BaseHandler
	 */
	public function connect()
	{
		$handler = '\\CodeIgniter\\Queue\\Handlers\\' . $this->connectionConfig['handler'] . 'Handler';
		return new $handler($this->connectionConfig, $this->config);
	}
}
