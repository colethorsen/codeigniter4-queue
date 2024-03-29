<?php

/**
 * Queue language strings.
 *
 * @package    CodeIgniter
 * @license    https://opensource.org/licenses/MIT	MIT License
 * @link       https://codeigniter.com
 * @since      Version 4.0.0
 * @filesource
 *
 * @codeCoverageIgnore
 */

return [
	'invalid_connection'   => "'{0}' is not a valid queue connection group.",
	'failGetQueueDatabase' => 'There was an error fetching from the queue table: `{0}`',

	'could_not_work'       => 'There is currently no functionality to work this queue, entries should be jobs, commands, or closures',

	'status' => [
		'waiting'   => 'Waiting',
		'executing' => 'Executing',
		'done'      => 'Done',
		'failed'    => 'Failed',
		'unknown'   => 'Unknown',
	],
];
