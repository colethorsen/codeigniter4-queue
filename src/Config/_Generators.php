<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

class Generators extends BaseConfig
{
	/**
	 * --------------------------------------------------------------------------
	 * Generator Commands' Views
	 * --------------------------------------------------------------------------
	 *
	 * This array defines the mapping of generator commands to the view files
	 * they are using. If you need to customize them for your own, copy these
	 * view files in your own folder and indicate the location here.
	 *
	 * You will notice that the views have special placeholders enclosed in
	 * curly braces `{...}`. These placeholders are used internally by the
	 * generator commands in processing replacements, thus you are warned
	 * not to delete them or modify the names. If you will do so, you may
	 * end up disrupting the scaffolding process and throw errors.
	 *
	 * YOU HAVE BEEN WARNED!
	 *
	 * @var array<string, string>
	 */
	public $views = [
		'make:queue' => 'CodeIgniter\Queue\Commands\Generators\Views\queue_migration.tpl.php',
	];
}
