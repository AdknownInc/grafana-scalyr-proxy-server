<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 05/07/18
 * Time: 11:57 AM
 */

namespace Adknown\ProxyScalyr\Grafana\Response\Query;


class TimeSeriesTarget
{
	/**
	 * @var string|null The name of the target
	 */
	public $target;

	/**
	 * @var Datapoint[]
	 */
	public $datapoints;

	/**
	 * Multiplies $time by 1000 to convert it into milliseconds
	 *
	 * @param float $value - The value of the datapoint
	 * @param int $time - The timestamp of the datapoint, in seconds
	 */
	public function Append($value, $time) {
		$this->datapoints[] = [$value, $time * 1000];
	}

	public function __construct(?string $target, array $datapoints)
	{
		$this->target = $target;
		$this->datapoints = $datapoints;
	}
}