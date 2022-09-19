<?php namespace CodeIgniter\Queue\Handlers\DatabaseHandler;

use CodeIgniter\Model as BaseModel;

use CodeIgniter\Queue\Message;

class Model extends BaseModel
{
	protected $primaryKey    = 'id';

	protected $returnType    = Message::class;

	protected $useTimestamps = true;

	protected $allowedFields = [
        'id',
        'queue',
        'status',
        'attempts',
        'data',
        'error',
        'progress_current',
        'progress_total',
        'available_at',
//        'created_at',
//        'updated_at',
    ];
}
