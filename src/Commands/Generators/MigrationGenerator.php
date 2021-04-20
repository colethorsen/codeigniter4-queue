<?php namespace CodeIgniter\Queue\Commands\Generators;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use CodeIgniter\CLI\GeneratorTrait;

/**
 * //TODO: if/when this gets added to the CI4 core it
 * would probably be best to add this directly to the
 * migration generator in a similar fashion to sessions.
 */

/**
 * Generates a skeleton migration file.
 */
class MigrationGenerator extends BaseCommand
{
	use GeneratorTrait;

	/**
	 * The Command's Group
	 *
	 * @var string
	 */
	protected $group = 'Generators';

	/**
	 * The Command's Name
	 *
	 * @var string
	 */
	protected $name = 'make:queue';

	/**
	 * The Command's Description
	 *
	 * @var string
	 */
	protected $description = 'Generates a new queue table migration file.';

	/**
	 * The Command's Usage
	 *
	 * @var string
	 */
	protected $usage = 'make:queue [options]';

	/**
	 * The Command's Arguments
	 *
	 * @var array
	 */
	protected $arguments = [
		//'name' => 'The migration class name.',
	];

	/**
	 * The Command's Options
	 *
	 * @var array
	 */
	protected $options = [
		'--table'     => 'Table name to use for the queue. Default: "ci_queue".',
		'--dbgroup'   => 'Database group to use for database sessions. Default: "default".',
		'--namespace' => 'Set root namespace. Default: "APP_NAMESPACE".',
	];

	/**
	 * Actually execute a command.
	 *
	 * @param array $params
	 */
	public function run(array $params)
	{
		$this->component = 'Migration';
		$this->directory = 'Database\Migrations';
		$this->template  = 'queue_migration.tpl.php';

		$table     = $params['table'] ?? CLI::getOption('table') ?? 'ci_queue';
		$params[0] = "_create_{$table}_table";

		$this->execute($params);
	}

	/**
	 * Prepare options and do the necessary replacements.
	 *
	 * @param string $class
	 *
	 * @return string
	 */
	protected function prepare(string $class): string
	{
		$table   = $this->getOption('table');
		$DBGroup = $this->getOption('dbgroup');

		$data['table']   = is_string($table) ? $table : 'ci_queue';
		$data['DBGroup'] = is_string($DBGroup) ? $DBGroup : 'default';

		return $this->parseTemplate($class, [], [], $data);
	}

	/**
	 * Change file basename before saving.
	 *
	 * @param string $filename
	 *
	 * @return string
	 */
	protected function basename(string $filename): string
	{
		return gmdate(config('Migrations')->timestampFormat) . basename($filename);
	}

	/**
	 * overwrites the renderTempalte to find the queue_migration view.
	 * //TODO: if/when this gets added to the core this would get removed
	 * once the files are in the right place.
	 */
	protected function renderTemplate(array $data = []): string
	{
		/*
		$reflector = new \ReflectionClass($this);
		$path = $reflector->getFileName();

		$parts = explode('/', $path);
		$last = array_pop($parts);
		$path = implode('/', $parts);
		echo $path;

		exit;
		*/
		return view("CodeIgniter\\Queue\\Commands\\Generators\\Views\\{$this->template}", $data, ['debug' => false]);
	}
}
