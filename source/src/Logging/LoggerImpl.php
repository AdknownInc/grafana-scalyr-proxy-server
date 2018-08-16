<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 17/05/18
 * Time: 3:09 AM
 */

namespace Adknown\ProxyScalyr\Logging;


class LoggerImpl extends aLogger
{
	const EVENT_LOG = "log";
	const DEBUG_LOG = "debug";

	const LOGS = [
		self::EVENT_LOG => ['normal_log.txt'],
		self::DEBUG_LOG => ['debug_log.txt']
	];

	public static function Log($message = '', $data = [])
	{
		if (is_object($data))
		{
			$data = (array)$data;
		}
		if ($data === null)
		{
			$data = [];
		}
		return static::Append(
			self::EVENT_LOG,
			$message,
			$data
		);
	}

	public static function DebugLog($message = '', $data = [])
	{
		if (is_object($data))
		{
			$data = (array)$data;
		}
		if ($data === null)
		{
			$data = [];
		}
		return static::Append(
			self::DEBUG_LOG,
			$message,
			$data
		);
	}
}