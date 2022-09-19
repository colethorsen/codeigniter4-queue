<?php namespace CodeIgniter\Queue;

/**
 * Message Entity.
 */
class Message extends \CodeIgniter\Entity\Entity
{
	protected $attributes = [
        'id'               => null,
        'queue'            => null,
        'status'           => Status::WAITING,
        'attempts'         => 0,
        'data'             => null,
        'error'            => '',
        'progress_current' => 0,
        'progress_total'   => 0,
        'available_at'     => null,
        'created_at'       => null,
        'updated_at'       => null,
    ];

	protected $casts = [
		'id'               => 'integer',
		'attempts'         => 'integer',
		'data'             => 'array',
		'progress_current' => 'integer',
		'progress_total'   => 'integer',
		'status'           => 'integer',
		'weight'           => 'integer',
		'available_at'     => 'datetime',
		'created_at'       => 'datetime',
		'updated_at'       => 'datetime',
	];

	/**
	 * A localized human readable status.
	 */
	public function getStatusText(): string
	{
		$class    = new \ReflectionClass(Status::class);
		$statuses = $class->getConstants();

		$statuses = array_flip($statuses);

		$status = $statuses[$this->status] ?? false;

		if($status)
			return lang('queue.status.' . strtolower($status));

		return lang('queue.status.unknown');
	}

	/**
	 * a readable progress report on the message.
	 */
	public function getProgress(): string
	{
		if($this->status != Status::EXECUTING)
			return $this->status_text;

		if($this->progress_total == 0)
			return $this->status_text;

		return ($this->progress_current / $this->progress_total * 100) . '%';
	}
}
