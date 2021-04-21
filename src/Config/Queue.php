<?php namespace CodeIgniter\Queue\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Queue Configuration file.
 */
class Queue extends BaseConfig
{
	public $defaultConnection = 'database';
	public $defaultQueue      = 'default';

	public $maxRetries              = 3;
	public $timeout                 = 30;
	public $deleteDoneMessagesAfter = 30 * DAY;

	//the max number of queue entries to process at once.
	public $maxWorkerBatch          = 20;

	/*
	public $rabbitmq = [
		'handler'  => 'RabbitMQ',
		'host'     => 'localhost',
		'port'     => 5672,
		'user'     => 'guest',
		'password' => 'guest',
		'vhost'    => '/',
		'do_setup' => true,
	];
	*/
	public $database = [
		'handler'          => 'Database',
		'dbGroup'          => 'default',
		'sharedConnection' => true,
		'table'            => 'ci_queue',
	];

	/*
	public $tests = [
		'handler'          => 'Database',
		'dbGroup'          => 'tests',
		'sharedConnection' => true,
		'table'            => 'ci_queue',
	];
	*/
}
