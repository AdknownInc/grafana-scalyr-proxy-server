<?php
/**
 * Generic logging class for use in all projects
 *
 * v0.1 - 2018-01-30 - Andrew Gorrie
 *      - Changed to an abstract pattern
 *
 * v0.1 - 2018-01-30 - Andrew Gorrie
 *      - Made the log folder dynamic with a default
 *
 * v0.0 - 2018-01-30 - Andrew Gorrie
 *      - Proposal for logger created
 */

namespace Adknown\ProxyScalyr\Logging;

use Adknown\ProxyScalyr\Utilities\Utilities;

abstract class aLogger
{
	const LOG_FOLDER = "/var/log/myapp/";
	const BASE_LOGS = [
		self::EVENT_EXCEPTION => ['exception_log.txt'],
	];
	const LOGS = [];

	const EVENT_EXCEPTION = 'exception';

	/**
	 * @param \Throwable $ex              The exception
	 * @param string    $extendedMessage A message to prepend to the exception message
	 * @param array     $data            Extra data to add to data
	 *
	 * @return bool
	 */
	public static function Exception($ex, $extendedMessage = '', $data = [])
	{
		return static::Append(
			self::EVENT_EXCEPTION,
			$extendedMessage . $ex->getMessage(),
			array_merge(
				[
					'file'        => $ex->getFile() . ":" . $ex->getCode(),
					'stack_trace' => $ex->getTraceAsString()
				],
				$data
			)
		);
	}

	/**
	 * Gets a log file path for  specified log
	 *
	 * @param $eventType
	 *
	 * @return null|string The log file path
	 */
	protected static function GetLogFile($eventType)
	{
		if(!isset(static::BASE_LOGS[$eventType]) && !isset(static::LOGS[$eventType]))
		{
			return null;
		}

		if(isset(static::BASE_LOGS[$eventType]))
		{
			$log = static::BASE_LOGS[$eventType];
		}
		else
		{
			$log = static::LOGS[$eventType];
		}

		$folder = isset($log[1]) ? $log[1] : static::LOG_FOLDER;

		return $folder . $log[0];
	}

	/**
	 * Append the string to the log file.
	 *
	 * @param string $eventType
	 * @param string $message
	 * @param array  $data Key value array of properties to log
	 *
	 * @return bool Returns true is logged successfully, otherwise false
	 */
	protected static function Append($eventType, $message, array $data = [])
	{
		if(($logFile = self::GetLogFile($eventType)) === null)
		{
			return false;
		}

		$toAppend = json_encode([
			'timestamp'  => static::GetDateTimeString(),
			'ip'         => Utilities::GetUserIp(),
			'user_agent' => Utilities::GetUserAgent(),
			'serve_url'  => Utilities::GetCurrentPageUrl(),
			'message'    => $message,
			'data'       => $data
		]);

		if($toAppend === false)
		{
			return false;
		}

		self::EnsureDirectory($logFile);
		file_put_contents($logFile, $toAppend . "\n", FILE_APPEND);
		return true;
	}

	/**
	 * Ensures the directory of the file exists.
	 * Creates the directory if it does not exist.
	 *
	 * @param string $filePath The directory to test for
	 */
	protected static function EnsureDirectory($filePath)
	{
		$dir = dirname($filePath);

		if(file_exists($dir) === false)
		{
			mkdir($dir, 0777, true);
		}
	}

	protected static function GetDateTimeString($time = 'now', $timeZone = 'UTC')
	{
		$d = new \DateTime($time, new \DateTimeZone($timeZone));

		return $d->format("Y-m-d H:i:s") . '.'
			. str_pad(round($d->format("u") / 1000, 0), 3, '0');
	}
}
