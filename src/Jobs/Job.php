<?php namespace CodeIgniter\Queue\Jobs;

use CodeIgniter\I18n\Time;

abstract class Job
{
	protected static $queue;

	protected static $defaultWeight = 100;

	protected static $weight = null;

	protected static $availableAt = null;

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
		$calledClass = get_called_class();
		$queue       = self::getQueue();

		$queue->weight(self::$weight ?: self::$defaultWeight);
		self::$weight = null;

		if (is_a($calledClass, SynchronousJob::class, true) && self::$availableAt === null)
		{
			return static::handle($data);
		}

		if (self::$availableAt !== null)
		{
			$queue->delayUntil(self::$availableAt);
			self::$availableAt = null;
		}

		return $queue->job($calledClass, $data);
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
		if ( ! $time instanceof Time)
		{
			$time = $time instanceof \DateTime
				? Time::instance($time, 'en_US')
				: new Time($time);
		}

		if ($time > new Time)
		{
			static::$availableAt = $time;
		}

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
		static::$availableAt = (new Time)->modify('+' . $min . ' minutes');

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
