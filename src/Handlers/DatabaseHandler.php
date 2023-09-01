<?php namespace CodeIgniter\Queue\Handlers;

use CodeIgniter\Queue\Exceptions\QueueException;

use CodeIgniter\Queue\Status;
use CodeIgniter\Queue\Message;

use CodeIgniter\Queue\Handlers\DatabaseHandler\Model;

use CodeIgniter\I18n\Time;

/**
 * Queue handler for database.
 */
class DatabaseHandler extends BaseHandler
{
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
	 * @var DatabaseHandler\Model;
	 */
	protected $model;

	/**
	 * @var row ID currently being worked on
	 */
	protected $messageID;

	/**
	 * constructor.
	 *
	 * @param array         $connectionConfig
	 * @param \Config\Queue $config
	 */
	public function __construct(array $connectionConfig, \Config\Queue $config)
	{
		parent::__construct($connectionConfig, $config);

		$dbConnection = \Config\Database::connect($connectionConfig['dbGroup'] ?? config('Database')->defaultGroup, $connectionConfig['sharedConnection'] ?? true);

		$this->model = new Model($dbConnection);

		$this->model->setTable($connectionConfig['table']);

		$this->timeout                 = $config->timeout;
		$this->maxRetries              = $config->maxRetries;
		$this->deleteDoneMessagesAfter = $config->deleteDoneMessagesAfter;
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 *
	 * @return Message the message that was just created.
	 */
	public function send($data, string $queue = ''): Message
	{
		if ($queue === '')
		{
			$queue = $this->defaultQueue;
		}

		$message = (new Message)->fill([
			'data'         => $data,
			'queue'        => $queue,
			'available_at' => $this->available_at,
			'weight'       => $this->weight,
		]);

//		$this->db->transStart();

		//check for duplicates.
		//There's no reason to create 2 jobs that run the same thing at the
		//exact same time on the same queue.
		$existing = $this->model->where([
				'queue'        => $message->queue,
				'status'       => $message->status,
				'data'         => serialize($message->data),
				'available_at' => $message->available_at,
			])
			->first();

		if ($existing)
		{
			return $existing;
		}

		$this->model->insert($message);

		$message->id         = $this->model->getInsertID();
		$message->created_at = new Time;
		$message->updated_at = new Time;

//		$this->db->transComplete();

		return $message;
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
		$message = $this->model
			->where('queue', $queue !== '' ? $queue : $this->defaultQueue)
			->where('status', Status::WAITING)
			->where('available_at <', date('Y-m-d H:i:s'))
			->orderBy('weight')
			->orderBy('id')
			->first();
		/*
				if (! $query)
				{
					throw QueueException::forFailGetQueueDatabase($this->table);
				}
		 */

		//if there is nothing else to run at the moment return false.
		if ( ! $message)
		{
			$this->housekeeping();

			return false;
		}

		$message->status = Status::EXECUTING;

		//set the status to executing if it hasn't already been taken.
		$this->model
			->where('status', Status::WAITING)
			->save($message);

		//don't run again if its already been taken.
		if ($this->model->db->affectedRows() === 0)
		{
			return true;
		}

		//if the callback doesn't throw an exception mark it as done.
		try
		{
			$this->messageID = $message->id;

			$callback($message->data);

			$message->status     = Status::DONE;
			$message->updated_at = new Time;

			$this->model->save($message);

			$this->fireOnSuccess($message);
		}
		catch (\Throwable $e)
		{
			//track any exceptions into the database for easier troubleshooting.
			$error = "{$e->getCode()} - {$e->getMessage()}\n\n" .
					"file: {$e->getFile()}:{$e->getLine()}\n" .
					"------------------------------------------------------\n\n";

			$message->error      = $message->error . $error;
			$message->updated_at = new Time;

			$this->model->save($message);

			$this->fireOnFailure($e, $message);

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
	 * Track progress of a message in the queuing system.
	 *
	 * @param int $currentStep the current step number
	 * @param int $totalSteps  the total number of steps
	 */
	public function progress(int $currentStep, int $totalSteps)
	{
		$this->model->update($this->messageID, [
				'progress_current' => $currentStep,
				'progress_total'   => $totalSteps,
				'updated_at'       => date('Y-m-d H:i:s'),
			]);
	}

	/**
	 * Get info on a message in the queuing system.
	 *
	 * @param $id identifier in the queue.
	 */
	public function getMessage(string $id)
	{
		return $this->model->find($id);
	}

	/**
	 * housekeeping.
	 *
	 * clean up the database at the end of each run.
	 */
	public function housekeeping()
	{
		//update executing statuses to waiting on timeout before max retry.
		$this->model
			->set('attempts', 'attempts + 1', false)
			->set('status', Status::WAITING)
			->set('updated_at', date('Y-m-d H:i:s'))
			->where('status', Status::EXECUTING)
			->where('updated_at <', date('Y-m-d H:i:s', time() - $this->timeout))
			->where('attempts <', $this->maxRetries)
			->update();

		//update executing statuses to failed on timeout at max retry.
		$this->model
			->set('attempts', 'attempts + 1', false)
			->set('status', Status::FAILED)
			->set('updated_at', date('Y-m-d H:i:s'))
			->where('status', Status::EXECUTING)
			->where('updated_at <', date('Y-m-d H:i:s', time() - $this->timeout))
			->where('attempts >=', $this->maxRetries)
			->update();

		//Delete messages after the configured period.
		if ($this->deleteDoneMessagesAfter !== false)
		{
			$this->model
				->where('status', Status::DONE)
				->where('updated_at <', date('Y-m-d H:i:s', time() - $this->deleteDoneMessagesAfter))
				->delete();
		}
	}
}
