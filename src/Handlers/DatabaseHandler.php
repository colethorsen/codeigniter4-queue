<?php namespace CodeIgniter\Queue\Handlers;

use CodeIgniter\Queue\Exceptions\QueueException;

/**
 * Queue handler for database.
 */
class DatabaseHandler extends BaseHandler
{
	protected const STATUS_WAITING   = 10;
	protected const STATUS_EXECUTING = 20;
	protected const STATUS_DONE      = 30;
	protected const STATUS_FAILED    = 40;

	/**
	 * @var string
	 */
	protected $table;

	/**
	 * @var integer
	 */
	protected $timeout;

	/**
	 * @var integer
	 */
	protected $maxRetries;

	/**
	 * @var integer
	 */
	protected $deleteDoneMessagesAfter;

	/**
	 * @var \CodeIgniter\Database\BaseConnection
	 */
	protected $db;

	/**
	 * constructor.
	 *
	 * @param array         $connectionConfig
	 * @param \Config\Queue $config
	 */
	public function __construct($connectionConfig, $config)
	{
		parent::__construct($connectionConfig, $config);

		$this->table = $connectionConfig['table'];

		$this->timeout                 = $config->timeout;
		$this->maxRetries              = $config->maxRetries;
		$this->deleteDoneMessagesAfter = $config->deleteDoneMessagesAfter;

		$this->db = \Config\Database::connect($connectionConfig['dbGroup'] ?? config('Database')->defaultGroup, $connectionConfig['sharedConnection'] ?? true);
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 */
	public function send($data, string $queue = '')
	{
		if ($queue === '')
		{
			$queue = $this->defaultQueue;
		}

		$this->db->transStart();

		$datetime = date('Y-m-d H:i:s');

		$this->db->table($this->table)->insert([
			'queue'        => $queue,
			'status'       => self::STATUS_WAITING,
			'weight'       => 100,
			'attempts'     => 0,
			'available_at' => $this->available_at->format('Y-m-d H:i:s'),
			'data'         => serialize($data),
			'created_at'   => $datetime,
			'updated_at'   => $datetime,
		]);

		$this->db->transComplete();
	}

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	public function fetch(callable $callback, string $queue = ''): bool
	{
		$query = $this->db->table($this->table)
			->where('queue', $queue !== '' ? $queue : $this->defaultQueue)
			->where('status', self::STATUS_WAITING)
			->where('available_at <', date('Y-m-d H:i:s'))
			->orderBy('weight')
			->orderBy('id')
			->limit(1)
			->get();

		if ( ! $query)
		{
			throw QueueException::forFailGetQueueDatabase($this->table);
		}

		$row = $query->getRow();

		//if there is nothing else to run at the moment return false.
		if ( ! $row)
		{
			$this->housekeeping();

			return false;
		}

		//set the status to executing if it hasn't already been taken.
		$this->db->table($this->table)
			->where('id', (int) $row->id)
			->where('status', (int) self::STATUS_WAITING)
			->update([
				'status'     => self::STATUS_EXECUTING,
				'updated_at' => date('Y-m-d H:i:s'),
			]);

		//don't run again if its already been taken.
		if ($this->db->affectedRows() === 0)
		{
			return true;
		}

		$data = unserialize($row->data);

		//if the callback doesn't throw an exception mark it as done.
		try
		{
			$callback($data);

			$this->db->table($this->table)
				->where('id', $row->id)
				->update([
					'status'     => self::STATUS_DONE,
					'updated_at' => date('Y-m-d H:i:s'),
				]);

			$this->fireOnSuccess($data);
		}
		catch (\Throwable $e)
		{
			//track any exceptions into the database for easier troubleshooting.
			$error = (new \DateTime)->format('Y-m-d H:i:s') . "\n" .
					"{$e->getCode()} - {$e->getMessage()}\n\n" .
					"file: {$e->getFile()}:{$e->getLine()}\n" .
					"------------------------------------------------------\n\n";

			$this->db->table($this->table)
				->where('id', $row->id)
				->set('error', 'CONCAT(error, "' . $this->db->escapeString($error) . '")', false)
				->update();

			$this->fireOnFailure($e, $data);

			throw $e;
		}

		//there could be more to run so return true.
		return true;
	}

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	public function receive(callable $callback, string $queue = ''): bool
	{
		while ( ! $this->fetch($callback, $queue))
		{
			usleep(1000000);
		}

		return true;
	}

	/**
	 * housekeeping.
	 *
	 * clean up the database at the end of each run.
	 */
	public function housekeeping()
	{
		//update executing statuses to waiting on timeout before max retry.
		$this->db->table($this->table)
			->set('attempts', 'attempts + 1', false)
			->set('status', self::STATUS_WAITING)
			->set('updated_at', date('Y-m-d H:i:s'))
			->where('status', self::STATUS_EXECUTING)
			->where('updated_at <', date('Y-m-d H:i:s', time() - $this->timeout))
			->where('attempts <', $this->maxRetries)
			->update();

		//update executing statuses to failed on timeout at max retry.
		$this->db->table($this->table)
			->set('attempts', 'attempts + 1', false)
			->set('status', self::STATUS_FAILED)
			->set('updated_at', date('Y-m-d H:i:s'))
			->where('status', self::STATUS_EXECUTING)
			->where('updated_at <', date('Y-m-d H:i:s', time() - $this->timeout))
			->where('attempts >=', $this->maxRetries)
			->update();

		//Delete messages after the configured period.
		if ($this->deleteDoneMessagesAfter !== false)
		{
			$this->db->table($this->table)
				->where('status', self::STATUS_DONE)
				->where('updated_at <', date('Y-m-d H:i:s', time() - $this->deleteDoneMessagesAfter))
				->delete();
		}
	}
}
