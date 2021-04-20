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

		while (\Config\Services::queue()->fetch([$this, 'fire'], $queue))
		{
		}

		CLI::write('Completed', 'green');
	}

	/**
	 * work an individual item in the queue.
	 *
	 * @param array $data
	 *
	 * @return string
	 */
	public function fire(array $data): bool
	{
		$success = false;

		if (isset($data['command']))
		{
			CLI::write('Executing Command: ' . $data['command']);

			$response = command($data['command']);

			$success = ($response === false) ? false : true;
		}
		else if (isset($data['job']))
		{
			CLI::write('Executing Job: ' . $data['job']);

			$success = $data['job']::handle($data['data']);
		}
		else if (isset($data['closure']))
		{
			CLI::write('Executing Closure: ' . $data['job']);

			$success = $data['job']::handle($data['data']);
		}
		else
		{
			throw QueueException::couldNotWork();
		}

		if ($success === true)
		{
			CLI::write('Success', 'green');
		}
		else
		{
			CLI::write('Failed', 'red');
		}

		return $success;
	}
}
