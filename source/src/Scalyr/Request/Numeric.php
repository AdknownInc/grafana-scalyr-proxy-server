<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 06/07/18
 * Time: 2:13 PM
 */

namespace Adknown\ProxyScalyr\Scalyr\Request;


use Exception;

class Numeric extends aBase
{
	const QUERY_TYPE = 'numeric';
	const FUNCTION_MEAN = 'mean';
	const FUNCTION_MIN = 'min';
	const FUNCTION_MAX = 'max';
	const FUNCTION_SUM_PER_SECOND = 'sumPerSecond';
	const FUNCTION_MEDIAN = 'median';
	const FUNCTION_P10 = 'p10';
	const FUNCTION_P50 = 'p50';
	const FUNCTION_P90 = 'p90';
	const FUNCTION_P95 = 'p95';
	const FUNCTION_P99 = 'p99';
	const FUNCTION_P999 = 'p999';
	const FUNCTION_COUNT = 'count';
	const FUNCTION_RATE = 'rate';

	/**
	 * @var string
	 */
	public $filter;

	/**
	 * @var string
	 */
	public $function;

	/**
	 * A datetime string for the start time you're searching for
	 *
	 * @var string
	 */
	public $startTime;

	/**
	 * A datetime string for the end time you're searching for
	 * Defaults to the current time.
	 *
	 * @var string
	 */
	public $endTime;

	/**
	 * @var int
	 *          the number of numeric values to return. The time range is divided into this many equal slices. For instance, suppose you query a four-hour period, with buckets = 4. The query will return four numbers, each covering a one-hour period.
	 *            You may specify a value from 1 to 5000. The default is 1.
	 */
	public $buckets;

	/**
	 * @var string
	 *             the execution priority for this query; defaults to "low". Use "low" for background operations where a delay of a second or so is acceptable. Low-priority queries have more generous rate limits.
	 */
	public $priority;

	/**
	 * Numeric constructor.
	 *
	 * @param string $filter    The filter used to match events
	 * @param string $function  The graph function used to compute a value from the filtered events
	 * @param string $subjectField
	 * @param string $startTime A datetime string for the start time you're searching for
	 * @param string $endTime
	 * @param int    $buckets   Number of numeric values to return. The time range is divided into this many equal slices. For instance, suppose you query a four-hour period, with buckets = 4. The query will return four numbers, each covering a one-hour period.
	 * @param string $priority
	 *
	 * @throws Exception
	 */
	public function __construct($filter, string $function, string $subjectField, string $startTime, string $endTime = "", int $buckets = 1, string $priority = self::PRIORITY_LOW)
	{
		$this->filter = $filter;
		$this->setFunction($function, $subjectField);
		$this->startTime = $startTime;
		$this->endTime = $endTime;
		$this->setBuckets($buckets);
		$this->priority = $priority;
	}

	/**
	 * Gets the function that will be passed to the api
	 *
	 * @param string $function
	 * @param string $subjectField
	 *
	 * @throws Exception
	 */
	public function setFunction($function, $subjectField)
	{
		switch($function)
		{
			case self::FUNCTION_MEAN:

			case self::FUNCTION_MIN:

			case self::FUNCTION_MAX:

			case self::FUNCTION_SUM_PER_SECOND:

			case self::FUNCTION_MEDIAN:

			case self::FUNCTION_P10:

			case self::FUNCTION_P50:

			case self::FUNCTION_P90:

			case self::FUNCTION_P95:

			case self::FUNCTION_P99:

			case self::FUNCTION_P999:
				$this->function = sprintf("%s(%s)", $function, $subjectField);
				break;
			case self::FUNCTION_COUNT:
			case self::FUNCTION_RATE:
				$this->function = $function;
				break;
			case '':
				$this->function = self::FUNCTION_RATE;
				break;
			default:
				throw new Exception("Unsupported function: $function");
		}
	}

	public function setBuckets(int $buckets)
	{
		$lower = 1;
		$upper = 5000;
		if($buckets < $lower || $buckets > $upper)
		{
			throw new Exception("Buckets must be between $lower and $upper. Attempted to set to $buckets.");
		}
		$this->buckets = $buckets;
	}
}