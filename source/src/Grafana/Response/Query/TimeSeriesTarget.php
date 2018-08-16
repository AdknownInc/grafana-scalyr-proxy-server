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

	public function __construct(?string $target, array $datapoints)
	{
		$this->target = $target;
		$this->datapoints = $datapoints;
	}
}