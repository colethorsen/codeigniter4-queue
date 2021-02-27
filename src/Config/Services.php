<?php namespace CodeIgniter\Queue\Config;

use CodeIgniter\Queue\Queue;
use Config\Services as BaseServices;

class Services extends BaseServices
{

	//--------------------------------------------------------------------

	/**
	 * The Queue class.
	 *
	 * @param  mixed   $config
	 * @param  boolean $getShared
	 * @return CodeIgniter\Queue\Handlers\QueueHandlerInterface
	 */
	public static function queue($config = null, $getShared = true)
	{
		if ($getShared)
		{
			return self::getSharedInstance('queue', $config);
		}

		if (is_null($config))
		{
			$config = new \Config\Queue;
		}

		return (new Queue($config))->connect();
	}

	//--------------------------------------------------------------------
}
