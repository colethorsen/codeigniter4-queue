<?php namespace CodeIgniter\Queue\Handlers;

use CodeIgniter\I18n\Time;

use CodeIgniter\Queue\Message;

/**
 * Base Queue handler.
 */
abstract class BaseHandler
{
	/**
	 * @var string
	 */
	protected $defaultQueue;

	/**
	 * when the message will be available for
	 * execution
	 *
	 * @var Time
	 */
	protected $available_at;

	/**
	 * weight for the message
	 */
	protected $weight = 100;

	/**
	 * constructor.
	 *
	 * @param array         $groupConfig
	 * @param \Config\Queue $config
	 */
	public function __construct(array $groupConfig, \Config\Queue $config)
	{
		$this->defaultQueue = $config->defaultQueue;

		$this->available_at = new Time;
	}

	/**
	 * send message to queueing system.
	 *
	 * @param array  $data
	 * @param string $queue
	 *
	 * @return Message the message that was just created.
	 */
	abstract public function send($data, string $queue = ''): Message;

	/**
	 * Fetch message from queueing system.
	 * When there are no message, this method will return (won't wait).
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function fetch(callable $callback, string $queue = ''): bool;

	/**
	 * Receive message from queueing system.
	 * When there are no message, this method will wait.
	 *
	 * @param  callable $callback
	 * @param  string   $queue
	 * @return boolean  whether callback is done or not.
	 */
	abstract public function receive(callable $callback, string $queue = ''): bool;

	/**
	 * Track progress of a message in the queuing system.
	 *
	 * @param int $currentStep the current step number
	 * @param int $totalSteps  the total number of steps
	 */
	abstract public function progress(int $currentStep, int $totalSteps);

	/**
	 * Get info on a message in the queuing system.
	 *
	 * @param $id identifier in the queue.
	 */
	abstract public function getMessage(string $id);

	/**
	 * Set the delay in minutes
	 *
	 * @param  integer $min
	 * @return $this
	 */
	public function delay($min)
	{
		$this->available_at = (new Time)->modify('+' . $min . ' minutes');

		return $this;
	}

	/**
	 * Set the delay to a specific time
	 *
	 * @param  datetime $datetime
	 * @param  mixed    $time
	 * @return $this
	 */
	public function delayUntil($time)
	{
		if ( ! $time instanceof Time)
		{
			if ($time instanceof \DateTime)
			{
				$time = Time::instance($time, 'en_US');
			}
			else
			{
				$time = new Time($time);
			}
		}

		$this->available_at = $time;

		return $this;
	}

	/**
	 * Set the weight
	 *
	 * @param  integer $weight
	 * @return $this
	 */
	public function weight($weight)
	{
		$this->weight = $weight;

		return $this;
	}

	/**
	 * run a command from the queue
	 *
	 * @param string $command the command to run
	 */
	public function command(string $command): Message
	{
		$data = [
			'command' => $command,
		];

		return $this->send($data);
	}

	/**
	 * run an anonymous function from the queue.
	 *
	 * @param callable $closure function to run
	 *
	 * TODO: this currently doesn't work with database
	 * as you can't serialize a closure. May need
	 * to implement something like laravel does to get
	 * around this.
	 */
	public function closure(callable $closure): Message
	{
		$data = [
			'closure' => $closure,
		];

		return $this->send($data);
	}

	/**
	 * run a job from the queue
	 *
	 * @param string $job  the job to run
	 * @param mixed  $data data for the job
	 */
	public function job(string $job, $data = []): Message
	{
		$data = [
			'job'  => $job,
			'data' => $data,
		];

		return $this->send($data);
	}

	/**
	 * run a job from the queue
	 *
	 * @param string  $job     the job to run
	 * @param Message $message the message experiencing the error
	 */
	protected function fireOnFailure(\Throwable $e, Message $message)
	{
		\CodeIgniter\Events\Events::trigger('queue_failure', $e, $message);
	}

	/**
	 * run a job from the queue
	 *
	 * @param Message $message the message that just succeeded.
	 */
	protected function fireOnSuccess(Message $message)
	{
		\CodeIgniter\Events\Events::trigger('queue_success', $message);
	}
}
