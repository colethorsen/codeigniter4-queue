<?php namespace CodeIgniter\Queue\Exceptions;

use CodeIgniter\Exceptions\ExceptionInterface;
use CodeIgniter\Exceptions\FrameworkException;

class QueueException extends FrameworkException implements ExceptionInterface
{
	public static function forInvalidGroup(string $group)
	{
		return new static(lang('Queue.invalid_group', [$group]));
	}

	public static function forFailGetQueueDatabase(string $table)
	{
		return new static(lang('Queue.failGetQueueDatabase', [$table]));
	}

	public static function forWorkFailure()
	{
		return new static(lang('Queue.could_not_work'));
	}
}
