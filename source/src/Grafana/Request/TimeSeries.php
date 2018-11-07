<?php
/**
 * Created by PhpStorm.
 * User: matt
 * Date: 05/07/18
 * Time: 11:46 AM
 */

namespace Adknown\ProxyScalyr\Grafana\Request;


class TimeSeries
{
	/**
	 * @var int
	 */
	public $panelId;

	/**
	 * @var Range
	 */
	public $range;

	/**
	 * @var string
	 */
	public $interval;

	/**
	 * @var Target[]
	 */
	public $targets;

	/**
	 * @var int
	 */
	public $maxDataPoints;

	/**
	 * @var bool
	 */
	public $parseComplex;
}