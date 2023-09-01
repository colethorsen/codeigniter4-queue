<?php namespace CodeIgniter\Queue\Jobs;

abstract class Job
{
	protected static $queue;

	protected static $defaultWeight = 100;

	protected static $weight = null;

	/**
	 * handle the execution of a job
	 *
	 * @param  array   $data data needed by the job
	 * @return boolean
	 */
	abstract public static function handle(array $data = []): bool;

	/**
	 * Dispatches the job into the queue.
	 *
	 * @param mixed $data any data that will be needed by the job
	 *
	 * @return identifier for the job from the queue.
	 */
	public static function dispatch($data = [])
	{
		$queue = self::getQueue();

		$queue->weight(self::$weight ?: self::$defaultWeight);

		self::$weight = null;

		return $queue->job(get_called_class(), $data);
	}

	/**
	 * set a queue other than the default to
	 * dispatch this job to.
	 *
	 * @param  string $queue the name of the queue
	 * @return this
	 */
	public static function queue($queue)
	{
		return get_called_class();
	}

	/**
	 * set a queue other than the default to
	 * dispatch this job to.
	 *
	 * @param  int  $queue weight other than default
	 * @return this
	 */
	public static function weight(int $weight)
	{
		self::$weight = $weight;

		return get_called_class();
	}

	/**
	 * delay execution of job until a specific time
	 *
	 * @param  mixed $time time as a string, time or datetime
	 * @return this
	 */
	public static function delayUntil($time)
	{
		$queue = self::getQueue();
		$queue->delayUntil($time);

		return get_called_class();
	}

	/**
	 * delay execution of job for a certain number of
	 * minutes
	 *
	 * @param  number $min minutes to delay excution
	 * @return this
	 */
	public static function delay($min)
	{
		$queue = self::getQueue();
		$queue->delay($min);

		return get_called_class();
	}

	protected static function getQueue()
	{
		if ( ! self::$queue)
		{
			self::$queue = \Config\Services::queue();
		}

		return self::$queue;
	}

	/**
	 * Track the progress of a job.
	 *
	 * @param int $currentStep the current step number
	 * @param int $totalSteps  the total number of steps
	 */
	public static function setProgress(int $currentStep, int $totalSteps)
	{
		$queue = self::getQueue();
		$queue->progress($currentStep, $totalSteps);
	}
}
