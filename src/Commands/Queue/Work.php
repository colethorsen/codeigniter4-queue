<?php namespace CodeIgniter\Queue\Commands\Queue;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

use CodeIgniter\Queue\Exceptions\QueueException;

/**
 * //TODO: if/when this gets added to the CI4 core it
 * would probably be best to add this directly to the
 * migration generator in a similar fashion to sessions.
 */

/**
 * Generates a skeleton migration file.
 */
class Work extends BaseCommand
{
	/**
	 * The Command's Group
	 *
	 * @var string
	 */
	protected $group = 'Queue';

	/**
	 * The Command's Name
	 *
	 * @var string
	 */
	protected $name = 'queue:work';

	/**
	 * The Command's Description
	 *
	 * @var string
	 */
	protected $description = 'Works the queue.';

	/**
	 * The Command's Usage
	 *
	 * @var string
	 */
	protected $usage = 'queue:work';

	/**
	 * The Command's Options
	 *
	 * @var array
	 */
	protected $options = [
		'--queue' => 'The name of the queue to work, if not specified it will work the default queue',
	];

	/**
	 * Actually execute a command.
	 *
	 * @param array $params
	 */
	public function run(array $params)
	{
		$queue = $params['queue'] ?? config('Queue')->defaultQueue;

		CLI::write('Working Queue: ' . $queue, 'yellow');

		$response      = true;
		$jobsProcessed = 0;
		$startTime     = time();

		do
		{
			try
			{
				$this->stopIfNecessary($startTime, $jobsProcessed);

				$response = \Config\Services::queue()->fetch([$this, 'fire'], $queue);

				$jobsProcessed++;
			}
			catch (\Throwable $e)
			{
				CLI::error('Failed', 'light_red');
				CLI::error("Exception: {$e->getCode()} - {$e->getMessage()}\nfile: {$e->getFile()}:{$e->getLine()}");

				log_exception($e);
			}
		}
		while($response === true);

		CLI::write('Completed Working Queue', 'green');
	}

	/**
	 * work an individual item in the queue.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function fire(array $data)
	{
		if (isset($data['command']))
		{
			CLI::write('Executing Command: ' . $data['command']);

			command($data['command']);
		}
		else if (isset($data['job']))
		{
			CLI::write('Executing Job: ' . $data['job']);

			$data['job']::handle($data['data']);
		}
		else if (isset($data['closure']))
		{
			CLI::write('Executing Closure: ' . $data['job']);

			$data['job']::handle($data['data']);
		}
		else
		{
			throw QueueException::couldNotWork();
		}

		CLI::write('Success', 'green');
	}

	/**
	 * Determine if we should stop the worker.
	 *
	 * @param integer $startTime
	 * @param integer $jobsProcessed
	 */
	protected function stopIfNecessary($startTime, $jobsProcessed)
	{
		$shouldQuit = false;

		$maxTime = ini_get('max_execution_time') - 5; //max execution time minus a bit of a buffer (5 sec).

		$maxMemory   = ($this->getMemoryLimit() / 1024 / 1024) - 10; //max memory with a buffer (10MB);
		$memoryUsage = memory_get_usage(true) / 1024 / 1024;

		$maxBatch = config('Queue')->maxWorkerBatch;

		//max time limit.
		if ($maxTime > 0 && time() - $startTime > $maxTime)
		{
			$shouldQuit = true;
			$reason     = 'Time Limit Reached';
		}
		//max memory
		else if ($maxMemory > 0 && $memoryUsage > $maxMemory)
		{
			$shouldQuit = true;
			$reason     = 'Memory Limit Reached';
		}
		else if ($maxBatch > 0 && $jobsProcessed >= $maxBatch)
		{
			$shouldQuit = true;
			$reason     = 'Maxmium Batch Size Reached';
		}

		if (isset($reason))
		{
			CLI::write('Exiting Worker: ' . $reason, 'yellow');
			exit;
		}

		return true;
	}
	/**
	 * calculate the memory limit
	 *
	 * @return integer memory limit in bytes.
	 */
	protected function getMemoryLimit()
	{
		$memory_limit = ini_get('memory_limit');

		//if there is no memory limit just set it to 2GB
		if($memory_limit = -1)
			return 2 * 1024 * 1024 * 1024;

		preg_match('/^(\d+)(.)$/', $memory_limit, $matches);

		if(!isset($matches[2]))
			throw new \Exception('Unknown Memory Limit');

		switch($matches[2])
		{
			case 'G' :
				$memoryLimit = $matches[1] * 1024 * 1024 * 1024;
				break;
			case 'M' :
				$memoryLimit = $matches[1] * 1024 * 1024;
				break;
			case 'K' :
				$memoryLimit = $matches[1] * 1024;
				break;
			default :
				throw new \Exception('Unknown Memory Limit');

			return $memoryLimit;
		}
	}
}
